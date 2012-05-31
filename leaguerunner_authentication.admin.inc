<?php
/**
 * @file
 * Admin page callbacks for Leaguerunner integration
 */

/**
 * Menu callback: displays the Leaguerunner module settings page.
 *
 * @ingroup forms
 *
 */
function leaguerunner_authentication_admin_settings($form) {

  // Indexing settings:
  $form['leaguerunner_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Leaguerunner settings')
  );
  $form['leaguerunner_settings']['leaguerunner_base_url'] = array(
    '#type' => 'textfield',
    '#title' => t('Base URL for Leaguerunner installation'),
    '#default_value' => variable_get('leaguerunner_base_url', ''),
    '#size' => 40,
    '#maxlength' => 80,
    '#description' => t('The base URL of your Leaguerunner installation')
  );
  $form['leaguerunner_settings']['leaguerunner_cookie_domain'] = array(
    '#type' => 'textfield',
    '#title' => t('Cookie domain for Leaguerunner installation'),
    '#default_value' => variable_get('leaguerunner_cookie_domain', ''),
    '#size' => 30,
    '#maxlength' => 50,
    '#description' => t('The domain to use for Leaguerunner cookies.  The domain must be the same as, or a parent of both the domains for this Drupal server and your Leaguerunner base URL')
  );

  return system_settings_form($form);
}

?>
