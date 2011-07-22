
================================================================================
Status Summary
================================================================================

This is a development version of LDAP provision add-on module to the LDAP package.

This module allows the scanning of ldap for accounts and provision/update drupal 
accounts based off the search results. Runs on a cron job for continous creates/updates.

Depends on ldap_profile for getting the fields to create the new drupal account.

Currently has the create drupal account in the LdapProvisionConf class until its
included in the LDAP servers/API module.
