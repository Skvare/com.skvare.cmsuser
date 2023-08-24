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
    'cmsuser_pattern', 'cmsuser_notify', 'cmsuser_preferred_language', 'cmsuser_group_create', 'cmsuser_group_history', 'cmsuser_group_reset',
    'cmsuser_tag_create', 'cmsuser_tag_history', 'cmsuser_tag_reset', 'cmsuser_cms_roles', 'cmsuser_user_fields',
    'cmsuser_allow_existing_user_login', 'cmsuser_block_roles_autologin'
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
    _cms_user_reset($setDefaults, TRUE);
  }

  if (!empty($setDefaults['cmsuser_tag_reset'])) {
    _cms_user_reset($setDefaults, FALSE);
  }

  return civicrm_api3_create_success(1, $params);
}

/**
 * @param $setDefaults
 * @param bool $isGroup
 * @param array $createImmediately list of contact ids
 */
function _cms_user_create($setDefaults, $isGroup = TRUE,
                          $createImmediately = [], $throughForm = FALSE) {
  $domainID = CRM_Core_Config::domainID();
  $config = CRM_Core_Config::singleton();
  $cmsUserID = NULL;
  // check this call for group or tag
  if (!empty($createImmediately)) {
    $contactX = $createImmediately;
  }
  else {
    if ($isGroup) {
      $contactX = _get_group_contact($setDefaults['cmsuser_group_create']);
    }
    else {
      $contactX = _get_tagged_contact($setDefaults['cmsuser_tag_create']);
    }
  }
  $activities = _cmsuser_activities();
  // if contact present, process it.
  if (!empty($contactX)) {
    // generate usernames
    $pattern = $setDefaults['cmsuser_pattern'];
    $p = new \Civi\Token\TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
    ]);
    $p->addMessage('username', $pattern, 'text/plain');

    if (!empty($setDefaults['cmsuser_user_fields'])) {
      $p->addMessage('fieldsmapping', $setDefaults['cmsuser_user_fields'], 'text/plain');
    }

    foreach ($contactX as $contactID) {
      $p->addRow()->context('contactId', $contactID);
    }
    $p->evaluate();
    $groupContactDeleted = [];
    foreach ($p->getRows() as $row) {
      $contactID = $row->context['contactId'];
      $cms_name = $row->render('username');
      $additionalFields = $fieldsMapping = [];
      if (!empty($setDefaults['cmsuser_user_fields'])) {
        $fieldsMapping = $row->render('fieldsmapping');
      }

      if (!empty($fieldsMapping)) {
        // convert new line into array
        $fieldsMapping = explode("\n", $fieldsMapping);
        // trim all values
        $fieldsMapping = array_map('trim', $fieldsMapping);
        // remove any empty value
        $fieldsMapping = array_filter($fieldsMapping);
        // conver into url param format
        $fieldsMapping2 = implode("&", $fieldsMapping);
        // create key value array
        $additionalFields = help_parse_qs($fieldsMapping2);
      }
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
              'user_exist' => TRUE,
            ];
            $cmsUserID = $match['uf_id'];
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
            'user_exist' => TRUE,
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
          // Preferred Language synchronization, with fallback to the default
          if (array_key_exists('cmsuser_preferred_language', $setDefaults) && $setDefaults['cmsuser_preferred_language']) {
            $preferredContactLang = \Civi\Api4\Contact::get(FALSE)->addWhere('id', '=', $contactID)->addSelect('preferred_language')->execute()->first()['preferred_language'];
            $createParams['preferred_langcode'] = $preferredContactLang;
          }
          if (!empty($additionalFields)) {
            $createParams['custom_fields'] = $additionalFields;
          }
          // get primary email of civicrm contact
          $errors = [];
          try {
            $createParams['email'] = civicrm_api3('Email', 'getvalue', [
              'contact_id' => $contactID,
              'is_primary' => 1,
              'return' => 'email',
            ]);
          }
          catch (CiviCRM_API3_Exception $e) {
            $api = [
              'is_error' => 1,
              'error_message' => $e->getMessage(),
              'email_already_taken' => TRUE,
            ];
            $errors[] = $e->getMessage();
            $groupContactDeleted[] = $contactID;
          }
          $check_params = [
            'name' => $createParams['cms_name'],
            'mail' => $createParams['email'],
          ];
          if (!empty($createParams['email'])) {
            $config->userSystem->checkUserNameEmailExists($check_params, $errors);
          }
          if (empty($errors) && !empty($createParams['email'])) {
            // call our custom api to create user
            $api = civicrm_api3('Cmsuser', 'Create', $createParams);
            $cmsUserID = $api['values']['uf_id'];
            if (empty($api['is_error']) && !empty($setDefaults['cmsuser_cms_roles'])) {
              if ($api['values']['uf_id']) {
                if (CIVICRM_UF == 'Drupal8') {
                  $account = CRM_Cmsuser_Utils::loadUser($api['values']['uf_id']);
                  CRM_Cmsuser_Utils::addRoleToUser($account, $setDefaults);
                }
                elseif (CIVICRM_UF == 'Drupal') {
                  $account = CRM_Cmsuser_Utils::loadUser($api['values']['uf_id']);
                  CRM_Cmsuser_Utils::addRoleToUser($account, $setDefaults);
                }
                elseif (CIVICRM_UF == 'Backdrop') {
                  $account = CRM_Cmsuser_Utils::loadUser($api['values']['uf_id']);
                  CRM_Cmsuser_Utils::addRoleToUser($account, $setDefaults);

                }
                elseif (CIVICRM_UF == 'WordPress') {
                  if (!empty($setDefaults['cmsuser_cms_roles'])) {
                    $account = CRM_Cmsuser_Utils::loadUser($api['values']['uf_id']);
                    CRM_Cmsuser_Utils::addRoleToUser($account, $setDefaults);
                  }
                }
                elseif (CIVICRM_UF == 'Joomla') {
                  $joomlaID = (int)$api['values']['uf_id'];
                  $account = CRM_Cmsuser_Utils::loadUser($api['values']['uf_id']);
                  // Skip adding the group to the user if they already have it.
                  foreach ($setDefaults['cmsuser_cms_roles'] as $role) {
                    if ($account !== FALSE && !isset($account->groups[$role])) {
                      JUserHelper::addUserToGroup($joomlaID, $role);
                    }
                  }
                }
              }
            }
          }
          else {
            $api = [
              'is_error' => 1,
              'error_message' => print_r($errors, TRUE),
              'email_already_taken' => TRUE,
            ];
          }
        }
        catch (CiviCRM_API3_Exception $e) {
          $api = [
            'is_error' => 1,
            'error_message' => $e->getMessage(),
          ];
        }
      }
      // if no error found OR user is already exist then remove contact from Tag / Group then
      // add same contact to another Tag / Group to
      // mainain history of contact , that are created using scheduled job
      if (empty($api['is_error']) || (!empty($api['is_error']) && !empty($api['user_exist']))) {
        try {
          if ($isGroup) {
            if ($setDefaults['cmsuser_group_history']) {
              // add contact to group
              civicrm_api3('GroupContact', 'create', [
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

      // create activity
      $activityDetails = '';
      if (empty($api['is_error'])) {
        $activityStatus = $activities['activity_completed'];
        $activitySubject = "Created : $cms_name ({$api['values']['uf_id']})";
      }
      else {
        $activityStatus = $activities['activity_failed'];
        $activitySubject = "Failed to create User $cms_name";
        if (!empty($api['user_exist'])) {
          $activitySubject .= " (user already exist)";
        }
        if (!empty($api['error_message'])) {
          $activityDetails = $api['error_message'];
        }
      }
      try {
        $activityParams = [
          'source_record_id' => $contactID,
          'target_contact_id' => $contactID,
          'source_contact_id' => CRM_Core_Session::getLoggedInContactID() ?? $contactID,
          'activity_type_id' => $activities['activity_creation'],
          'status_id' => $activityStatus,
          'subject' => $activitySubject,
          'check_permissions' => 0,
          'details' => $activityDetails,
        ];
        $activityResult = civicrm_api3('Activity', 'create', $activityParams);
        if (!empty($api['is_error']) || !empty($api['email_already_taken'])) {
          $result = civicrm_api3('Activity', 'getcount', [
            'activity_type_id' => "User Account Creation",
            'status_id' => "Failed",
            'target_contact_id' => $contactID,
          ]);
          if ($result >= 4) {
            if ($isGroup) {
              $groupContactDeleted[] = $contactID;
              CRM_Core_Error::debug_log_message("remove contact from group as it unable to create user in $result attempt.");
            }
            else {
              // and then remove it from Tag, so that on next iteration, same contact not get pulled
              civicrm_api3('EntityTag', 'delete', [
                'entity_table' => 'civicrm_contact',
                'entity_id' => $contactID,
                'tag_id' => $setDefaults['cmsuser_tag_create'],
              ]);
              CRM_Core_Error::debug_log_message("remove contact from tag as it unable to create user in $result attempt.");
            }
          }
        }
      }
      catch (CiviCRM_API3_Exception $exception) {
        CRM_Core_Error::debug_var('exception', $exception->getMessage());
      }
    }

    // remove contacts from Group, so that on next iteration, same contact not get pulled
    // this block kept outside loop to avoid cache clear performance on every delete action. Passing all contacts in
    // one go.
    if ($isGroup and !empty($groupContactDeleted)) {
      CRM_Contact_BAO_GroupContact::removeContactsFromGroup($groupContactDeleted, $setDefaults['cmsuser_group_create'], 'Deleted');
    }

    if ($throughForm && !empty($cmsUserID)) {
      if (!empty($api['user_exist'])) {
        if (!empty($setDefaults['cmsuser_block_roles_autologin'])) {
          $hasBlockedRole = CRM_Cmsuser_Utils::isRolePresentToUser($cmsUserID,
            $setDefaults['cmsuser_block_roles_autologin']);
          if ($hasBlockedRole) {
            CRM_Core_Error::debug_log_message("CMS User Extension: User ID {$cmsUserID}, Blocked Auto login due to role configuration.");

            return;
          }
        }

        if (empty($setDefaults['cmsuser_allow_existing_user_login'])) {
          CRM_Core_Error::debug_log_message("CMS User Extension: User ID {$cmsUserID}, Existing user not allowed to auto login.");

          return;
        }
      }

      return $cmsUserID;
    }
  }
}

/**
 * @param $setDefaults
 * @param bool $isGroup
 */
function _cms_user_reset($setDefaults, $isGroup = TRUE) {
  $domainID = CRM_Core_Config::domainID();
  $activities = _cmsuser_activities();
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
    if (!$config->userSystem->is_drupal) {
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
          continue;
        }

        $resetParams = ['uf_id' => $uf_id];
        // call our custom api to reset user
        $api = civicrm_api3('Cmsuser', 'Reset', $resetParams);
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
            civicrm_api3('EntityTag', 'delete', [
              'entity_table' => 'civicrm_contact',
              'entity_id' => $contactID,
              'tag_id' => $setDefaults['cmsuser_tag_reset'],
            ]);
          }
        }

        catch (CiviCRM_API3_Exception $e) {

        }
      }

      // create activity
      $activityDetails = '';
      if (empty($api['is_error'])) {
        $activityStatus = $activities['activity_completed'];
        $activitySubject = "Password Reset email send to uid : ({$api['values']['uf_id']})";
      }
      else {
        $activityStatus = $activities['activity_failed'];
        $activitySubject = "Failed to send Password reset email to User uid : $uf_id";
        if (!empty($api['error_message'])) {
          $activityDetails = $api['error_message'];
        }
      }
      try {
        civicrm_api3('Activity', 'create', [
          'source_record_id' => $contactID,
          'target_contact_id' => $contactID,
          'activity_type_id' => $activities['activity_password'],
          'status_id' => $activityStatus,
          'subject' => $activitySubject,
          'check_permissions' => 0,
          'details' => $activityDetails,
        ]);
      }
      catch (CiviCRM_API3_Exception $exception) {

      }
    }
    // remove contacts from Group, so that on next iteration, same contact not get pulled
    // this block kept outside loop to avoid cache clear performance on every delete action. Passing all contacts in
    // one go.
    if ($isGroup and !empty($groupContactDeleted)) {
      CRM_Contact_BAO_GroupContact::removeContactsFromGroup($groupContactDeleted, $setDefaults['cmsuser_group_reset'], 'Deleted');
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
  foreach ($groupContactResult['values'] as $entity) {
    $groupContacts[] = $entity['contact_id'];
  }
  return $groupContacts;
}

/**
 * @param $data
 * @return array
 */
function help_parse_qs($data) {
  $data = preg_replace_callback('/(?:^|(?<=&))[^=[]+/', function ($match) {
    return bin2hex(urldecode($match[0]));
  }, $data);

  parse_str($data, $values);

  return array_combine(array_map('hex2bin', array_keys($values)), $values);
}
