<?php
// $Id: ldap_servers.functions.inc,v 1.3.2.1 2011/02/08 06:01:00 johnbarclay Exp $

/**
 * @file
 * collection of functions that don't belong in server object
 */



/**
 * returns new user account if created, otherwise integer error message such
 * as LDAP_CREATE_ACCOUNT_ALREADY_EXISTS, LDAP_CREATE_ERROR
 *
 */
function ldap_create_drupal_account($name, $mail, $dn, $sid, $edit = array()) {

  $edit['name'] = $name;
  $edit['pass'] = user_password(20);
  $edit['mail'] = $mail;
  $edit['init'] = $mail;
  $edit['status'] = 1;

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
    user_set_authmaps($account, array('authname_ldap_authentication' => $name));
  }
  return $account;
}


/**
  * Modify an LDAP Entry
  */
  function ldap_user_modify($userdn, $attributes, $ldap_server) {
    $status = ldap_modify($ldap_server->connection, $userdn, $attributes);
    if (!$status) {
      watchdog(
        'ldap_servers',
        'Error: user_modify() failed to modify ldap entry w/ base DN "!dn" with values: !values',
        array('!dn' => $userdn, '!value' => var_export($attributes, TRUE)),
        WATCHDOG_ERROR
      );
    }

    return $status;
  }

  /**
   * Modify a password
   */
  function ldap_password_modify($userdn, $new_password, $ldap_server) {

    $new_password = "\"" . $new_password . "\"";
    $len = drupal_strlen($new_password);
    $new_pass = NULL;
    for ($i = 0; $i < $len; $i++) {
      $new_pass .= "{$new_password{$i}}\000";
    }

    $status = ldap_mod_replace($ldap_server->connection, $userdn, array('unicodePwd' => $new_pass));
    if (!$status) {
      watchdog(
        'ldap_servers',
        'Error: password_modify() failed to modify ldap password w/ base DN "!dn"',
        array('!dn' => $userdn),
        WATCHDOG_ERROR
      );
    }

    return $status;
  }

/**
 *
 *  this attempts to find bad dns, but should only be used as warningswe
 *  as the ldap spec allows for any old character to be escaped and ldap
 *  implementations may not follow the spec.
 *
 *  http://www.ietf.org/rfc/rfc2253.txt
 *
 */
function ldap_baddn($dn, $dn_name) {
  $result = array();
  $valid_attr_name = '[_a-zA-Z\d\s]';
  $valid_attr_values = '[_\-a-zA-Z\d\s]';
  $regex = '/^(' . $valid_attr_name . '*\=' . $valid_attr_values . '*[,]{1})*(' . $valid_attr_name . '*\=' . $valid_attr_values . '*){1}$/';
  $match = (preg_match($regex, $dn)) ? TRUE : FALSE;
  $result['boolean'] = $match;
  if (!$match) {
    $tokens = array('%dn' => htmlspecialchars($dn), '%dn_name' => $dn_name);
    $result['text'] = t('Possible invalid format for:<br/> <code><strong>%dn</strong><code>.  The format may be correct for
      your ldap, but please double check.', $tokens);
  }
  return $result;
}

/**
 *
 *  this attempts to find bad dns, but should only be used as warningswe
 *  as the ldap spec allows for any old character to be escaped and ldap
 *  implementations may not follow the spec.
 *
 *  http://www.ietf.org/rfc/rfc2253.txt
 *
 */

function ldap_badattr($attr, $attr_name) {
  $result = array();
  $valid_attr_name = '[_a-zA-Z\d\s]';
  $regex = '/^(' . $valid_attr_name . '){1,}$/';
  $match = (preg_match($regex, $attr)) ? TRUE : FALSE;
  $result['boolean'] = $match;
  if (!$match) {
    $tokens = array('%attr' => htmlspecialchars($attr), '%attr_name' => $attr_name);
    $result['text'] = t('Possible invalid format for %attr_name:<br/> <code><strong>%attr</strong><code>
      The format may be correct for your ldap, but please double check.', $tokens);
  }
  return $result;
}
