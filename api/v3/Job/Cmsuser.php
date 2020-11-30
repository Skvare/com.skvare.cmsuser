<?php
use CRM_Cmsuser_ExtensionUtil as E;

/**
 * Job.Cmsuser API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_job_Cmsuser_spec(&$spec) {
}

/**
 * Job.Cmsuser API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_job_Cmsuser($params) {
  $domainID = CRM_Core_Config::domainID();

  $settings = Civi::settings($domainID);
  $setDefaults = [];
  $elementNames = [
    'cmsuser_pattern', 'cmsuser_notify', 'cmsuser_group_create', 'cmsuser_group_history', 'cmsuser_group_reset',
    'cmsuser_tag_create', 'cmsuser_tag_history', 'cmsuser_tag_reset'
  ];

  foreach ($elementNames as $elementName) {
    $setDefaults[$elementName] = $settings->get($elementName);
  }

  if (!empty($setDefaults['cmsuser_group_create'])) {
    _cms_user_create($setDefaults, TRUE);
  }

  if (!empty($setDefaults['cmsuser_tag_create'])) {
    _cms_user_create($setDefaults, FALSE);
  }

  if (!empty($setDefaults['cmsuser_group_reset'])) {
    //_cms_user_reset($setDefaults, TRUE);
  }

  if (!empty($setDefaults['cmsuser_tag_reset'])) {
    //_cms_user_reset($setDefaults, FALSE);
  }

  return civicrm_api3_create_success(1, $params);
}

/**
 * @param $setDefaults
 * @param bool $isGroup
 */
function _cms_user_create($setDefaults, $isGroup = TRUE) {
  $domainID = CRM_Core_Config::domainID();
  // check this call for group or tag
  if ($isGroup) {
    $contactX = _get_group_contact($setDefaults['cmsuser_group_create']);
  }
  else {
    $contactX = _get_tagged_contact($setDefaults['cmsuser_tag_create']);
  }

  // if contact present, process it.
  if (!empty($contactX)) {
    // generate usernames
    $pattern = $setDefaults['cmsuser_pattern'];
    $p = new \Civi\Token\TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
    ]);
    $p->addMessage('username', $pattern, 'text/plain');

    foreach ($contactX as $contactID) {
      $p->addRow()->context('contactId', $contactID);
    }
    $p->evaluate();
    $groupContactDeleted = [];
    foreach ($p->getRows() as $row) {
      $contactID = $row->context['contactId'];
      $cms_name = $row->render('username');
      $api = NULL;

      // don't bother attempting to create user account
      // when the contact is already connected to a user
      $matches = civicrm_api3('UFMatch', 'get', [
        'sequential' => 1,
        'contact_id' => $contactID,
      ]);
      // if record present in UFMatch, check its belong to current domain
      if ($matches['count'] > 0) {
        foreach ($matches['values'] as $match) {
          // yes , its belong to same domain, then throw error
          if ($match['domain_id'] == $domainID) {
            $api = [
              'is_error' => 1,
              'error_message' => 'Contact is already connected to a CMS user account.',
            ];
            break;
          }
        }
        // no record, then create entry in UF Match
        if (!$api) {
          civicrm_api3('UFMatch', 'create', [
            'contact_id' => $contactID,
            'domain_id' => $domainID,
            'uf_id' => $match['uf_id'],
            'uf_name' => $match['uf_name'],
          ]);
          $api = [
            'is_error' => 1,
            'error_message' => 'Contact was connected to an existing CMS user account.',
          ];
        }
      }
      else {
        // no entry found in UF Match then create user...
        try {
          // create CMS user
          $createParams = [
            'cms_name' => $cms_name, // generated cms name using civicrm token
            'contactID' => $contactID,
            'notify' => $setDefaults['cmsuser_notify'],
          ];
          // get primary email of civicrm contact
          $createParams['email'] = civicrm_api3('Email', 'getvalue', [
            'contact_id' => $contactID,
            'is_primary' => 1,
            'return' => 'email',
          ]);
          // call our custom api to create user
          CRM_Core_Error::debug_var('Cmsuser API  $createParams', $createParams);
          $api = civicrm_api3('Cmsuser', 'Create', $createParams);
        }
        catch (CiviCRM_API3_Exception $e) {
          $api = [
            'is_error' => 1,
            'error_message' => $e->getMessage(),
          ];
        }
      }
      // if no error found then remove contact from Tag / Group and then add same contact to another Tag / Group to
      // mainain history of contact , that are created using scheduled job
      if (empty($api['is_error'])) {
        try {
          if ($isGroup) {
            if ($setDefaults['cmsuser_group_history']) {
              // add contact to group
              $resultGroupContact = civicrm_api3('GroupContact', 'create', [
                'contact_id' => $contactID,
                'group_id' => $setDefaults['cmsuser_group_history'],
              ]);
            }
            $groupContactDeleted[] = $contactID;
          }
          else {
            // add contact to tag
            if ($setDefaults['cmsuser_tag_history']) {
              civicrm_api3('EntityTag', 'create', [
                'entity_table' => 'civicrm_contact',
                'entity_id' => $contactID,
                'tag_id' => $setDefaults['cmsuser_tag_history'],
              ]);
            }
            // and then remove it from Tag, so that on next iteration, same contact not get pulled
            $result = civicrm_api3('EntityTag', 'delete', [
              'entity_table' => 'civicrm_contact',
              'entity_id' => $contactID,
              'tag_id' => $setDefaults['cmsuser_tag_create'],
            ]);
          }
        }
        catch (CiviCRM_API3_Exception $e) {
        }
      }
    }

    // remove contacts from Group, so that on next iteration, same contact not get pulled
    // this block kept outside loop to avoid cache clear performance on every delete action. Passing all contacts in
    // one go.
    if ($isGroup and !empty($groupContactDeleted)) {
      foreach ($groupContactDeleted as $contactId) {
        // api does not accept multiple contacts, so iterating here.
        $result = civicrm_api3('GroupContact', 'delete', [
          'contact_id' => $contactId,
          'group_id' => $setDefaults['cmsuser_group_create'],
          'skip_undelete' => TRUE,
        ]);
      }
    }
  }
}

