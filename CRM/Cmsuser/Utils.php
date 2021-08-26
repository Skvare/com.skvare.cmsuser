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
    $config = CRM_Core_Config::singleton();
    $ufID = FALSE;
    if (CIVICRM_UF == 'Drupal8') {
      $ufID = self::create_d8($params, $mail);
    }
    elseif (CIVICRM_UF == 'Drupal') {
      $ufID = self::create_d7($params, $mail);
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
   * @return mixed
   */
  public static function create_d8(&$params, $mail) {
    $user = \Drupal::currentUser();
    $user_register_conf = \Drupal::config('user.settings')->get('register');
    $verify_mail_conf = \Drupal::config('user.settings')->get('verify_mail');

    /** @var \Drupal\user\Entity\User $account */
    $account = \Drupal::entityTypeManager()->getStorage('user')->create();
    $account->setUsername($params['cms_name'])->setEmail($params[$mail]);

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

    switch (TRUE) {
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

    // If this is a user creating their own account, login them in!
    if (!$verify_mail_conf && $account->isActive() && $user->isAnonymous()) {
      \user_login_finalize($account);
    }

    return $account->id();
  }

  /**
   * Function to create user for Drupal 7
   * @param $params
   * @param $mail
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

}