<?php

require_once('UKM/sql.class.php');
require_once('UKM/inc/dropbox.inc.php');
require_once('imagick.php');

### KONSTANTER
$tmp_filename = 'tmp_image.jpg';
$dropbox_base_folder = '/UKMinsta/';

### Denne filen gjennomfører opplasting av filer til dropbox fra en liste i databasen
echo '<br><b>Dropbox-cron</b>';


### SJEKK FILER SOM ER IN PROGRESS
$qry = new SQL("SELECT * FROM `ukm_insta_bilder` 
				WHERE `upload_status` = 'PENDING'");
echo $qry->debug();
$res = $qry->run();
if($res)
	echo '<br>'.mysql_num_rows($res).' filer holder på med opplasting.';

### TELL ANTALL FILER SOM IKKE ER LASTET OPP
$qry = new SQL("SELECT *,
				`i`.`id` AS `id`,
				`u`.`id` AS `user_id`,
				`i`.`url` AS `url`,
				`u`.`url` AS `user_url`
				FROM `ukm_insta_bilder` AS `i`
					JOIN `ukm_insta_users` AS `u` 
					ON (`i`.`user_id` = `u`.`id`)
					WHERE `upload_status` = 'new'
					ORDER BY `i`.`id` ASC");
echo '<br>'. $qry->debug();
$res = $qry->run();
if (mysql_num_rows($res) == 0) {
	die('<br>Alle filer er lastet opp.');
}
echo '<br>'.mysql_num_rows($res).' filer er ikke lastet opp til Dropbox.';

### BEGYNN PÅ KØEN
while ($r = mysql_fetch_assoc($res)) {
	echo '<br>Bilde-info: ';
	echo '<br><pre>';
	var_dump($r);
	echo '</pre>';
	### FINN BILDEDETALJER
	$image_id = $r['id'];
	$image_folder = $r['search_tag'];
	$image_filename = $r['username'] . '_' . $r['insta_id'] . '.jpg';
	$image_caption = $r['caption'];
	$image_username = '@'.$r['username'];
	if ($r['nicename']) {
		$image_username .= ' - ' . $r['nicename'];
	}
	$image_file = $r['url'];
	
	echo '<br>Mappe: '.$image_folder;
	echo '<br>Fil: '.$image_filename;
	echo '<br>Caption: '.$image_caption;
	echo '<br>Dropbox-path: ' . $dropbox_base_folder . $image_folder . '/' . $image_filename;
	
	### SEND BILDET TIL IMAGICK
	$img_res = ukm_wrap($image_file, $tmp_filename, $image_username, $image_caption, null);
	echo '<img src="'.$tmp_filename.'">';
	### LAST OPP BILDET
	# Koble til API
	$dropbox = new Dropbox\Client( DROPBOX_AUTH_ACCESS_TOKEN, DROPBOX_APP_NAME, 'UTF-8' );
	# Gjør oplasting
	$db_res = $dropbox->uploadFile($dropbox_base_folder . $image_folder . '_' . $image_filename , Dropbox\WriteMode::add(), $file, $size);
	# Resultat:
	echo '<br>Dropbox-upload-resultat: ';
	echo '<br><pre>';
	var_dump($db_res);
	echo '</pre>';
	$success = $db_res['bytes'] == $size;
	if( $success ) {		
		$SQLins = new SQLins('ukm_insta_bilder', array('id' => $image_id ) );
		$SQLins->add('upload_status', 'COMPLETE');
		$SQLins->run();
	}
	else {
		$SQLins = new SQLins('ukm_insta_bilder', array('id' => $image_id ) );
		$SQLins->add('upload_status', 'ERROR');
		$SQLins->run();	
	}

	die();
}
