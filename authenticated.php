<?php

ini_set('display_errors', '1');
error_reporting( E_WARNING );

require 'vendor/autoload.php';

use Httpful\Request;

$CLIENT_ID = INSTAGRAM_CLIENT_ID;
echo '<br>';
var_dump($_REQUEST);