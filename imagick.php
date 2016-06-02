<?php
// DATA
/*$imagepath_read = 'testimage.jpg';
$imagepath_write = 'testimage_new.png';
$name = 'Marius Mandal';
$username = '@mariusmandal';
$caption = 'There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don\'t look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn\'t anything embarrassing hidden in the middle of text.';
$tags = '#wow #denne #kan #bli #lang #og #veldigstygg #men #er #verdt #et #forsøk #There #are #manyvariationsofpassages #of #LoremIpsum';
*/

function ukm_wrap($imagepath_read, $imagepath_write, $name, $username, $caption, $tags) {
    // FONT SIZE AND COLOR
    $name_fontsize = 22;
    $name_fontcolor = '#000';
    $name_font = 'ITCAvantGardePro-Bold.otf';

    if( empty( $name ) ) {
        $username_fontsize = $name_fontsize;
        $username_fontcolor = $name_fontcolor;
    } else {
        $username_fontsize = 18;
        $username_fontcolor = '#363636';    
    }
    $username_font = 'ITCAvantGardePro-Md.otf';

    $caption_fontsize = 16;
    $caption_fontcolor = '#000';
    $caption_font = 'ITCAvantGardePro-Bk.otf';
    $caption_margin = 4;

    $tags_fontsize = 14;
    $tags_fontcolor = '#0000ff';
    $tags_font = 'ITCAvantGardePro-Md.otf';

    $textfield_margin = 10;
    $textfield_top_margin = 10;

    $name_username_margin = empty( $name ) ? 0 : 6;

    //////////////////////////////////////////////
    // DO THE MAGIC

    // LOAD IMAGE
    $image = new Imagick( $imagepath_read );
    $image->setImageFormat('png');
    $image_dimensions = $image->getImageGeometry(); 
    $image_width = $image_dimensions['width']; 
    $image_height = $image_dimensions['height']; 

    // CREATE PALETTE
    $palette = new Imagick();
    $palette->newImage($image_width, $image_height, new ImagickPixel('#fff'));
    $palette->setImageFormat('png');

    // CREATE USERNAME
    $username_palette = new ImagickDraw();
    $username_palette->setFont('font/'. $username_font );
    $username_palette->setFontSize( $username_fontsize );
    $username_palette->setFillColor( $username_fontcolor );
    $username_palette->setGravity(Imagick::GRAVITY_SOUTHWEST);

    // CREATE USERNAME
    $name_palette = new ImagickDraw();
    $name_palette->setFont('font/'. $name_font );
    $name_palette->setFontSize( $name_fontsize );
    $name_palette->setFillColor( $name_fontcolor );
    $name_palette->setGravity(Imagick::GRAVITY_SOUTHWEST);

    // CREATE CAPTION
    $caption_palette = new ImagickDraw();
    $caption_palette->setFont('font/'. $caption_font );
    $caption_palette->setFontSize( $caption_fontsize );
    $caption_palette->setFillColor( $caption_fontcolor );
    $caption_palette->setGravity(Imagick::GRAVITY_SOUTHWEST);

    // CREATE TAGS
    $tags_palette = new ImagickDraw();
    $tags_palette->setFont('font/'. $tags_font );
    $tags_palette->setFontSize( $tags_fontsize );
    $tags_palette->setFillColor( $tags_fontcolor );
    $tags_palette->setGravity(Imagick::GRAVITY_SOUTHWEST);

    // CALCULATE TEXT LINES (CAPTION + TAGS)
    list($caption_lines, $caption_fontsize) = wordWrapAnnotation($palette, $caption_palette, $caption, $image_width-($textfield_margin*2) );
    list($tags_lines, $tags_fontsize) = wordWrapAnnotation($palette, $tags_palette, $tags, $image_width-($textfield_margin*2) );

    // CALCULATE PALETTE SIZE
    $tags_height        = $tags_fontsize * sizeof( $tags_lines );
    $caption_height     = $caption_fontsize * sizeof( $caption_lines );
    $username_height    = $username_fontsize;
    $height_textfield   = $textfield_top_margin*2 // Legg på margin over og under tekst
                         + $name_fontsize       // Høyde på navnet (brukernavn krever mindre plass)
                         + $caption_height      // Høyde på selve caption
                         + $caption_margin      // Margin mellom caption og tags
                         + $tags_height;        // Høyde på tags

    if(isset($_GET['debug'])) {
        echo '<br>Caption height: '. var_export($caption_height, TRUE);
        echo '<br>Username height: '. var_export($username_height, TRUE);
        echo '<br>Tags height: '. var_export($tags_height, TRUE);
        echo '<br>height_textfield: '. var_export($height_textfield, TRUE);
        echo '<br>caption_lines: '.var_export($caption_lines, TRUE);
        echo '<br>tags_lines: '.var_export($tags_lines, TRUE);
        echo '<br>textfield_top_margin: '.var_export($textfield_top_margin, TRUE);
    }

    // RE-CREATE PALETTE INCLUDING TEXTFIELD HEIGHT
    $palette = new Imagick();
    $palette->newImage($image_width, ($image_height+$height_textfield), new ImagickPixel('#fff'));
    $palette->setImageFormat('png');

    // ADD IMAGE TO PALETTE
    $palette->compositeImage($image, Imagick::COMPOSITE_DEFAULT, 0, 0); 

    // WRITE STUFF
        // ADD NAME
        $name_offset_x = $textfield_margin;
        $name_offset_y = $height_textfield - $textfield_top_margin - $name_fontsize;
        $palette->annotateImage($name_palette, $name_offset_x, $name_offset_y, 0, $name);

        $name_metrics = $palette->queryFontMetrics($name_palette, $name);
        $name_width = $name_metrics['textWidth'];
        
        // ADD USERNAME
        $username_offset_x = $textfield_margin + $name_username_margin + $name_width;
        $username_offset_y = $name_offset_y + 1;
        $palette->annotateImage($username_palette, $username_offset_x, $username_offset_y, 0, $username);
        
        // ADD CAPTION
        $caption_offset_x = $textfield_margin;
        $caption_offset_y = $name_offset_y; #- $name_fontsize
        for($i = 0; $i < count($caption_lines); $i++) {
            $caption_text_height = $i * $caption_fontsize;
            $palette->annotateImage($caption_palette, $caption_offset_x, ($caption_offset_y - $caption_text_height), 0, $caption_lines[$i]);
        }
        
        // ADD TAGS
        $tags_offset_x = $textfield_margin;
        $tags_offset_y = $caption_offset_y - $caption_text_height - $caption_fontsize - $caption_margin; 
        for($i = 0; $i < count($tags_lines); $i++) {
            $text_height = $i * $tags_fontsize;
            $palette->annotateImage($tags_palette, $textfield_margin, ($tags_offset_y - $text_height), 0, $tags_lines[$i]);
        }

    $palette->setImageFileName( dirname(__FILE__) .'/'. $imagepath_write );
    $res = $palette->writeImage();
    return $res;
}


// THANKS TO: 
// http://stackoverflow.com/questions/5746537/how-can-i-wrap-text-using-imagick-in-php-so-that-it-is-drawn-as-multiline-text
function wordWrapAnnotation(&$image, &$draw, $text, $maxWidth)
{
    $words = explode(" ", $text);
    $lines = array();
    $i = 0;
    $lineHeight = 0;
    while($i < count($words) )
    {
        $currentLine = $words[$i];
        if($i+1 >= count($words))
        {
            $lines[] = $currentLine;
            break;
        }
        //Check to see if we can add another word to this line
        $metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i+1]);
        while($metrics['textWidth'] <= $maxWidth)
        {
            //If so, do it and keep doing it!
            $currentLine .= ' ' . $words[++$i];
            if($i+1 >= count($words))
                break;
            $metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i+1]);
        }
        //We can't add the next word to this line, so loop to the next line
        $lines[] = $currentLine;
        $i++;
        //Finally, update line height
        if($metrics['textHeight'] > $lineHeight)
            $lineHeight = $metrics['textHeight'];
    }
    return array($lines, $lineHeight);
}