<?php
ob_end_clean();

require_once('UKM/sql.class.php');
require_once('UKM/inc/dropbox.inc.php');
require_once('imagick.php');

### KONSTANTER
$tmp_filename = 'tmp_image.jpg';
$dropbox_base_folder = '/UKMdigark/UKMinsta/'. date("Y").'/';

### Denne filen gjennomfører opplasting av filer til dropbox fra en liste i databasen
out('Dropbox-cron', 'b');

### SJEKK FILER SOM ER IN PROGRESS
$qry = new SQL("SELECT * FROM `ukm_insta_bilder` 
				WHERE `upload_status` = 'PENDING'");
#echo $qry->debug();
$res = $qry->run();
if(mysql_num_rows($res) > 0)
	out(mysql_num_rows($res).' filer holder på med opplasting.');

### TELL ANTALL FILER SOM IKKE ER LASTET OPP
$qry = new SQL("SELECT *,
				`i`.`id` AS `id`,
				`u`.`id` AS `user_id`,
				`i`.`url` AS `url`,
				`u`.`url` AS `user_url`,
				`i`.`insta_id` AS `insta_id`,
				`u`.`insta_id` AS `insta_user_id`
				FROM `ukm_insta_bilder` AS `i`
					JOIN `ukm_insta_users` AS `u` 
					ON (`i`.`user_id` = `u`.`id`)
					WHERE `upload_status` = 'new'
					ORDER BY `i`.`id` ASC");
out( $qry->debug() );
$res = $qry->run();
if (mysql_num_rows($res) == 0) {
	die('<br>Alle filer er lastet opp.');
}
out( mysql_num_rows($res).' filer er ikke lastet opp til Dropbox.' );

### BEGYNN PÅ KØEN
while ($r = mysql_fetch_assoc($res)) {
	
	out('Bilde-info: ');
	print_r($r);
	### FINN BILDEDETALJER
	$image_id = $r['id'];
	$image_folder = $r['search_tag'];
	$image_filename = $r['username'] . '_' . $r['insta_id'] . '.jpg';
	$image_caption = $r['caption'];
	$image_username = '@'.$r['username'];
	$db_path = $dropbox_base_folder . $image_folder . '/' . $image_filename;
	$image_nicename = null;
	if ( !empty( $r['nicename'] ) ) {
		$image_nicename = $r['nicename']; #$image_username .= ' - ' . $r['nicename'];
	}
	$image_file = $r['url'];
	
	out('Mappe: ' . $image_folder);
	out('Fil: ' . $image_filename);
	out('Navn: ' . $image_nicename);
	out('Brukernavn: ' . $image_username);
	out('Caption: ' . $image_caption);
	out('Dropbox-path: ' . $db_path);
	
	### SEND BILDET TIL IMAGICK
	try {
		$img_res = ukm_wrap($image_file, $tmp_filename, $image_nicename, $image_username, $image_caption, null);
		echo '<img src="'.$tmp_filename.'">';
	}
	catch(Exception $e) {
		out('Imagick feilet på bildet: '. $e->getMessage(), 'b');
		out('Markerer bildet som IMAGICK_ERROR og går videre.');

		$SQLins = new SQLins('ukm_insta_bilder', array('id' => $image_id ) );
		$SQLins->add('upload_status', 'IMAGICK_ERROR');
		$SQLins->run();	
		ob_flush();
		flush();
		continue;
	}

	### LAST OPP BILDET
	# Koble til API
	$dropbox = new Dropbox\Client( DROPBOX_AUTH_ACCESS_TOKEN, DROPBOX_APP_NAME, 'UTF-8' );
	# Gjør oplasting
	clearstatcache(false, $tmp_filename);
	$size = filesize($tmp_filename);
	$file = fopen($tmp_filename, "rb");
	$db_res = $dropbox->uploadFile($db_path, Dropbox\WriteMode::add(), $file, $size);
	fclose($file);
	# Resultat:
	out('Dropbox-upload-resultat: ');
	out(print_r($db_res));

	$success = $db_res['bytes'] == $size;
	if( $success ) {		
		$SQLins = new SQLins('ukm_insta_bilder', array('id' => $image_id ) );
		$SQLins->add('upload_status', 'COMPLETE');
		$SQLins->add('dropbox_path', $db_path);
		$SQLins->run();
	}
	else {
		$SQLins = new SQLins('ukm_insta_bilder', array('id' => $image_id ) );
		$SQLins->add('upload_status', 'ERROR');
		$SQLins->run();	
	}
	ob_flush();
	flush();
}

function out($string, $tag = false) {
	if($tag) {
		echo '<br><'.$tag.'>'.htmlentities($string).'</'.$tag.'>';
	}
	else
		echo '<br>'.htmlentities($string);
}
