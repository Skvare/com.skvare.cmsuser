<?php

/**
 *
 */
class CRM_Cmsuser_Form_Task_Delete extends CRM_Contact_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   *   Nothing.
   */
  public function preProcess(): void {
    parent::preProcess();
    if (!CRM_Core_Permission::check('delete contacts')) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to delete this contact user.'));
    }
  }

  /**
   * Build the form object.
   *
   * @return void
   *   Nothing.
   */
  public function buildQuickForm(): void {
    if (CIVICRM_UF == 'Drupal8'|| CIVICRM_UF == 'Drupal') {
      $action = [
        'user_cancel_delete' => 'delete user, delete content',
        'user_cancel_reassign' => 'delete user, reassign content to uid=0',
      ];
      $this->add('select', 'delete_option', ts('Select Action'), $action, FALSE,
        ['class' => 'crm-select2 huge']);
    }

    $this->addDefaultButtons(ts('Delete Users'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @return void
   *   Nothing.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function postProcess(): void {
    $method = '';
    $values = $this->exportValues();
    if (array_key_exists('delete_option', $values)) {
      $method = $values['delete_option'];
    }
    $deleted = $failed = $noUser = 0;
    foreach ($this->_contactIds as $cid) {
      if ($userID = CRM_Core_BAO_UFMatch::getUFId($cid)) {
        CRM_Core_Error::debug_var('$userID', $userID);
        if (CRM_Cmsuser_Utils::delete($userID, $method)) {
          $deleted++;
        }
        else {
          $failed++;
        }
      }
      else {
        $noUser++;
      }
    }

    if ($deleted) {
      $msg = ts('%count users deleted.', ['plural' => '%count users deleted.', 'count' => $deleted]);
      CRM_Core_Session::setStatus($msg, ts('Removed'), 'success');
    }

    if ($failed) {
      CRM_Core_Session::setStatus(ts('1 could not be deleted.', ['plural' => '%count could not be deleted.', 'count' => $failed]), ts('Error'), 'error');
    }
  }

}
