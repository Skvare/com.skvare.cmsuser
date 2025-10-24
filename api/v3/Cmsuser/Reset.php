<?php
use CRM_Cmsuser_ExtensionUtil as E;

/**
 * Cmsuser.Reset API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_cmsuser_Reset_spec(&$spec) {
  $spec['uf_id']['api.required'] = 1;
}

/**
 * Cmsuser.Reset API
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
function civicrm_api3_cmsuser_Reset($params) {
  civicrm_api3_verify_mandatory($params, NULL, ['uf_id']);
  $config = CRM_Core_Config::singleton();
  if (CIVICRM_UF == 'Drupal') {
    require_once DRUPAL_ROOT . '/modules/user/user.pages.inc';
    // For Drupal 7.
    global $language;
    $account = user_load($params['uf_id']);
    // Mail one time login URL and instructions using current language.
    if (!empty($account)) {
      $mail = _user_mail_notify('password_reset', $account, $language);
    }
    else {
      return civicrm_api3_create_error('Failed to send reset email to CMS user account, user not exit', $params);
    }
  }
  elseif (CIVICRM_UF == 'Backdrop') {
    require_once BACKDROP_ROOT . '/core/modules/user/user.pages.inc';
    // For Backdrop.
    global $language;
    $account = user_load($params['uf_id']);
    // Mail one time login URL and instructions using current language.
    if (!empty($account)) {
      $mail = _user_mail_notify('password_reset', $account, $language);
      watchdog('user', 'Reset Password sent %name.', ['%name' => $params['uf_id']]);
    }
    else {
      return civicrm_api3_create_error('Failed to send reset email to CMS user account, user not exit', $params);
    }
  }
  elseif (CIVICRM_UF == 'Drupal8') {
    // For Drupal 8.
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $account = \Drupal\user\Entity\User::load($params['uf_id']);
    if (!empty($account)) {
      $mail = _user_mail_notify('password_reset', $account, $langcode);
    }
    else {
      return civicrm_api3_create_error('Failed to send reset email to CMS user account, user not exit', $params);
    }
  }
  elseif (CIVICRM_UF == 'WordPress') {
    // For WordPress.
    $account = CRM_Cmsuser_Utils::loadUser($params['uf_id']);
    if (!empty($account)) {
      $user_login = $account->user_login;
      // $user_email = $account->user_email;
      // Generate and send the password reset email.
      $reset = retrieve_password($user_login);
      if (!is_wp_error($reset)) {
        $mail = TRUE;
      }
      else {
        $mail = FALSE;
      }
    }
    else {
      return civicrm_api3_create_error('Failed to send reset email to CMS user account, user not exit', $params);
    }
  }
  if (!empty($mail)) {
    return civicrm_api3_create_success(['uf_id' => $params['uf_id']], $params);
  }

  return civicrm_api3_create_error('Failed to send reset email to CMS user account', $params);
}
