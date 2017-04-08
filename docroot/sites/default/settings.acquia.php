<?php

/**
 * @file
 * Contains settings for acquia cloud.
 */

$username = 'dcc';
$password = 'trustno1';

if ($_ENV['AH_SITE_ENVIRONMENT'] == 'prod') {
  // Remove basic auth on prod.
}
else {
  // PHP-cgi fix.
  if (!empty($_SERVER["REMOTE_USER"])) {
    $a = base64_decode(substr($_SERVER["REMOTE_USER"], 6));
  }
  else {
    $a = "";
  }

  if ((strlen($a) == 0) || (strcasecmp($a, ":") == 0)) {
    header('WWW-Authenticate: Basic realm="Private"');
    header('HTTP/1.0 401 Unauthorized');
  }
  else {
    list($name, $pass) = explode(':', $a);
    $_SERVER['PHP_AUTH_USER'] = $name;
    $_SERVER['PHP_AUTH_PW'] = $pass;
  }
  if (!(isset($_SERVER['PHP_AUTH_USER']) && ($_SERVER['PHP_AUTH_USER'] == $username && $_SERVER['PHP_AUTH_PW'] == $password))) {
    header('WWW-Authenticate: Basic realm="This site is protected"');
    header('HTTP/1.0 401 Unauthorized');
    // Fallback message when the user presses cancel / escape.
    echo 'Access denied';
    exit;
  }
}
// Enable acquia connector.
$config['acquia_connector.settings.yml']['subscription_data']['active'] = 'true';
