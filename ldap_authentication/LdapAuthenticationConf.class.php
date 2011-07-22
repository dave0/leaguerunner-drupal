<?php
// $Id: LdapAuthenticationConf.class.php,v 1.4.2.2 2011/02/08 20:05:41 johnbarclay Exp $

/**
 * @file
 * This class represents an ldap_authentication module's configuration
 * It is extended by LdapAuthenticationConfAdmin for configuration and other admin functions
 */

class LdapAuthenticationConf {

  // no need for LdapAuthenticationConf id as only one instance will exist per drupal install

  public $sids = array();  // server configuration ids being used for authentication
  public $servers = array(); // ldap server object
  public $inDatabase = FALSE;
  public $authenticationMode = LDAP_AUTHENTICATION_MODE_DEFAULT;
  public $ldapUserHelpLinkUrl;
  public $ldapUserHelpLinkText = LDAP_AUTHENTICATION_HELP_LINK_TEXT_DEFAULT;
  public $loginConflictResolve = LDAP_AUTHENTICATION_CONFLICT_RESOLVE_DEFAULT;
  public $acctCreation = LDAP_AUTHENTICATION_ACCT_CREATION_DEFAULT;
  public $emailOption = LDAP_AUTHENTICATION_EMAIL_FIELD_DEFAULT;
  public $emailUpdate = LDAP_AUTHENTICATION_EMAIL_UPDATE_ON_LDAP_CHANGE_DEFAULT;
  public $apiPrefs = array();
  public $createLDAPAccounts; // should an drupal account be created when an ldap user authenticates
  public $createLDAPAccountsAdminApproval; // create them, but as blocked accounts

  /**
   * Advanced options.   whitelist / blacklist options
   *
   * these are on the fuzzy line between authentication and authorization
   * and determine if a user is allowed to authenticate with ldap
   *
   */

  public $allowOnlyIfTextInDn = array(); // eg ou=education that must be met to allow ldap authentication
  public $excludeIfTextInDn = array();


  protected $saveable = array(
    'sids',
    'authenticationMode',
    'loginConflictResolve',
    'acctCreation',
    'ldapUserHelpLinkUrl',
    'ldapUserHelpLinkText',
    'emailOption',
    'emailUpdate',
    'allowOnlyIfTextInDn',
    'excludeIfTextInDn',
  );

  /** are any ldap servers that are enabled associated with ldap authentication **/
  public function enabled_servers() {
    return !(count(array_filter(array_values($this->sids))) == 0);
  }
  function __construct() {
    $this->load();
  }


  function load() {

    if ($saved = variable_get("ldap_authentication_conf", FALSE)) {
      $this->inDatabase = TRUE;
      foreach ($this->saveable as $property) {
        if (isset($saved[$property])) {
          $this->{$property} = $saved[$property];
        }
      }
      foreach ($this->sids as $sid) {
        $this->servers[$sid] = ldap_servers_get_servers($sid, 'enabled', TRUE);
      }
    }
    else {
      $this->inDatabase = FALSE;
    }

    $this->apiPrefs['requireHttps'] = variable_get('ldap_servers_require_ssl_for_credentails', 1);
    $this->apiPrefs['encryption'] = variable_get('ldap_servers_encryption', LDAP_SERVERS_ENC_TYPE_CLEARTEXT);

    // determine account creation configuration
    $user_register = variable_get('user_register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL);
    if ($this->acctCreation == LDAP_AUTHENTICATION_ACCT_CREATION_DEFAULT || $user_register == USER_REGISTER_VISITORS) {
      $this->createLDAPAccounts = TRUE;
      $this->createLDAPAccountsAdminApproval = FALSE;
    }
    elseif ($user_register == USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL) {
      $this->createLDAPAccounts = FALSE;
      $this->createLDAPAccountsAdminApproval = TRUE;
    }
    else {
      $this->createLDAPAccounts = FALSE;
      $this->createLDAPAccountsAdminApproval = FALSE;
    }

  }

  /**
   * Destructor Method
   */
  function __destruct() {


  }


 /**
   * decide if a username is excluded or not
   *
   * return boolean
   */
  public function allowUser($name, $ldap_user) {
    /**
     * do one of the exclude attribute pairs match
     */
    $exclude = FALSE;
    foreach ($this->excludeIfTextInDn as $test) {
      if (strpos(drupal_strtolower($ldap_user['dn']), drupal_strtolower($test)) !== FALSE) {
        return FALSE;//  if a match, return FALSE;
      }
    }

    /**
     * do one of the allow attribute pairs match
     */
    if (count($this->allowOnlyIfTextInDn)) {
      foreach ($this->allowOnlyIfTextInDn as $test) {
        if (strpos(drupal_strtolower($ldap_user['dn']), drupal_strtolower($test)) !== FALSE) {
          return TRUE;
        }
      }
      return FALSE;
    }

    /**
     * default to allowed
     */
    return TRUE;
  }


}
