<?php

ini_set('display_errors', '1');
error_reporting( E_WARNING );

require 'vendor/autoload.php';
require_once 'UKMconfig.inc.php';
require_once 'UKM/curl.class.php';
require_once('UKM/sql.class.php');

use Httpful\Request;

$INSTAGRAM_CLIENT_ID = INSTAGRAM_CLIENT_ID;
$INSTAGRAM_CLIENT_SECRET = INSTAGRAM_CLIENT_SECRET;
$redirect_uri = INSTAGRAM_AUTHORIZATION_REDIRECT_URI;
$authorization_endpoint = 'https://api.instagram.com/oauth/access_token';

$code = $_GET['code'];

$curl = new UKMcurl();
$curl->post(array(
					'client_id' => $INSTAGRAM_CLIENT_ID,
					'client_secret' => $INSTAGRAM_CLIENT_SECRET,
					'grant_type' => 'authorization_code',
					'redirect_uri' => $redirect_uri,
					'code' => $code
					));
$result = $curl->process($authorization_endpoint);

var_dump($result);
if(property_exists($result, 'error_message') ) {

	die();
}

$qry = new SQLins('ukm_insta_config', array('option_name' => 'access_token') );
$qry->add('option_name', 'access_token');
$qry->add('option_value', $result->access_token);
$res = $qry->run();

if ($res === false) {
	echo '<br>Klarte ikke lagre access_token i databaden.';
	echo '<br>'.$qry->error();
	die();
}
else if ($res == 0) {
	echo '<br>Ingen endringer i databasen.';
}
else {
	echo '<br>Lagret access_token i databasen.';
}

echo '<br><b>Done.</b>';