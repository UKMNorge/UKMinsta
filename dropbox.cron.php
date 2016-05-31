<?php

require_once('UKM/sql.class.php');
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
				`i`.`id` AS `image_id`,
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
	$image_folder = $r['search_tag'];
	$image_filename = $r['username'] . '_' . $r['insta_id'] . '.jpg';
	
	echo '<br>Mappe: '.$image_folder;
	echo '<br>Fil: '.$image_filename;
	echo '<br>Dropbox-path: ' . $dropbox_base_folder . $image_folder . '/' . $image_filename;
	### SEND BILDET TIL IMAGICK

	### LAST OPP BILDET
	#$res = $dropbox->uploadFile($dropbox_base_folder . $image_folder . '_' . $image_filename , Dropbox\WriteMode::add(), $file, $size);
	#$success = $res['bytes'] == $size;

	die();
}


// KOBLE TIL API
#$dropbox = new Dropbox\Client( DROPBOX_AUTH_ACCESS_TOKEN, DROPBOX_APP_NAME, 'UTF-8' );
#$res = $dropbox->save_url()
#$db_endpoint = 'https://api.dropboxapi.com/2/files/save_url';

#$curl = new UKMCURL();
#$curl->post(array('path' => $db_save_path, 'url' => $image['url']));
