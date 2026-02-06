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

  // FIX: Convert date fields to YmdHis format before passing to duplicate check
  // CRM_Dedupe_Finder::formatParams expects dates in YmdHis format (e.g., "19690724000000")
  // but Contact.getsingle returns them in various formats (e.g., "1969-07-24" or display format)
  // Get custom date field names (cached statically for performance)
  static $customDateFields = NULL;
  if ($customDateFields === NULL) {
    $customDateFields = [];
    try {
      $customFields = civicrm_api3('CustomField', 'get', [
        'data_type' => 'Date',
        'return' => ['id'],
        'options' => ['limit' => 0],
      ]);
      foreach ($customFields['values'] as $field) {
        $customDateFields[] = 'custom_' . $field['id'];
      }
    } catch (CRM_Core_Exception $e) {
      // If we can't get custom fields, just continue without them
    }
  }

  // Convert both core date fields and custom date fields to YmdHis format
  $dateFields = array_merge(['birth_date', 'deceased_date'], $customDateFields);
  foreach ($dateFields as $fieldName) {
    if (!empty($result[$fieldName]) && is_string($result[$fieldName])) {
      $timestamp = strtotime($result[$fieldName]);
      if ($timestamp !== FALSE) {
        $result[$fieldName] = date('YmdHis', $timestamp);
      }
    }
  }

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
