<?php
// $Id: ldap_servers.theme.inc,v 1.4.2.1 2011/02/08 20:05:41 johnbarclay Exp $


/**
 * @file
 * theming functions for ldap_servers module
 *
 */

/**
 * Returns HTML for ldap servers list.
 *
 * @param $variables
 *   An associative array containing:
 *   - ldap_servers: an array of one or more ldap server configurations.
 *   - actions:  true or false indicating include update, delete, etc. links
 *   - type:  'table', 'list', etc for style to render
 *
 * @ingroup themeable
 */
function theme_ldap_servers_list($variables) {
  extract($variables);

  $table = array(
    'header' => array(t('Name'), t('Type'), t('Enabled'), t('Server Address')),
    'attributes' => array('id' => 'ldap_servers_servers', 'class' => 'data'),
    'colgroups' => array(),
    'sticky' => FALSE,
    'empty' => '',
    'caption' => 'LDAP Server Configurations',
  );

  if ($actions) {
    $table['header'][] = "Operations";
  }

  if (count($ldap_servers)) {
    foreach ($ldap_servers as $sid => $ldap_server ) {
      $row =  array(
        $ldap_server->name,
        $ldap_server->type,
        ($ldap_server->status == 1) ? "Yes" : "No",
        $ldap_server->address
          );
      if ($actions) {
        $switch = ($ldap_server->status ) ? 'disable' : 'enable';
        $actions =  ' | ' . l(t('edit'), LDAP_SERVERS_MENU_BASE_PATH . '/servers/edit/' . $sid) . ' | ' .
            l(t('delete'), LDAP_SERVERS_MENU_BASE_PATH . '/servers/delete/' . $sid) . ' | ' .
            l(t('test'), LDAP_SERVERS_MENU_BASE_PATH . '/servers/test/' . $sid) . ' | ' .
            l($switch, LDAP_SERVERS_MENU_BASE_PATH . '/servers/' . $switch . '/' . $sid) . ' | ';


        $row[] = $actions;
      }

      $table['rows'][] = $row;
    }
  }
  else {
    $table['rows'] = array();
  }
  $output = theme('table', $table);

  return $output;
}

/**
 * Returns HTML for ldap server.
 *
 * @param $variables
 *   An associative array containing:
 *   - ldap_servers_config: an array of one or more ldap server configurations.
 *   - actions:  true or false indicating include update, delete, etc. links
 *   - type:  'table', 'list', etc for style to render
 *
 * @ingroup themeable
 */

function theme_ldap_servers_server($variables) {
  extract($variables);  // $ldap_server, $actions, $type (see above)

  require_once('LdapServer.class.php');
  $properties = array();
  foreach (LdapServer::field_to_properties_map() as $field_name => $property_name) {
      $properties[] = "$field_name = " . $ldap_server->$property_name; // $value";
  }

  if ($actions) {
    $switch = ($ldap_server->status) ? 'disable' : 'enable';
    $properties[] =  ' | ' . l(t('edit'), LDAP_SERVERS_MENU_BASE_PATH . '/servers/edit/' . $sid) . ' | ' .
        l(t('delete'), LDAP_SERVERS_MENU_BASE_PATH . '/servers/delete/' . $sid) . ' | ' .
        l(t('test'), LDAP_SERVERS_MENU_BASE_PATH . '/servers/test/' . $sid) . ' | ' .
        l($switch, LDAP_SERVERS_MENU_BASE_PATH . '/servers/' . $switch . '/' . $sid) . ' | ';
  }

  $output = theme_item_list(
    array(
      'items' => $properties,
      'type' => 'ul',
      'title' => 'Server Properties',
      'attributes' => array()
      )
    );

  return $output;
}

function theme_ldap_servers_https_required($vars) {

  if (!isset($vars['site_contact_link']) || empty($vars['site_contact_link'])) {
    $vars['site_contact_link'] = 'site admin';
  }
  return t("You are accessing site_name using an unencrypted connection. For your security,
     site_name only supports account logins using a secure protocol such as HTTPS. You can switch
     to HTTPS by trying to view this page again after changing the URL in your browser's
     location bar to begin with \"https\" instead of \"http\". Please contact
      site_contact_link for help if this error continues.", $vars);

}
