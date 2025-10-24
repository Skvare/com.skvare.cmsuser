<?php
use CRM_Cmsuser_ExtensionUtil as E;

/**
 * Cmsuser.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_cmsuser_Create_spec(&$spec) {
  $params['cms_name'] = [
    'api.required' => 1,
    'title' => 'CMS Username',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['email'] = [
    'api.required' => 1,
    'title' => 'Email Address',
    'type' => CRM_Utils_Type::T_EMAIL,
  ];
  $params['cms_pass'] = [
    'title' => 'CMS Password',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['notify'] = [
    'title' => 'Notify User',
    'description' => 'Whether an email should be sent to the user to notify them of account creation.',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $params['contactID'] = [
    'title' => 'Contact ID',
    'description' => 'CiviCRM contact ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
}

/**
 * Cmsuser.Create API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws CRM_Core_Exception
 */
function civicrm_api3_cmsuser_Create($params) {
  civicrm_api3_verify_mandatory($params, NULL, ['cms_name', 'email']);

  if (!empty($params['contactID'])) {
    try {
      $user = CRM_Core_Config::singleton()->userSystem->getUser($params['contactID']);
      $user['contactID'] = $params['contactID'];

      return civicrm_api3_create_error('CMS user account already exists for this contact.', $user);
    }
    catch (CRM_Core_Exception $e) {
      // user account doesn't exist, just fall through to rest of code
    }
  }

  if (empty($params['cms_pass'])) {
    $params['cms_pass'] = md5(print_r($_SERVER, TRUE));
  }
  $params['notify'] = empty($params['notify']) ? 0 : 1;

  // changes for handling dedupe match
  $result = civicrm_api3('Contact', 'getsingle', [
    'id' => $params['contactID'],
  ]);
  if (!empty($_POST)) {
    $_POST = array_merge($_POST, $result);
  }
  else {
    $_POST = $result;
  }

  if ($uf_id = CRM_Cmsuser_Utils::create($params, 'email')) {
    return civicrm_api3_create_success(['uf_id' => $uf_id], $params);
  }

  return civicrm_api3_create_error('Failed to create CMS user account', $params);
}
