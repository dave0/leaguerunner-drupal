<?php

/**
 * @file
 * This class represents an ldap_profile module's configuration
 * It is extended by LdapProfileConfAdmin for configuration and other admin functions
 */

class LdapProfileConf {

  public $ldap_fields = array();
  public $mapping = array();
  public $inDatabase = FALSE;

  protected $saveable = array(
    'ldap_fields',
    'mapping',
  );
  
  function __construct() {
    $this->load();
  }


  function load() { 
    if ($saved = variable_get("ldap_profile_conf", FALSE)) {
      $this->inDatabase = TRUE;
      foreach ($this->saveable as $property) {
        if (isset($saved[$property])) {
          $this->{$property} = $saved[$property];
        }
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

}
