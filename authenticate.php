<?php

ini_set('display_errors', '1');
error_reporting( E_WARNING );

require 'vendor/autoload.php';

use Httpful\Request;

$CLIENT_ID = INSTAGRAM_CLIENT_ID;

$redirect_uri = 'http://insta.ukm.no/authenticated.php';
$authentication_uri = 'https://api.instagram.com/oauth/authorize/?client_id='.$INSTAGRAM_CLIENT_ID.'&redirect_uri='.$redirect_uri.'&response_type=code';

echo '<script>window.location.href = "' . $authentication_uri . '"</script>';