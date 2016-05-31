<?php

require_once('UKM/sql.class.php');
require_once('imagick.php');

### Denne filen gjennomfører opplasting av filer til dropbox fra en liste i databasen

### TELL ANTALL FILER SOM IKKE ER LASTET OPP
$qry = new SQL("SELECT * FROM `ukm_insta_bilder` 
				WHERE `upload_status` = 'new'");
echo $qry->debug();
$res = $qry->run();
echo '<br>'.mysql_num_rows($res).' filer er ikke lastet opp til Dropbox.';
### 


// KOBLE TIL API
#$dropbox = new Dropbox\Client( DROPBOX_AUTH_ACCESS_TOKEN, DROPBOX_APP_NAME, 'UTF-8' );
#$res = $dropbox->save_url()
#$db_endpoint = 'https://api.dropboxapi.com/2/files/save_url';

#$curl = new UKMCURL();
#$curl->post(array('path' => $db_save_path, 'url' => $image['url']));
