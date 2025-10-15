<?php

require_once 'cmsuser.civix.php';
// phpcs:disable
use CRM_Cmsuser_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function cmsuser_civicrm_config(&$config) {
  _cmsuser_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function cmsuser_civicrm_install() {
  _cmsuser_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function cmsuser_civicrm_enable() {
  _cmsuser_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
function cmsuser_civicrm_navigationMenu(&$menu) {
  _cmsuser_civix_insert_navigation_menu($menu, 'Administer/System Settings', [
    'label' => E::ts('CMS User Setting'),
    'name' => 'cms_user_setting',
    'url' => CRM_Utils_System::url('civicrm/admin/cmsuser', 'reset=1', TRUE),
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);
  _cmsuser_civix_navigationMenu($menu);
}

function _cmsuser_activities() {
  static $activities;

  if (!$activities) {
    $activities = [
      'activity_creation' => civicrm_api3('OptionValue', 'getvalue', [
        'option_group_id' => 'activity_type',
        'name' => 'User Account Creation',
        'return' => 'value',
      ]),
      'activity_password' => civicrm_api3('OptionValue', 'getvalue', [
        'option_group_id' => 'activity_type',
        'name' => 'User Account Password Reset',
        'return' => 'value',
      ]),
      'activity_failed' => civicrm_api3('OptionValue', 'getvalue', [
        'option_group_id' => 'activity_status',
        'name' => 'Failed',
        'return' => 'value',
      ]),
      'activity_completed' => civicrm_api3('OptionValue', 'getvalue', [
        'option_group_id' => 'activity_status',
        'name' => 'Completed',
        'return' => 'value',
      ]),
    ];
  }

  return $activities;
}

/**
 * Implementation of hook_civicrm_post
 */
function cmsuser_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  if ($op == 'create' && ($objectName == 'EntityTag' || $objectName == 'GroupContact')) {

    // when contact is added to Tag or Group, it should  create user for it. then remove same contact from the group.
    require_once 'api/v3/Job/Cmsuser.php';
    $domainID = CRM_Core_Config::domainID();
    $settings = Civi::settings($domainID);
    $setDefaults = [];
    $elementNames = ['cmsuser_pattern', 'cmsuser_notify', 'cmsuser_preferred_language', 'cmsuser_group_create', 'cmsuser_group_history', 'cmsuser_group_reset',
      'cmsuser_tag_create', 'cmsuser_tag_history', 'cmsuser_tag_reset', 'cmsuser_create_immediately', 'cmsuser_cms_roles',
      'cmsuser_user_fields', 'cmsuser_login_immediately', 'cmsuser_allow_existing_user_login', 'cmsuser_block_roles_autologin'];

    // load setting for cms extension
    foreach ($elementNames as $elementName) {
      $setDefaults[$elementName] = $settings->get($elementName);
    }

    // if its not configured for create new user immediately then it will be processed using scheduled job.
    if (!$setDefaults['cmsuser_create_immediately']) {
      return;
    }
    if ($objectName == 'EntityTag' && $setDefaults['cmsuser_tag_create'] == $objectId) {
      $cmsUserID = _cms_user_create($setDefaults, FALSE, $objectRef['0'], TRUE);
    }
    elseif ($objectName == 'GroupContact') {
      if (is_object($objectRef) && is_a($objectRef, 'CRM_Contact_BAO_GroupContact')
        && $setDefaults['cmsuser_group_create'] == $objectRef->group_id
      ) {
        $cmsUserID = _cms_user_create($setDefaults, TRUE, [$objectRef->contact_id], TRUE);
      }
      else if (is_array($objectRef) && $setDefaults['cmsuser_group_create'] == $objectId) {
        $cmsUserID = _cms_user_create($setDefaults, TRUE, $objectRef, TRUE);
      }
    }

    if (!empty($setDefaults['cmsuser_login_immediately']) &&
      isset($cmsUserID) && !empty($cmsUserID)) {
      CRM_Cmsuser_Utils::autoLogin($cmsUserID);
    }
  }
}
