<?php

/**
* @file
* Local settings.
*/

$databases['default']['default'] = array (
 'database' => 'drupalcluj',
 'username' => 'drupalcluj',
 'password' => 'drupalcluj',
 'prefix' => '',
 'host' => 'db',
 'port' => '3306',
 'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
 'driver' => 'mysql',
);
$settings['install_profile'] = 'dcc';
$config_directories['sync'] = 'config/sync';
