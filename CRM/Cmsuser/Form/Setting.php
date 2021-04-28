<?php

use CRM_Cmsuser_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Cmsuser_Form_Setting extends CRM_Core_Form {
  public function buildQuickForm() {

    // add form elements

    $this->add('text', 'cmsuser_pattern', 'Username pattern', ['size' => 60], TRUE);
    $this->addElement('checkbox', 'cmsuser_notify', ts('Notify User?'));
    $this->addElement('checkbox', 'cmsuser_create_immediately', ts('Create New User Immediately?'));
    if (CIVICRM_UF == 'Drupal8') {
      $user_role_names = user_role_names();
      $this->add('select', 'cmsuser_cms_roles', ts('Assign Role to Users'),
        $user_role_names, FALSE, ['class' => 'crm-select2 huge', 'multiple' => 1]);
    }
    $groups = ['' => '-- select --'] + CRM_Core_PseudoConstant::nestedGroup();
    $tags = ['' => '-- select --'] + CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', ['onlyActive' => FALSE]);

    $this->add('select', 'cmsuser_group_create', ts('Create CMS User for Group contact'), $groups);
    $this->add('select', 'cmsuser_group_history', ts('Assign New CMS user to Group'), $groups);
    $this->add('select', 'cmsuser_group_reset', ts('Reset CMS Password from Group Contact'), $groups);

    $this->add('select', 'cmsuser_tag_create', ts('Create CMS User for Tagged contact'), $tags);
    $this->add('select', 'cmsuser_tag_history', ts('Assign New CMS user to Tag'), $tags);
    $this->add('select', 'cmsuser_tag_reset', ts('Reset CMS Password from Tagged Contact'), $tags);


    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ],
    ]);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());

    // use settings as defined in default domain
    $domainID = CRM_Core_Config::domainID();
    $settings = Civi::settings($domainID);
    $setDefaults = [];
    foreach ($this->getRenderableElementNames() as $elementName) {
      $setDefaults[$elementName] = $settings->get($elementName);
    }
    $this->setDefaults($setDefaults);

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();

    // use settings as defined in default domain
    $domainID = CRM_Core_Config::domainID();
    $settings = Civi::settings($domainID);

    foreach ($values as $k => $v) {
      if (strpos($k, 'cmsuser_') === 0) {
        $settings->set($k, $v);
      }
    }
    CRM_Core_Session::setStatus(E::ts('Setting updated successfully'));
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = [];
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }

    return $elementNames;
  }

}
