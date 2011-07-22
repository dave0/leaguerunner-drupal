<?php

/**
 * @file
 * This class represents an ldap_provision module's configuration
 * It is extended by LdapProvisionConfAdmin for configuration and other admin functions
 */

class LdapProvisionConf {

  public $provisionRule = 0;
  public $provisionRuleAttrSelect = "";
  public $provisionRuleAttrText = "";
  public $provisionRuleCustText = "";
  public $provisionCron = 0;

  protected $provisionCronLast = 0;
  protected $saveable = array(
    'provisionRule',
    'provisionRuleAttrSelect',
    'provisionRuleAttrText',
    'provisionRuleCustText',
    'provisionCron',
    'provisionCronLast',
  );

  function __construct() {
    $this->load();
  }


  function load() {
    if ($saved = variable_get("ldap_provision_conf", FALSE)) {
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

  /**
   * Provides last time that the provision cron job was executed
   */
  function get_last_cron() {
    return $this->provisionCronLast;
  }

  /**
   * Saves current time as last time provision cron job was executed
   */
  function update_cron() {
    $this->provisionCronLast = time();
  }

  /**
   * Returns the ldap filter to search on when provisioning accoutns
   */
  function get_filter($ldap_server = NULL) {
    $filter = '';
    (!empty($ldap_server)) ? $user_attr = $ldap_server->user_attr : $user_attr = 'uid';
    // filter depends on the current rule
    switch($this->provisionRule){
       // case 0 - all people
       case 0: $filter = $user_attr ."=*";
               break;
       case 1: $field = ldap_profile_get_mapping($this->provisionRuleAttrSelect);
               $field = $field[0];
               if($field == "") $error = "No Ldap Mapping for Selected Field.";
               else {
                 $filter = $field ."=". $this->provisionRuleAttrText;
               }
               break;
       case 2: $filter = $this->provisionRuleCustText;
               break;
       default:  $filter = $user_attr ."=*";
    }
    return $filter;
  }

  /**
   * Performs a ldap search based on current configuration settings
   */
  function search() {
    $accounts = array();
    $servers = ldap_servers_get_servers('','enabled');
    foreach ($servers as $sid => $ldap_server) {
      $result = $ldap_server->connect();
      if ($result != LDAP_SUCCESS) {
        // failed
        continue; // next server
      }
      $result = $ldap_server->bind();
      if ($result != LDAP_SUCCESS) {
        // failed
        continue; // next server
      }

      $attributes = ldap_profile_get_ldap_fields();
      // need to add in dn to allow for ldap authenticaiton
      $attributes[] = 'dn';
      $filter = $this->get_filter($ldap_server);
      $basedn = ''; // need to modify this to get the base dn from the server

      // searches each basedn for this server configuration
      foreach($ldap_server->basedn as $index => $base) {
        $accounts[$sid][$index] = $ldap_server->search($base, $filter, $attributes);
      }
    }
    return $accounts;
  }

  /**
   * Creates a drupal account based off of a ldap search result.
   * @params - $account - This is a ldap account returned based off of a ldap search.
   * @params - $sid - This is the ldap server the account information was pulled from.
   */
  function create_drupal_account($account, $sid) {

    $attributes = ldap_profile_get_mapping();
    $username = $attributes['username'];
    $mail = $attributes['mail'];

    $name = $account[$username][0];
    // account must have an email associated with it.
    if(!empty($account[$mail])) {
      $dn = $account['dn'];
      $edit = array(
        'name' => $name,
        'pass' => user_password(20),
        'init' => $account[$mail][0],
        'status' => 1,
      );
      foreach($attributes as $drupal => $ldap) {
        if(!empty($account[$ldap])) {
          // translates the ldap value into a value that drupal reconizes.
          $value = ldap_profile_translate($ldap, $account[$ldap][0]);
          $edit[$drupal] = array('und' => array(array('value' => $value)));
        }
      }
      $edit['mail'] = $account[$mail][0];

      // save 'init' data to know the origin of the ldap authentication provisioned account
      $edit['data']['ldap_authentication']['init'] = array(
        'sid'  => $sid,
        'dn'   => $dn,
        'mail' => $mail,
      );

      if (!$account = user_save( NULL, $edit)) {
        drupal_set_message(t('User account creation failed because of system problems.'), 'error');
        return FALSE;
      }
      else {
        drupal_set_message(t('User account created.'));
        user_set_authmaps($account, array('authname_ldap_authentication' => $name));
        return TRUE;
      }
    } else {
      // $account had no email so return false for not creating account.
      return FALSE;
    }

  }
  /**
   * Update a drupal account based off of a ldap search result.
   * @params - $account - This is a ldap account returned based off of a ldap search.
   * @params - $user - This is this drupal account of the same user
   */
  function update_drupal_account($account,$user) {
    $attributes = ldap_profile_get_mapping();
    $mail = $user->mail;
    $name = $user->name;
    $edit = array();
    foreach($attributes as $drupal => $ldap) {
      if(!empty($account[$ldap])) {
        $value = ldap_profile_translate($ldap, $account[$ldap][0]);
        $edit[$drupal] = array('und' => array(array('value' => $value)));
      }
    }
    $edit['mail'] = $account[$attributes['mail']][0];

    $updated = array_intersect_key($edit, (array) $user);
    if (!$account = user_save($user, $updated)) {
      drupal_set_message(t('User account creation failed because of system problems.'), 'error');
      return FALSE;
    }
    else {
      // drupal_set_message(t('User account updated.'));
      return TRUE;
    }
  }
}
