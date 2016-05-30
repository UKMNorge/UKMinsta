<?php
ini_set('display_errors', '1');
error_reporting( E_ALL );

# Denne filen laster opp nye bilder lagt ut på Instagram med hashtaggen #javielskerukm til UKMs Dropbox.
# Author: Asgeir Stavik Hustad

# Inspired by JimTrims HashViewer - https://github.com/Jimtrim/HashViewer
require 'vendor/autoload.php';

require_once('UKMconfig.inc.php');
require_once('UKM/inc/dropbox.inc.php');
require_once('UKM/sql.class.php');
require_once('UKM/curl.class.php');

#phpinfo();
use Httpful\Request;

### CONSTANTS
$table = 'instagram_bilder';


## TODO / DOCS:
#1 - DATABASESJEKK - HVILKE BILDER HAR VI LAGRET
#       - Lagre:
#	# ukm_insta_bilder
# 		- id, insta_id, user_id, insta_url, url, url_thumb, url_lowres, search_tag, caption, created_time, dropbox_job_id, dropbox_path, dropbox_upload_status
#			- id = INT, auto increment
#			- insta_id = VARCHAR
#			- user_id = INT, rel til ukm_insta_users->ID
#			- insta_url = URL til bildesiden på Instagram
#			- url - url_lowres = URL til bildefiler
#			- search-tag = tag vi lagret bildet på, REL til ukm_insta_tags
#			- caption = Bildetekst
#			- created_time = Tidspunkt bildet ble lastet opp på instagram
#			- dropbox_job_id = Jobb-ID fra Dropbox
# 			- upload_status = Status fra Dropbox. { NEW, PENDING, DOWNLOADING, COMPLETE, FAILED }
# 				- New er før registrert med Dropbox,
#	# ukm_insta_rel_img_tag
#		- id, img_id, tag_id
#	# ukm_insta_tags
# 		- id, tag
# 	# ukm_insta_users
#		- id, insta_id, username, nicename, url
#2 - LOGIKK FOR Å BE OM BILDER FRA TIDLIGERE
#       - Hva med å se på CreatedTime? Hvis vårt siste lagrede bilde er eldre enn det eldste bildet i samlinga, be om flere.
#       - Kan ha "deep mode" - som ber om flere til Insta ikke gir flere, og sjekker alle ID'er.
#3 - DROPBOX-INTEGRERING
#       - Se på Dropbox-HTTP-endpoint save_url
#               - https://api.dropboxapi.com/2/files/save_url
#               - Eksempel:
#                     curl -X POST https://api.dropboxapi.com/2/files/save_url \
#                       --header "Authorization: Bearer <get access token>" \
#                       --header "Content-Type: application/json" \
#                       --data "{"path": "/a.txt","url": "http://example.com/a.txt"}"
#                       
#                       PARAMETERS
#                       {
#                               "path": "/a.txt",
#                               "url": "http://example.com/a.txt"
#                       }
#                       SaveUrlArg
#                       path String The path in Dropbox where the URL will be saved to.
#                       url String The URL to be saved.
#		- Filnavn: 
#			- Ikke bruk caption som filnavn (Ekstremt lange captions er veldig vanlig!)
#			- Lagre heller caption i databasen, om vi skal ha de.
#4 - UKM.NO-INTEGRASJON
#       - "Det er postet 5 nye #javielskerukm-bilder denne uken."
#		- Bruk insta.ukm.no som galleri for bilder tagget med UKM-hashtagger?

# JIMs ID - skaff egen og få appen godkjent etterhvert
$CLIENT_ID = INSTAGRAM_CLIENT_ID;

#$tag    =  $_GET['hashtag'];
$tag 	= "javielskerukm";
$uri    = "https://api.instagram.com/v1/tags/" . $tag . "/media/recent?client_id=".$CLIENT_ID;

# DETTE FORTELLER HVOR MANGE BILDER VI SKAL FÅ
# IKKE I BRUK
if ( isset($_GET['max_tag_id']) ) {
        $uri .= "&max_tag_id=" . $_GET['max_tag_id'];
}

$response = Request::get($uri)->send();
#var_dump($response);
$images = $response->body->data;

### SJEKK OM VI HAR BILDET FRA FØR
# Det eldste bildet er sist i arrayet, så vi kan hente bilder fra databasen som er nyere enn det.
# Da klarer vi oss med èn spørring.
$last = end($images);
reset($images);
$nyere_enn = $last['created_time'];

$qry = new SQL("SELECT * FROM `#table` 
				AND 	`created_time` > '#nyere_enn'",
				array('table' => $table, 'tag' => $tag, 'nyere_enn' => $nyere_enn));
echo $qry->debug();
$res = $qry->run();
var_dump($res);
if (!$res) {
	die('Ingen nye bilder.');
}

$lagrede_bilder = array();
while($row = mysql_fetch_assoc($res)) {
	var_dump($row);
	$lagrede_bilder[] = $row['insta_id'];
}

$imageList = array();
foreach ( $images as $image ){
        #echo '<br>image<br>\r\n';
        #var_dump ($image);
        #var_dump($image->images->standard_resolution->url);
        if (in_array($lagrede_bilder, $image->id) ) {
        	continue;
        }
        
        $imageList[$image->id]['url'] = $image->images->standard_resolution->url;
        $imageList[$image->id]['user'] = $image->user->username;
        $imageList[$image->id]['caption'] = $image->caption->text;
        $imageList[$image->id]['created_time'] = $image->created_time;
}
var_dump($imageList);

#var_dump($response);
#echo("".$response);

#$nye_bilder = array_diff_key($imageList, $lagrede_bilder);


### START OPPLASTING TIL DROPBOX
// KOBLE TIL API
#$dropbox = new Dropbox\Client( DROPBOX_AUTH_ACCESS_TOKEN, DROPBOX_APP_NAME, 'UTF-8' );
#$res = $dropbox->save_url()
$db_endpoint = 'https://api.dropboxapi.com/2/files/save_url';

#$curl = new UKMCURL();
#$curl->post(array('path' => $db_save_path, 'url' => $image['url']));

?>