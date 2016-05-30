<?php
ini_set('display_errors', '1');
error_reporting( E_WARNING );

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
$table = 'ukm_insta_bilder';


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
$search_tag 	= "javielskerukm";
$uri    = "https://api.instagram.com/v1/tags/" . $search_tag . "/media/recent?client_id=".$CLIENT_ID;

# DETTE FORTELLER HVOR MANGE BILDER VI SKAL FÅ
if ( isset( $_GET['deepdive'] ) && isset( $_GET['next_url'] ) ) {
	#$uri .= "&max_tag_id=" . $_GET['max_tag_id'];
	$uri = $_GET['next_url'];
}

$response = Request::get($uri)->send();
#var_dump($response);
$next_url = $response->body->pagination->next_url;

$images = $response->body->data;

### SJEKK OM VI HAR BILDET FRA FØR
# Det eldste bildet er sist i arrayet, så vi kan hente bilder fra databasen som er nyere enn det.
# Da klarer vi oss med èn spørring.
$last = end($images);
reset($images);
$nyere_enn = $last->created_time;

if ( isset( $_GET['deepdive'] ) ) {
	$qry = new SQL("SELECT * FROM `#table`",
					array('table' => $table, 'tag' => $search_tag, 'nyere_enn' => $nyere_enn));	
}
else {
	$qry = new SQL("SELECT * FROM `#table` 
					WHERE 	`created_time` > '#nyere_enn'",
					array('table' => $table, 'tag' => $search_tag, 'nyere_enn' => $nyere_enn));
}

#echo $qry->debug();
$res = $qry->run();
#var_dump($res);
$lagrede_bilder = array();
if ($res) {
	while($row = mysql_fetch_assoc($res)) {
		#var_dump($row);
		$lagrede_bilder[] = $row['insta_id'];
	}	
}
else echo '<br>Ingen nyere bilder funnet i databasen.';

$imageList = array();
#var_dump($images);
foreach ( $images as $image ){
        #var_dump ($image);
        if (!in_array($image->id, $lagrede_bilder) ) {
        	$imageList[$image->id] = $image;
        }
/*      $imageList[$image->id]['url'] = $image->images->standard_resolution->url;
        $imageList[$image->id]['user'] = $image->user->username;
        $imageList[$image->id]['caption'] = $image->caption->text;
        $imageList[$image->id]['created_time'] = $image->created_time;*/
}

echo "<br>".count($imageList)." nye bilder funnet.";

#var_dump($imageList);
foreach ($imageList as $image) {
	### LEGG TIL / SJEKK OM BRUKEREN ER LAGT TIL I DATABASEN
	#var_dump($image);
	$sql = new SQL("SELECT `id` FROM `ukm_insta_users`
					WHERE `username` = '#username'", array('username' => $image->user->username));
	#echo $sql->debug();
	$user_id = $sql->run('field', 'id');
	if(!$user_id) {
		echo '<br>Oppretter ny bruker '.$image->user->username.'.';

		// Legg til bruker
		$sql = new SQLins('ukm_insta_users');
		$sql->add('username', $image->user->username);
		$sql->add('nicename', $image->user->full_name);
		$sql->add('insta_id', $image->user->id);
		$sql->add('profile_picture', $image->user->profile_picture);

		#echo $sql->debug();
		$res = $sql->run();
		if(!$res) continue;
		$user_id = $sql->insid();
	}
	echo '<br>Bruker '.$image->user->username.' har id '.$user_id.'.';
	
	### LEGG TIL / SJEKK OM TAGS ER LAGT TIL I DATABASEN
	echo '<br>Søker opp tagger...';
	$tags = array();
	foreach($image->tags as $tag) {
		$sql = new SQL("SELECT `id` FROM `ukm_insta_tags`
						WHERE `tag` = '#tag'", array('tag' => $tag));
		#echo $sql->debug();
		$tag_id = $sql->run('field', 'id');
		if(!$tag_id) {
			$sql = new SQLins('ukm_insta_tags');
			$sql->add('tag', $tag);
			$res = $sql->run();
			if(!$res) continue;
			$tag_id = $sql->insid();
		}
		$tags[$tag_id] = $tag;
		echo '<br>Tag '.$tag.' har id '.$tag_id.'.';
	}

	### LEGG TIL BILDET I DATABASEN
	echo '<br>Legger til bilde '.$image->id.'.';
	$sql = new SQLins('ukm_insta_bilder');
	$sql->add('insta_id', $image->id);
	$sql->add('user_id', $user_id);
	$sql->add('insta_url', $image->link);
	$sql->add('url', $image->images->standard_resolution->url);
	$sql->add('url_thumb', $image->images->thumbnail->url);
	$sql->add('url_lowres', $image->images->low_resolution->url);
	$sql->add('caption', $image->caption->text);
	$sql->add('search_tag', $search_tag);
	$sql->add('created_time', $image->created_time);
	$sql->add('upload_status', 'new');

	#echo $sql->debug();
	$res = $sql->run();
	$img_id = $sql->insid();
	if(!$res) {
		echo '<br><b>Feilet å legge til bilde!</b>';
		continue;
	} else '<br>Lagt til bilde '. $img_id .'.';

	### LEGG TIL TAGGER I RELASJONSTABELL
	echo '<br>Legger til tagger i relasjonstabell.';
	foreach ($tags as $tag_id => $tag) {
		$sql = new SQLins('ukm_insta_rel_img_tag');
		$sql->add('img_id', $img_id);
		$sql->add('tag_id', $tag_id);
		$res = $sql->run();
		if(!$res) continue;
		echo '<br>Tag '. $tag.' lagt til i relasjonstabell.';
	}
	echo '<br>Done.';
}

echo '<br>Alle bilder er lagt i databasen.';

### HVIS VI SKAL GÅ LANGT TILBAKE
if (isset( $_GET['deepdive'] ) ) {
	echo '<br>Går videre til neste sett nå...';
	echo '
	<script>
	window.setTimeout(function() {
		window.location = "http://insta.ukm.no?deepdive=true&next_url='.$next_url.'"
	}, 2000);
	</script>';
	#echo '<script>window.location = "http://insta.ukm.no?deepdive=true&max_tag_id='.$max_tag_id.'"</script>';

}

#var_dump($response);
#echo("".$response);

#$nye_bilder = array_diff_key($imageList, $lagrede_bilder);


### START OPPLASTING TIL DROPBOX
// KOBLE TIL API
#$dropbox = new Dropbox\Client( DROPBOX_AUTH_ACCESS_TOKEN, DROPBOX_APP_NAME, 'UTF-8' );
#$res = $dropbox->save_url()
#$db_endpoint = 'https://api.dropboxapi.com/2/files/save_url';

#$curl = new UKMCURL();
#$curl->post(array('path' => $db_save_path, 'url' => $image['url']));

?>