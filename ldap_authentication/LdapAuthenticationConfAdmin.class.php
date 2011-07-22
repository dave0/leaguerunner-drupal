<?php
// $Id: LdapAuthenticationConfAdmin.class.php,v 1.4.2.1 2011/02/08 06:01:00 johnbarclay Exp $

/**
 * @file
 * This classextends by LdapAuthenticationConf for configuration and other admin functions
 */

require_once('LdapAuthenticationConf.class.php');
class LdapAuthenticationConfAdmin extends LdapAuthenticationConf {

  protected function setTranslatableProperties() {

    $values['authenticationModeOptions']  = array(
      LDAP_AUTHENTICATION_MIXED => t('Mixed mode. Drupal authentication is tried first.  On failure, LDAP authentication is performed.'),
      LDAP_AUTHENTICATION_EXCLUSIVE => t('Only LDAP Authentication is allowed except for user 1.
        If selected, (1) reset password links will be replaced with links to ldap end user documentation below.
        (2) The reset password form will be left available at user/password for user 1; but no links to it
        will be provided to anonymous users.
        (3) Password fields in user profile form will be removed except for user 1.'),
      );

    $values['authenticationServersDescription'] = t('Check all LDAP server configurations to use in authentication.
     Each will be tested for authentication until successful or
     until each is exhausted.  In most cases only one server configuration is selected.');

    $values['ldapUserHelpLinkUrlDescription'] = t('URL to LDAP user help/documentation for users resetting
     passwords etc. Should be of form http://domain.com/. Could be the institutions ldap password support page
     or a page within this drupal site that is available to anonymous users.');

    $values['ldapUserHelpLinkTextDescription']  = t('Text for above link e.g. Account Help or Campus Password Help Page');

    /**
    * 3. Drupal Account Provisioning and Syncing
    */
    $values['loginConflictResolveDescription'] = t('What should be done if a local Drupal or other external
      authentication account already exists with the same login name.');
    $values['loginConflictOptions'] = array(
      LDAP_AUTHENTICATION_CONFLICT_LOG => t('Disallow login and log the conflict'),
      LDAP_AUTHENTICATION_CONFLICT_RESOLVE => t('Associate local account with the LDAP entry.  This option
      is useful for creating accounts and assigning roles before an ldap user authenticates.'),
      );


    $values['acctCreationOptions'] = array(
      LDAP_AUTHENTICATION_ACCT_CREATION_LDAP_BEHAVIOR => t('Create accounts automatically for ldap authenticated users.
        Account creation settings at /admin/config/people/accounts/settings will only affect non-ldap authenticated accounts.'),
      LDAP_AUTHENTICATION_ACCT_CREATION_USER_SETTINGS_FOR_LDAP => t('Use account creation policy
         at /admin/config/people/accounts/settings under for both Drupal and LDAP Authenticated users.
         "Visitors" option automatically creates and account when they successfully LDAP authenticate.
         "Admin" and "Admin with approval" do not allow user to authenticate until the account is approved.'),
      );


    /**
    * 4. Email
    */

  $values['emailUpdateOptions'] = array(
    LDAP_AUTHENTICATION_EMAIL_UPDATE_ON_LDAP_CHANGE_ENABLE_NOTIFY => t('Update stored email if LDAP email differs at login and notify user.'),
    LDAP_AUTHENTICATION_EMAIL_UPDATE_ON_LDAP_CHANGE_ENABLE => t('Update stored email if LDAP email differs at login but don\'t notify user.'),
    LDAP_AUTHENTICATION_EMAIL_UPDATE_ON_LDAP_CHANGE_DISABLE => t('Don\'t update stored email if LDAP email differs at login.'),
    );

    foreach ($values as $property => $default_value) {
      $this->$property = $default_value;
    }
  }

  /**
   * 1.  logon options
   */
  public $authenticationModeDefault = LDAP_AUTHENTICATION_MIXED;
  public $authenticationModeOptions;

  protected $authenticationServersDescription;
  protected $authenticationServersOptions = array();
  protected $ldapUserHelpLinkUrlDescription;
  protected $ldapUserHelpLinkTextDescription;

   /**
   * 3. Drupal Account Provisioning and Syncing
   */
  public $loginConflictResolveDescription;
  public $loginConflictResolveDefault = LDAP_AUTHENTICATION_CONFLICT_LOG; // LDAP_CONFLICT_RESOLVE;
  public $loginConflictOptions;

  public $acctCreationDescription = '';
  public $acctCreationDefault = LDAP_AUTHENTICATION_ACCT_CREATION_DEFAULT;
  public $acctCreationOptions;


   /**
   * 4. Email
   */

  public $emailUpdateDefault = LDAP_AUTHENTICATION_EMAIL_UPDATE_ON_LDAP_CHANGE_ENABLE_NOTIFY;
  public $emailUpdateOptions;

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
    variable_set('ldap_authentication_conf', $save);
  }

  static public function uninstall() {
    variable_del('ldap_authentication_conf');
  }

  public function __construct() {
    parent::__construct();
    $this->setTranslatableProperties();
    if ($servers = ldap_servers_get_servers(NULL, 'enabled')) {
      foreach ($servers as $sid => $ldap_server) {
        $enabled = ($ldap_server->status) ? 'Enabled' : 'Disabled';
        $this->authenticationServersOptions[$sid] = $ldap_server->name . ' (' . $ldap_server->address . ') Status: ' . $enabled;
      }
    }
  }


  public function drupalForm() {

    if (count($this->authenticationServersOptions) == 0) {
      $message = ldap_servers_no_enabled_servers_msg('configure LDAP Authentication');
      $form['intro'] = array(
        '#type' => 'item',
        '#markup' => t('<h1>LDAP Authentication Settings</h1>') . $message,
      );
      return $form;
    }

    $tokens = array();  // not sure what the tokens would be for this form?

    $form['intro'] = array(
        '#type' => 'item',
        '#markup' => t('<h1>LDAP Authentication Settings</h1>'),
    );

    $form['logon'] = array(
      '#type' => 'fieldset',
      '#title' => t('Logon Options'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['logon']['authenticationMode'] = array(
      '#type' => 'radios',
      '#title' => t('Allowable Authentications'),
      '#required' => 1,
      '#default_value' => $this->authenticationMode,
      '#options' => $this->authenticationModeOptions,
    );


    $form['logon']['authenticationServers'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Authentication LDAP Server Configurations'),
      '#required' => FALSE,
      '#default_value' => $this->sids,
      '#options' => $this->authenticationServersOptions,
      '#description' => $this->authenticationServersDescription
    );

    $form['logon']['ldapUserHelpLinkUrl'] = array(
      '#type' => 'textfield',
      '#title' => t('LDAP Account User Help URL'),
      '#required' => 0,
      '#default_value' => $this->ldapUserHelpLinkUrl,
      '#description' => $this->ldapUserHelpLinkUrlDescription,
    );


    $form['logon']['ldapUserHelpLinkText'] = array(
      '#type' => 'textfield',
      '#title' => t('LDAP Account User Help Link Text'),
      '#required' => 0,
      '#default_value' => $this->ldapUserHelpLinkText,
      '#description' => $this->ldapUserHelpLinkTextDescription,
    );

    $form['restrictions'] = array(
      '#type' => 'fieldset',
      '#title' => t('LDAP User "Whitelists" and Restrictions'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['drupal_accounts'] = array(
      '#type' => 'fieldset',
      '#title' => t('Drupal User Account Creation'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['drupal_accounts']['loginConflictResolve'] = array(
      '#type' => 'radios',
      '#title' => t('Existing Drupal User Account Conflict'),
      '#required' => 1,
      '#default_value' => $this->loginConflictResolve,
      '#options' => $this->loginConflictOptions,
      '#description' => t( $this->loginConflictResolveDescription),
    );


    $form['drupal_accounts']['acctCreation'] = array(
      '#type' => 'radios',
      '#title' => t('Account Creation for LDAP Authenticated Users'),
      '#required' => 1,
      '#default_value' => $this->acctCreation,
      '#options' => $this->acctCreationOptions,
      '#description' => t($this->acctCreationDescription),
    );

    $form['email'] = array(
      '#type' => 'fieldset',
      '#title' => t('Email'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['email']['emailUpdate'] = array(
      '#type' => 'radios',
      '#title' => t('Email Update'),
      '#required' => 1,
      '#default_value' => $this->emailUpdate,
      '#options' => $this->emailUpdateOptions,
      );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

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
    $this->authenticationMode = ($values['authenticationMode']) ? (int)$values['authenticationMode'] : NULL;
    $this->sids = $values['authenticationServers'];
    $this->loginConflictResolve  = ($values['loginConflictResolve']) ? (int)$values['loginConflictResolve'] : NULL;
    $this->acctCreation  = ($values['acctCreation']) ? (int)$values['acctCreation'] : NULL;
    $this->ldapUserHelpLinkUrl = ($values['ldapUserHelpLinkUrl']) ? (string)$values['ldapUserHelpLinkUrl'] : NULL;
    $this->ldapUserHelpLinkText = ($values['ldapUserHelpLinkText']) ? (string)$values['ldapUserHelpLinkText'] : NULL;
    $this->emailUpdate  = ($values['emailUpdate']) ? (int)$values['emailUpdate'] : NULL;

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

  protected function arrayToLines($array) {
        $lines = "";
        if (is_array($array)) {
          $lines = join("\n", $array);
        }
        elseif (is_array(@unserialize($array))) {
          $lines = join("\n", unserialize($array));
        }
        return $lines;
      }

  protected function linesToArray($lines) {
    $lines = trim($lines);

    if ($lines) {
      $array = preg_split('/[\n\r]+/', $lines);
      foreach ($array as $i => $value) {
        $array[$i] = trim($value);
      }
    }
    else {
      $array = array();
    }
    return $array;
  }

}
