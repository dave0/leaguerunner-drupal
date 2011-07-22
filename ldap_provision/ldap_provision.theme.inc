<?php


/**
 * @file
 * theming functions for ldap_provision module
 *
 */


/**
 * Returns HTML for ldap_provision admin settings form.
 * @param $variables
 *   An associative array containing:
 *   - accounts: array with ldap provisioning search results
 *
 * @ingroup themeable
 */
function theme_ldap_provision_admin_test_table($variables) {
  $max_rows = 10;
  extract($variables);
  $accounts = $variables['accounts'];
  $header = array(
    t('ID'),
    t('Server'),
    t('Base Dn #'),
    t('Username'),
    t('Email'),
  );
  $rows = array();
 
  $servers = ldap_servers_get_servers('','enabled');
  (!empty($ldap_server)) ? $user_attr = $ldap_server->user_attr : $user_attr = 'uid';  
  (!empty($ldap_server)) ? $mail_attr = $ldap_server->mail_attr : $mail_attr = 'mail';  
  $index = 0;

  foreach($accounts as $server => $dns) {
    foreach($dns as $basedn => $dn_accounts) {   
      foreach($dn_accounts as $index => $account) {
        if($index !== 'count') {
          if(!empty($account[$user_attr])) {
            $username = $account[$user_attr][0];
            $user = user_load_by_name($username);
            if($user) {
              $user_link = drupal_get_path_alias('user/'. $user->uid);
              $name = l(check_plain(format_username($user)), $user_link);
            } else {
              $name = $username;
            }
          } else {
            $name = t('N/A');
          }
          if(!empty($account[$mail_attr])) {
            $mail = $account[$mail_attr][0];
          } else {
            $mail = t('N/A');
          }
    
          $rows[] = array(
            $index+1,
            $server,
            $basedn,
            $name,
            $mail,
          );
          $index++;
        }
      }
    }
  }
  $output = '';
  $count = count($rows);
  if($count > $max_rows) {
    $output .= 'Search found <strong>'. $count .'</strong> matching entries. Only displaying the first '. $max_rows .' results.';
  }
  $output .= theme('table', array('header' => $header, 'rows' => $rows));
  return $output;

}
