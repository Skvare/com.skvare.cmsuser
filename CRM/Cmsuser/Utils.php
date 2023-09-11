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
    elseif (CIVICRM_UF == 'Backdrop') {
      $ufID = self::create_backdrop($params, $mail);
    }
    elseif (CIVICRM_UF == 'WordPress') {
      $ufID = self::create_wordpress($params, $mail);
    }
    elseif (CIVICRM_UF == 'Joomla') {
      $params['cms_pass'] = rand();
      $ufID = self::create_joomla($params, $mail);
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

    // Check for preferred language
    if (array_key_exists('preferred_langcode', $params) && $params['preferred_langcode']) {
      $languageList = \Drupal::languageManager()->getLanguages();
      $listAvailable = [];
      foreach ($languageList as $k => $language) {
        $listAvailable[] = $language->getId();
      }
      // This is not a good approach: We just need the language string (ignoring the country) so we're going to split the locale selected.
      // We're getting from `en_US` the string `en`
      $langString = explode('_', $params['preferred_langcode']);
      if (is_array($langString) && $langString[0] && in_array($langString[0], $listAvailable)) {
        $account->set('preferred_langcode', $langString[0]);
      }
      else {
        CRM_Core_Error::debug_var('cmsuser extension preferred_langcode not available in CMS', $langString[0]);
      }
    }

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
      foreach ($violations as $violation) {
        CRM_Core_Error::debug_var('cmsuser extension validation',
          $violation->getMessage());
      }

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
    // Make status unblocked, otherwise auto login will not work.
    $form_state['input']['status'] = 1;
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
   * Function to create user for Backdrop
   * @param $params
   * @param $mail
   *
   * @return bool
   */
  public static function create_backdrop(&$params, $mail) {
    $form_state = form_state_defaults();

    $form_state['input'] = [
      'name' => $params['cms_name'],
      'mail' => $params[$mail],
      'op' => 'Create new account',
    ];
    $form_state['input']['pass'] = ['pass1' => $params['cms_pass'], 'pass2' => $params['cms_pass']];
    // Make status unblocked, otherwise auto login will not work.
    $form_state['input']['status'] = 1;

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

    $form = backdrop_retrieve_form('user_register_form', $form_state);
    $form_state['process_input'] = 1;
    $form_state['submitted'] = 1;
    $form['#array_parents'] = [];
    $form['#tree'] = FALSE;
    backdrop_process_form('user_register_form', $form, $form_state);

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
   * Function to create user for Joomla.
   *
   * @param $params
   * @param $mail
   * @return false|int|null
   */
  public static function create_joomla(&$params, $mail) {
    if (isset($params['name'])) {
      $fullName = trim($params['name']);
    }
    elseif (isset($params['contactID'])) {
      $fullName = trim(CRM_Contact_BAO_Contact::displayName($params['contactID']));
    }
    else {
      $fullName = trim($params['cms_name']);
    }
    $user = new JUser;
    $user_data = [
      "username" => $params['cms_name'],
      "name" => $fullName,
      "email" => $params[$mail],
      "block" => 0,
      "activated" => 1,
      "is_guest" => 0
    ];

    if (!$user->bind($user_data)) {
      return FALSE;
    }
    if (!$user->save()) {
      return FALSE;
    }

    return $user->id;
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
    elseif (CIVICRM_UF == 'Drupal' || CIVICRM_UF == 'Backdrop') {
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
    elseif (CIVICRM_UF == 'Joomla') {
      $user = new JUser($cmsUserID);
      $session = JFactory::getSession();
      $session->set('user', $user);
    }
  }

  public static function getJoomlaGroups() {
    jimport('joomla.user.helper');
    $groups = Joomla\CMS\Helper\UserGroupsHelper::getInstance()->getAll();
    $groups = json_decode(json_encode($groups), TRUE);
    $groupList = [];
    foreach ($groups as $key => $group) {
      $groupList[$key] = $group['title'];
    }
    return $groupList;
  }

  /**
   * Function to get user details.
   * @param $userId
   * @return \Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface|\Drupal\user\Entity\User|WP_User|null
   */
  public static function loadUser($userId) {
    if (CIVICRM_UF == 'Drupal8') {
      $account = \Drupal\user\Entity\User::load($userId);
    }
    elseif (CIVICRM_UF == 'Drupal' || CIVICRM_UF == 'Backdrop') {
      $account = user_load((int)$userId, TRUE);
    }
    elseif (CIVICRM_UF == 'WordPress') {
      $account = new WP_User($userId);
    }
    elseif (CIVICRM_UF == 'Joomla') {
      $account = JUser::getInstance((int)$userId);
    }

    return $account;
  }

  /**
   * Function to assign role to user.
   *
   * @param $account
   * @param $setDefaults
   * @return false|mixed
   */
  public static function addRoleToUser(&$account, $setDefaults) {
    if (CIVICRM_UF == 'Drupal8') {
      foreach ($setDefaults['cmsuser_cms_roles'] as $role) {
        $account->addRole($role);
      }
      $account->save();
    }
    elseif (CIVICRM_UF == 'Drupal') {
      $allRoles = user_roles(TRUE);
      $roles = [];
      // Skip adding the role to the user if they already have it.
      foreach ($setDefaults['cmsuser_cms_roles'] as $role) {
        if ($account !== FALSE && !isset($account->roles[$role])) {
          $roles = $account->roles + [$role => $allRoles[$role]];
        }
      }
      if (!empty($roles)) {
        user_save($account, ['roles' => $roles]);
      }
    }
    elseif (CIVICRM_UF == 'Backdrop') {
      // Skip adding the role to the user if they already have it.
      foreach ($setDefaults['cmsuser_cms_roles'] as $role) {
        if ($account !== FALSE && !in_array($role, $account->roles)) {
          $account->roles[] = $role;
        }
      }
      if (!empty($account->roles)) {
        $account->save();
      }
    }
    elseif (CIVICRM_UF == 'WordPress') {
      $account->set_role($setDefaults['cmsuser_cms_roles']);
    }
    elseif (CIVICRM_UF == 'Joomla') {
    }

    return $account;
  }

  /**
   * Function to check user have mentioned role
   *
   * @param $cmsUserID
   * @param $roles
   */
  public static function isRolePresentToUser($userId, $roles) {
    $account = self::loadUser($userId);
    $hasRole = FALSE;
    if (CIVICRM_UF == 'Drupal8') {
      $userRoles = $account->getRoles();
      foreach ($roles as $role) {
        if (in_array($role, $userRoles)) {
          $hasRole = TRUE;
          break;
        }
      }
    }
    elseif (CIVICRM_UF == 'Drupal') {
      foreach ($roles as $role) {
        if ($account !== FALSE && isset($account->roles[$role])) {
          $hasRole = TRUE;
          break;
        }
      }
    }
    elseif (CIVICRM_UF == 'Backdrop') {
      foreach ($roles as $role) {
        if ($account !== FALSE && in_array($role, $account->roles)) {
          $hasRole = TRUE;
          break;
        }
      }
    }
    elseif (CIVICRM_UF == 'WordPress') {
      foreach ($roles as $role) {
        if (in_array($role, (array)$account->roles)) {
          $hasRole = TRUE;
          break;
        }
      }
    }
    elseif (CIVICRM_UF == 'Joomla') {
      foreach ($roles as $role) {
        if ($account !== FALSE && isset($account->groups[$role])) {
          $hasRole = TRUE;
          break;
        }
      }
    }

    return $hasRole;
  }

}
