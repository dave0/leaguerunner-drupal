<?php

/**
 * @file
 * This classextends by LdapProvisionConf for configuration and other admin functions
 */

require_once('LdapProvisionConf.class.php');
class LdapProvisionConfAdmin extends LdapProvisionConf {

  // no need for LdapAuthenticationConf id as only one instance will exist per drupal install
  public $errorMsg = NULL;
  public $hasError = FALSE;
  public $errorName = NULL;


  public function clearError() {
    $this->hasError = FALSE;
    $this->errorMsg = NULL;
    $this->errorName = NULL;
  }

  
  public function save() {
    foreach ($this->saveable as $property) {
      $save[$property] = $this->{$property};
    }
    variable_set('ldap_provision_conf', $save);
  }

  static public function uninstall() {
    variable_del('ldap_provision_conf');
  }

  public function __construct() {
    parent::__construct();
    if ($servers = ldap_servers_get_servers(NULL, 'enabled')) {
      foreach ($servers as $sid => $ldap_server) {
        $enabled = ($ldap_server->status) ? 'Enabled' : 'Disabled';
        $this->authenticationServersOptions[$sid] = $ldap_server->name . ' (' . $ldap_server->address . ') Status: ' . $enabled;
      }
    }
  }


  public function drupalForm($accounts = array()) {
    if (count($this->authenticationServersOptions) == 0) {
      $message = ldap_servers_no_enabled_servers_msg('configure LDAP Authentication');
      $form['intro'] = array(
        '#type' => 'item',
        '#markup' => t('<h1>LDAP Provision Settings</h1>') . $message,
      );
      return $form;
    }
    // grabs field information for a user account  
    $profile_fields = field_info_instances('user','user');
    $mapped_fields = ldap_profile_get_mapping();
    $field_options = array();
    foreach($mapped_fields as $key => $field) {
      if($key == 'username') {
        $field_options[$key] = 'User Name';
      } elseif($key == 'mail') {
        $field_options[$key] = 'Email';
      } else {
        $field_options[$key] = $profile_fields[$key]['label'];
      }
    }

    $rule_options = array(
      t('All People'),
      t('People who match one attribute'),
      t('Custom LDAP rule <em>(requires LDAP query formatting)</em>'),
    );

    $form['intro'] = array(
        '#type' => 'item',
        '#markup' => t('<h1>LDAP Provision Settings</h1>'),
    );

    $form['provisionRule'] = array(
      '#type' => 'radios',
      '#title' => 'Provisioning Rule',
      '#default_value' => $this->provisionRule,
      '#options' => $rule_options,
    );

    $form['provisionRuleAttrSelect'] = array(
      '#type' => 'select',
      '#title' => 'Attribute to Match',
      '#default_value' => $this->provisionRuleAttrSelect,
      '#options' => $field_options,
      '#states' => array(
        'visible' => array(
          'input[name="provisionRule"]' => array('value' => '1'),
        ),
      ),
    );

    $form['provisionRuleAttrText'] = array(
      '#type' => 'textfield',
      '#title' => 'Equal to',
      '#default_value' => $this->provisionRuleAttrText,
      '#states' => array(
        'visible' => array(
          'input[name="provisionRule"]' => array('value' => '1'),
        ),
      ),
    );

    $form['provisionRuleCustText'] = array(
      '#type' => 'textfield',
      '#title' => 'Custom Rule',
      '#default_value' => $this->provisionRuleCustText,
      '#states' => array(
        'visible' => array(
          'input[name="provisionRule"]' => array('value' => '2'),
        ),
      ),
    );

    $form['provisionCron'] = array(
      '#type' => 'radios',
      '#title' => 'Cron Setting',
      '#collapsible' => true,
      '#collapsed' => true,
      '#options' => array(
        'Once A Day (run at 3:00 am)',
        'Every 12 Hours',
        'Every 6 Hours',
        'Every 2 Hours',
        'Every Hour',
        'Every Cron',
      ),
      '#default_value' => $this->provisionCron,
      '#description' => t('This will determine how often Ldap is polled to provision accounts. Please choose wisely as this could be resource intensive on large user bases. Also, this assumes cron is setup to run at least as frequently as selected.'),
    );


    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Update',
    );

    $form['test'] = array(
      '#type' => 'submit',
      '#value' => 'Test',
      '#submit' => array('ldap_provision_test_submit'),
    );

    if(!empty($accounts)) {
      $form['test_table'] = array(
        '#type' => 'item',
        '#markup' => theme('ldap_provision_admin_test_table', array('auth_conf' => $this, 'accounts' => $accounts)),
        '#weight' => 10,
      );
    }

    return $form;
  }

  /**
   * validate form, not object
   */
  public function drupalFormValidate($values)  {

    $this->populateFromDrupalForm($values);

    $errors = $this->validate();

    return $errors;
  }

  /**
   * validate object, not form
   */
  public function validate() {
    $errors = array();

    return $errors;
  }

  protected function populateFromDrupalForm($values) {
    $this->provisionRule = ($values['provisionRule']) ? (int)$values['provisionRule'] : NULL;
    $this->provisionRuleAttrSelect = ($values['provisionRuleAttrSelect']) ? (string)$values['provisionRuleAttrSelect'] : NULL;
    $this->provisionRuleAttrText = ($values['provisionRuleAttrText']) ? (string)$values['provisionRuleAttrText'] : NULL;
    $this->provisionRuleCustText = ($values['provisionRuleCustText']) ? (string)$values['provisionRuleCustText'] : NULL;
    $this->provisionCron = ($values['provisionCron']) ? (int)$values['provisionCron'] : NULL;
  }

  public function drupalFormSubmit($values) {

    $this->populateFromDrupalForm($values);
    try {
        $save_result = $this->save();
    }
    catch (Exception $e) {
      $this->errorName = 'Save Error';
      $this->errorMsg = t('Failed to save object.  Your form data was not saved.');
      $this->hasError = TRUE;
    }

  }

}