/**
 * @param $setDefaults
 * @param bool $isGroup
 */
function _cms_user_reset($setDefaults, $isGroup = TRUE) {
  $domainID = CRM_Core_Config::domainID();
  // check this call for group or tag
  if ($isGroup) {
    $contactX = _get_group_contact($setDefaults['cmsuser_group_reset']);
  }
  else {
    $contactX = _get_tagged_contact($setDefaults['cmsuser_tag_reset']);
  }

  // if contact present, process it.
  if (!empty($contactX)) {
    $config = CRM_Core_Config::singleton();

    if ($config->userSystem->is_drupal && CIVICRM_UF == 'Drupal') {
      // Drupal 7
      require_once DRUPAL_ROOT . '/modules/user/user.pages.inc';
    }
    elseif ($config->userSystem->is_drupal && CIVICRM_UF == 'Drupal8') {
      // Drupal 8
    }
    else {
      return;
    }
    $domainID = CRM_Core_Config::domainID();
    $groupContactDeleted = [];
    foreach ($contactX as $contactID) {
      $api = NULL;
      try {
        // get drupal user id from uf match
        $uf_id = civicrm_api3('UFMatch', 'getvalue', [
          'contact_id' => $contactID,
          'domain_id' => $domainID,
          'return' => 'uf_id',
        ]);
        // no uf id found then do nothging...
        if (empty($uf_id)) {
          return;
        }

        if ($config->userSystem->is_drupal && CIVICRM_UF == 'Drupal') {
          // for Drupal 7
          $user = user_load($uf_id);
          $form_state = [
            'values' => [
              'account' => $user,
            ],
          ];
          user_pass_submit(NULL, $form_state);
        }
        elseif ($config->userSystem->is_drupal && CIVICRM_UF == 'Drupal8') {
          // for Drupal 8
          $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
          $account = \Drupal\user\Entity\User::load($uf_id);
          $mail = _user_mail_notify('password_reset', $account, $langcode);
        }

        // all ok
        $api = [
          'is_error' => 0,
        ];
      }
      catch (CiviCRM_API3_Exception $e) {
        $api = [
          'is_error' => 1,
          'error_message' => $e->getMessage(),
        ];
      }

      // if no error found then remove contact from Tag / Group
      if (empty($api['is_error'])) {
        try {
          if ($isGroup) {
            // for group , collect all contact ids then do at the end of the operation.
            $groupContactDeleted[] = $contactID;
          }
          else {
            // remove contact from Reset Tag, so that on next iteration, same contact not get pulled
            $result = civicrm_api3('EntityTag', 'delete', [
              'entity_table' => 'civicrm_contact',
              'entity_id' => $contactID,
              'tag_id' => $setDefaults['cmsuser_tag_reset'],
            ]);
          }
        }

        catch (CiviCRM_API3_Exception $e) {

        }
      }

      // remove contacts from Group, so that on next iteration, same contact not get pulled
      // this block kept outside loop to avoid cache clear performance on every delete action. Passing all contacts in
      // one go.
      if ($isGroup and !empty($groupContactDeleted)) {
        foreach ($groupContactDeleted as $contactId) {
          // api does not accept multiple contacts, so iterating here.
          $result = civicrm_api3('GroupContact', 'delete', [
            'contact_id' => $contactId,
            'group_id' => $setDefaults['cmsuser_group_reset'],
            'skip_undelete' => TRUE,
          ]);
        }
      }
    }
  }
}

/**
 *
 * @param $tag_id
 * @return array
 */
function _get_tagged_contact($tag_id) {
  $tagContacts = [];
  $api = civicrm_api3('EntityTag', 'get', [
    'sequential' => 1,
    'tag_id' => $tag_id,
    'options' => [
      'sort' => 'id ASC',
      'limit' => 25,
    ],
  ]);
  foreach ($api['values'] as $entity) {
    $tagContacts[] = $entity['entity_id'];
  }
  return $tagContacts;
}

/**
 *
 * @param $group_id
 * @return array
 */
function _get_group_contact($group_id) {
  $groupContactResult = civicrm_api3('GroupContact', 'get', [
    'sequential' => 1,
    'return' => ["contact_id"],
    'group_id' => $group_id,
  ]);
  $groupContacts = [];
  // make list of all contact ids
  $domainID = CRM_Core_Config::domainID();
  foreach ($groupContactResult['values'] as $entity) {
    $groupContacts[] = $entity['contact_id'];
  }
  return $groupContacts;
}
