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
  public $emailUpdate = LDAP_AUTHENTICATION_EMAIL_UPDATE_ON_LDAP_CHANGE_DEFAULT;

  protected $saveable = array(
    'sids',
    'authenticationMode',
    'loginConflictResolve',
    'ldapUserHelpLinkUrl',
    'ldapUserHelpLinkText',
    'emailUpdate',
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
     * default to allowed
     */
    return TRUE;
  }


}
