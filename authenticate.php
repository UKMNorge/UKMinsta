<?php

ini_set('display_errors', '1');
error_reporting( E_WARNING );

require 'vendor/autoload.php';
require_once('UKMconfig.inc.php');

use Httpful\Request;

$INSTAGRAM_CLIENT_ID = INSTAGRAM_CLIENT_ID;
$redirect_uri = INSTAGRAM_AUTHORIZATION_REDIRECT_URI;

$authentication_uri = 'https://api.instagram.com/oauth/authorize/?client_id='.$INSTAGRAM_CLIENT_ID.'&redirect_uri='.$redirect_uri.'&response_type=code';

echo '<script>window.location.href = "' . $authentication_uri . '"</script>';