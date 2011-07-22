<?php

/**
 * @file
 * Administrative page callbacks for the ldap_provision module.
 */


/**
 * form for adding, updating, and deleting a single ldap authorization mapping
 *
 * @param <type> $form
 * @param <type> $form_state
 * @return array drupal form array
 */
function ldap_provision_admin_form($form, &$form_state) {
  require_once('LdapProvisionConfAdmin.class.php');
  $auth_conf = new LdapProvisionConfAdmin();
  if(!empty($form_state['storage']) && !empty($form_state['storage']['values']['accounts'])) {
    $accounts = $form_state['storage']['values']['accounts'];
  } else {
    $accounts = array();
  }
  return $auth_conf->drupalForm($accounts);
}

/**
 * alters the form based on the rule selection
 */
function ldap_provision_ajax_rule_callback($form, $form_state) {
}

/**
 * validate handler for the ldap_provision_admin_form
 */
function ldap_provision_admin_form_validate($form, &$form_state) {
  require_once('LdapProvisionConfAdmin.class.php');
  $auth_conf = new LdapProvisionConfAdmin();
  $errors = $auth_conf->drupalFormValidate($form_state['values']);
  foreach ($errors as $error_name => $error_text) {
    form_set_error($error_name, t(c($error_text)));
  }
}


/**
 * submit handler function for ldap_provision_admin_form
 */
function ldap_provision_admin_form_submit($form, &$form_state) {
  require_once('LdapProvisionConfAdmin.class.php');
  $auth_conf = new LdapProvisionConfAdmin();
  $auth_conf->drupalFormSubmit($form_state['values']);  // add form data to object and save or create

  if ($auth_conf->hasError == FALSE) {
    drupal_set_message(t('LDAP Provisioning configuration saved'), 'status');
    drupal_goto(LDAP_SERVERS_MENU_BASE_PATH . '/provisioning');
  }
  else {
    form_set_error($auth_conf->errorName, $auth_conf->errorMsg);
    $auth_conf->clearError();
  }

}


/**
 * submit handler function for test button on ldap_provision_admin_form
 */
function ldap_provision_test_submit($form, &$form_state) {
  require_once('LdapProvisionConfAdmin.class.php');
  $auth_conf = new LdapProvisionConfAdmin();
  $auth_conf->drupalFormSubmit($form_state['values']);  // add form data to object and save or create
  $accounts = ldap_provision_test_search($auth_conf, $form_state);
  $form_state['values']['accounts'] = $accounts;
  $form_state['storage']['values'] = $form_state['values'];
  $form_state['rebuild'] = true;
  drupal_set_message(t('LDAP Provisioning Test Run and Configuration Saved'), 'status');

}


