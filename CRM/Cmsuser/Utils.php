<?php

use CRM_Cmsuser_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Cmsuser_Utils {

  /**
   * Create CMS user using Profile.
   *
   * @param array $params
   * @param string $mail
   *   Email id for cms user.
   *
   * @return int
   *   contact id that has been created
   */
  public static function create(&$params, $mail) {
    $ufID = FALSE;
    if (CIVICRM_UF == 'Drupal8') {
      $ufID = self::create_d8($params, $mail);
    }
    elseif (CIVICRM_UF == 'Drupal') {
      $ufID = self::create_d7($params, $mail);
    }
    elseif (CIVICRM_UF == 'WordPress') {
      $ufID = self::create_wordpress($params, $mail);
    }

    //if contact doesn't already exist create UF Match
    if ($ufID !== FALSE && isset($params['contactID'])) {
      // create the UF Match record
      $ufmatch['uf_id'] = $ufID;
      $ufmatch['contact_id'] = $params['contactID'];
      $ufmatch['uf_name'] = $params[$mail];
      CRM_Core_BAO_UFMatch::create($ufmatch);
    }

    return $ufID;
  }

  /**
   * Function to cerate user for drupal 8
   *
   * @param $params
   * @param $mail
   *
   * @return mixed
   */
  public static function create_d8(&$params, $mail) {
    $user = \Drupal::currentUser();
    $user_register_conf = \Drupal::config('user.settings')->get('register');
    $verify_mail_conf = \Drupal::config('user.settings')->get('verify_mail');

    /** @var \Drupal\user\Entity\User $account */
    $account = \Drupal::entityTypeManager()->getStorage('user')->create();
    $account->setUsername($params['cms_name'])->setEmail($params[$mail]);

    $account->setPassword(FALSE);
    $account->enforceIsNew();
    $account->activate();
    /*
    // Allow user to set password only if they are an admin or if
    // the site settings don't require email verification.
    if (!$verify_mail_conf || $user->hasPermission('administer users')) {
      // @Todo: do we need to check that passwords match or assume this has already been done for us?
      $account->setPassword($params['cms_pass']);
    }
    // Only activate account if we're admin or if anonymous users don't require
    // approval to create accounts.
    if ($user_register_conf != 'visitors' && !$user->hasPermission('administer users')) {
      $account->block();
    }
    else {
      $account->activate();
    }
    */

    // PATCH START : Add Drupal user Custom field, mostly those are required.
    if (!empty($params['custom_fields'])) {
      foreach ($params['custom_fields'] as $fieldName => $fieldValue) {
        $account->set($fieldName, $fieldValue);
      }
    }
    // PATCH END
    // Validate the user object
    $violations = $account->validate();
    if (count($violations)) {
      return FALSE;
    }

    // Let the Drupal module know we're already in CiviCRM.
    $config = CRM_Core_Config::singleton();
    $config->inCiviCRM = TRUE;
    try {
      $account->save();
      $config->inCiviCRM = FALSE;
    }
    catch (\Drupal\Core\Entity\EntityStorageException $e) {
      $config->inCiviCRM = FALSE;

      return FALSE;
    }
    if (!empty($params['notify'])) {
      _user_mail_notify('register_no_approval_required', $account);
    }
    /*
    switch ($params['notify']) {
      case $user_register_conf == 'admin_only' || $user->isAuthenticated():
        _user_mail_notify('register_admin_created', $account);
        break;

      case $user_register_conf == 'visitors':
        _user_mail_notify('register_no_approval_required', $account);
        break;

      case 'visitors_admin_approval':
        _user_mail_notify('register_pending_approval', $account);
        break;
    }
    */

    return $account->id();
  }

  /**
   * Function to create user for Drupal 7
   * @param $params
   * @param $mail
   *
   * @return bool
   */
  public static function create_d7(&$params, $mail) {
    $form_state = form_state_defaults();

    $form_state['input'] = [
      'name' => $params['cms_name'],
      'mail' => $params[$mail],
      'op' => 'Create new account',
    ];
    $form_state['input']['pass'] = ['pass1' => $params['cms_pass'], 'pass2' => $params['cms_pass']];

    // PATCH START : Add Drupal user Custom field, mostly those are required.
    if (!empty($params['custom_fields'])) {
      foreach ($params['custom_fields'] as $fieldName => $fieldValue) {
        $form_state['input'][$fieldName] = [LANGUAGE_NONE => [['value' => $fieldValue]]];
      }
    }
    // PATCH END
    if (!empty($params['notify'])) {
      $form_state['input']['notify'] = $params['notify'];
    }

    $form_state['rebuild'] = FALSE;
    $form_state['programmed'] = TRUE;
    $form_state['complete form'] = FALSE;
    $form_state['method'] = 'post';
    $form_state['build_info']['args'] = [];
    /*
     * if we want to submit this form more than once in a process (e.g. create more than one user)
     * we must force it to validate each time for this form. Otherwise it will not validate
     * subsequent submissions and the manner in which the password is passed in will be invalid
     */
    $form_state['must_validate'] = TRUE;
    $config = CRM_Core_Config::singleton();

    // we also need to redirect b
    $config->inCiviCRM = TRUE;

    $form = drupal_retrieve_form('user_register_form', $form_state);
    $form_state['process_input'] = 1;
    $form_state['submitted'] = 1;
    $form['#array_parents'] = [];
    $form['#tree'] = FALSE;
    drupal_process_form('user_register_form', $form, $form_state);

    $config->inCiviCRM = FALSE;

    if (form_get_errors()) {
      return FALSE;
    }

    return $form_state['user']->uid;
  }

  /**
   * Function to create user for WordPress.
   * @param $params
   * @param $mail
   *
   * @return bool
   */
  public static function create_wordpress(&$params, $mail) {
    $user_data = [
      'ID' => '',
      'user_login' => $params['cms_name'],
      'user_email' => $params[$mail],
      'nickname' => $params['cms_name'],
      'role' => get_option('default_role'),
    ];

    // If there's a password add it, otherwise generate one.
    if (!empty($params['cms_pass'])) {
      $user_data['user_pass'] = $params['cms_pass'];
    }
    else {
      $user_data['user_pass'] = wp_generate_password(12, FALSE);;
    }

    // Assign WordPress User "name" field(s).
    if (isset($params['contactID'])) {
      $contactType = CRM_Contact_BAO_Contact::getContactType($params['contactID']);
      if ($contactType == 'Individual') {
        $user_data['first_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['contactID'], 'first_name'
        );
        $user_data['last_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['contactID'], 'last_name'
        );
      }
      if ($contactType == 'Organization') {
        $user_data['first_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['contactID'], 'organization_name'
        );
      }
      if ($contactType == 'Household') {
        $user_data['first_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['contactID'], 'household_name'
        );
      }
    }

    // Now go ahead and create a WordPress User.
    $uid = wp_insert_user($user_data);

    if (!empty($params['notify'])) {
      // Fire the new user action. Sends notification email by default.
      do_action('register_new_user', $uid);
    }

    return $uid;
  }


  /**
   * Function to auto login user.
   *
   * @param int $cmsUserID
   */
  public static function autoLogin($cmsUserID) {
    // if Operation is done by logged in user then do not log in.
    if (CRM_Utils_System::isUserLoggedIn()) {
      return;
    }
    if (CIVICRM_UF == 'Drupal8') {
      $account = \Drupal\user\Entity\User::load($cmsUserID);
      \user_login_finalize($account);
    }
    elseif (CIVICRM_UF == 'Drupal') {
      // if Operation is done by logged in user then do not log in.
      global $user;
      $user = user_load($cmsUserID);
      $formState['uid'] = $user->uid;
      user_login_finalize($formState);
    }
    elseif (CIVICRM_UF == 'WordPress') {
      $user = new WP_User($cmsUserID);
      if (!is_wp_error($user)) {
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
      }
    }
  }

}
