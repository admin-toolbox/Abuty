<?php

function initialize($ui){
	global $f3;
    $xhtml .= '<ul id="bfSecondaryTabs">';
    $xhtml .= '<li class="abuty_ui_left"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/media/images">Images/Photos</a></li>';
    $xhtml .= '<li class="abuty_ui_middle"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/media/audio">Audio</a></li>';
    $xhtml .= '<li class="abuty_ui_middle"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/media/video">Video</a></li>';
    $xhtml .= '<li class="abuty_ui_right"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/media/streams">Live Streams</a></li>';
    $xhtml .= '<li class="abuty_ui_seperator"></li>';
    $xhtml .= '<li class="abuty_ui_left"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/media/players">Player/Presentation</a></li>';
    $xhtml .= '<li class="abuty_ui_middle"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/media/galleries">Photo Galleries</a></li>';
    $xhtml .= '<li class="abuty_ui_right"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/media/feeds">RSS & XML Feeds</a></li>';
    $xhtml .= '<li class="abuty_ui_seperator"></li>';
    $xhtml .= '<li class="abuty_ui_help"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/media/help" class="help">?</a></li>';
    $xhtml .= '</ul>';
	$xhtml .= '<div class="head">Media Management</div>';
	$xhtml .= '<ul>';
	$xhtml .= '<li>We will offer a nice display for recently uploaded or managed media. Right now, you must click a sub-navigation tab to see anything.</li>';
    $xhtml .= "</ul>";
$xhtml .= <<<EOF
EOF;
	return array("display"=>"$xhtml");
}

function details($ui, $params) {
    switch (SCREEN) { // switch to specific details function for each media type.
        case "images" : $xhtml = image_details($ui, $params); break;
        case "audio" : $xhtml = avdetails($ui, $params); break;
        case "video" : $xhtml = avdetails($ui, $params); break;
        case "players" : $xhtml = player_details($ui, $params); break;
		case "player" : $xhtml = player_details($ui, $params); break;
        case "galleries" : $xhtml = gallery_details($ui, $params); break;
        default: $xhtml = "<div class=\"white_form\">No details panel defined.</div>"; break;
    }
    return $xhtml;
}

/*	PREVIEW		*/
/* Handles the previewing of images, audio and video. */
function preview($params) {
    switch(SCREEN) { // Detecting screen from rail
        case 'images' : $xhtml = images_cms_preview($ui, $params); break;
        case 'audio' : $xhtml = $ui->make_audio_player($ui, $params); break;
        case 'video' : $xhtml = $ui->make_video_player($ui, $params); break;
        case 'other' : $xhtml = $ui->other_cms_preview($ui, $params); break;
        case 'players' : $xhtml = $ui->make_video_player($ui, $params); break;
        case 'galleries' : $xhtml = $ui->make_galleries($ui, $params); break;
        case 'feeds' : $xhtml = $ui->make_feeds($ui, $params); exit; break;
    }
   return $xhtml;
}

function audio($ui, $params) { //Field named is this... so we direct that field name to use another function.
	return avpanel($ui, $params);
}
function video($ui, $params) { // Same thing as audio. Field name is function name for normal screens.... RPC's are different.
	return avpanel($ui, $params);
}

function get_any_id_of_group($group){
	$query = mysql_query("SELECT id FROM `media` WHERE `group` = '".$group."' LIMIT 1;");
	$result = mysql_fetch_object($query);
	return $result->id;
}

function count_files_by_group($group) {
	return mysql_num_rows(mysql_query("SELECT * FROM `media` WHERE `group` = '".$group."';"));
}

function players($ui, $params) { // formally the audio and video functions. Now both in one.
	$record = RECORD;
	$panel = PANEL;
	if ( ACTION != "" ) {
		// use switch(ACTION) here.
		switch(ACTION) {
			case 'details':
				$screen_function = ACTION;
				$screen = (object) $screen_function($ui, $params);
				return array("details"=>$screen->xhtml, "javascript"=>$screen->javascript);			
			break;
			case 'preview':
				$screen_function = ACTION;
				$screen = $screen_function($ui, $params);
				echo $screen;
			break;
		}
		exit;
	}
	$extentions = $framework->get_extentions_from_mimetype(SCREEN."/%");	// function name is same as screen.??
	$xhtml .= '<div id="abuty_leftnav_container">';
	if (SCREEN == 'search') {
		$sql = ( defined('RECORD') ) ? "SELECT * FROM `abuty_presentation` WHERE `group` != '' LIMIT 15;"
									 : "SELECT * FROM `abuty_presentation` WHERE `LIMIT 15;";
	} else {	
		$xhtml .= '<div id="abuty_leftnav_search"><input name="abuty_leftnav_search" value="Search"></div>';
	    $xhtml .= '<div id="abuty_leftnav" class="scrollable vertical"><div class="items">'."\n";
		$sql = "SELECT distinct `group` FROM `media` WHERE `group` != '';";
	}
	$results = mysql_query($sql);
	$counter = 0;
    if ( mysql_num_rows($results) > 0 ) {
        while($result = mysql_fetch_object($results)) {
				if ( $counter == 0  ) { $xhtml .= "\t".'<div>'."\n"; }
				$title = str_replace('.'.$result->extention, "", $result->orig_filename);
				//$title = $framework->wordtruncate($result->orig_filename, 5, ".mp3", "...");
				$random_id_of_group = get_any_id_of_group($result->group);
				$files_counted = count_files_by_group($result->group);
				
				
				$files_counted  = ($files_counted > 1) ? " items" : " item";
				
				$xhtml .= "\t\t".'<div class="item"><a id="'.$random_id_of_group.'" href="#" title="'.$title.'">'.stripslashes($result->group).'</a>&nbsp;&nbsp;<span>('.$files_counted.')</span></div>'."\n";
				if ( $counter > 3 ) { $xhtml .= "\t".'</div>'."\n\t".'<div>'."\n"; }
				if ( $counter == 4 ) { $counter = 0; }
           
			$counter++;
        }
    } else {
        $xhtml .= "No results available for ".RECORD;
    }
	if (SCREEN == 'search') {
		$javascript = '$(".scrollable").scrollable({ vertical: true, mousewheel: true });';
		return array("leftnav"=>$xhtml, "sql"=>$sql, "javascript"=>"$javascript");
		exit;
	}
    $xhtml .= "\t".'</div>'."\n"; //leftnav
    $xhtml .= "\t".'</div>'."\n"; //leftnav_container
    $xhtml .= <<<EOF
</div>
</div>
<div id="abuty_windows">
	<div id="abuty_details"><div class="head">Please select a presentation to begin working.</div></div>
	<div id="abuty_preview"></div>
</div>
<style>
.abuty_leftnav  a {
	background:transparent url(/icons/folder.png) center left no-repeat;
	display:inline-block;
	padding-left:20px;
	line-height:18px;
}
.abuty_leftnav a[href$='.mp3'], a[href$='.rar'], a[href$='.gzip'] {
	background:transparent url(/icons/small/sound2.png) center left no-repeat;
	display:inline-block;
	padding-left:20px;
	line-height:18px;
}
</style>

EOF;
//$xhtml .= '</textarea>';
$javascript .= <<<EOF
	$('div[id=abuty_actions]').empty();
	$('div[id=abuty_actions]').html('<a class="prev">&laquo; Back</a><a class="next">More results &raquo;</a>');
	$(".scrollable").scrollable({ vertical: true, mousewheel: true });
	$('input[name=abuty_leftnav_search]').each(function() {
	    var default_value = this.value;
    	$(this).focus(function() {
    	    if(this.value == default_value) {
    	        this.value = '';
    	    }
    	});
    	$(this).blur(function() {
    	    if(this.value == '') {
    	        this.value = default_value;
    	    }
    	});
	});

	
$('div[id=abuty_preview]').hide();
EOF;
    return array("javascript"=>"$javascript", "display"=>"$xhtml");
}


function get_group_from_an_id($id){
	$query = mysql_query("SELECT * FROM `media` WHERE `id` = '".$id."' LIMIT 1;");
	$result = mysql_fetch_object($query);
	return $result->group;
}

function player_details($ui, $params) {
    # Rail: /commnetivivity/tab/player/details/RECORD
	 $xhtml  = '<link rel="stylesheet" media="screen" type="text/css" href="/assets/colorpicker/css/colorpicker.css">';
	$xhtml .= '<script src="/assets/colorpicker/js/colorpicker.js" type="text/javascript"></script>'; 
	//$xhtml .= '<div id="abuty_toolbar"><strong style="text-align: center; font-size: 14px;">Once your media is uploaded. Assign it a group, and then visit this screen to customize a dedicated player.</strong></div>';
	
	$id = RECORD;
	
	$group= get_group_from_an_id(mysql_real_escape_string($id));
	
	
    $presentation = mysql_query("SELECT * FROM `abuty_presentation` WHERE `group` = '".$group."' LIMIT 1;");
	
	
	//$flash_vars = (object) $presentation->flash_vars;
	
    if ( mysql_num_rows($presentation) == 1 ) { // YES, THERE IS PRESENTATION DATA IN TABLE.
        $player = mysql_fetch_object($presentation);
        //$flash_vars = (object) $presentation->flash_vars;
		$video_extentions = $framework->get_extentions_from_mimetype("video/%");
        $results = mysql_query("SELECT * FROM `media` WHERE `group` = '".$group."';");
        if ( mysql_num_rows($results) == 1 ) {
            $record = mysql_fetch_object($results);
            $has_video = in_array($record->extention, $video_extentions) ? true : false;
            $xhtml .= '<div class="head">Note: There is only 1 file associated with this player.</div>';
        } elseif ( mysql_num_rows($results) > 1 ) {
			$xhtml .= '<div class="head" style="color: white;">Presentation uses a playlist XML file.</div>';
		}else { // 
            $xhtml .= '<div class="head">Note: This player appears to be abandoned. To use this player, you must assign media files to group "' . $group.'"</div>';
        }
		// if ( in_array($)) { $xthml .=  $ui->make_select("Stretching:", "stretching", $flash_vars->stretching, array("yes"=>"Yes", "no"=>"No")); }
        
		$flash_vars = (object) unserialize($player->flash_vars);
        $params = (object) unserialize($player->params);


       // if ( !isset($flash_vars->frontcolor) ) { $flash_vars->frontcolor = "FFFFFF"; }
       // if ( !isset($flash_vars->backcolor) ) { $flash_vars->backcolor = "000000"; }
       // if ( !isset($flash_vars->lightcolor) ) { $flash_vars->lightcolor = "FFFFFF"; }
       // if ( !isset($flash_vars->screencolor) ) { $flash_vars->screencolor = "000000"; }
		
		$xhtml .= '<ul>';
		$xhtml .= '<div class="head" style="background-image: url(/assets/abuty/ui/images/whitetransparent.png); color: #000; font-size: 15px; padding: 0px; margin: 0px; vertical-align: bottom;">Colors</div>';
        $xhtml .= '<li title="The background color of the player">'.$ui->make_input("Background",array("name"=>"backcolor", "maxlength"=>"6", "size"=>"6"), $flash_vars->backcolor).'</li>';
        $xhtml .= '<li title="The icon / text color of the player">'.$ui->make_input("Foreground", array("name"=>"frontcolor", "maxlength"=>"6", "size"=>"6"), $flash_vars->frontcolor).'</li>';
        $xhtml .= '<li title="The rollover color of the player">'.$ui->make_input("Highlight", array("name"=>"lightcolor", "maxlength"=>"6", "size"=>"6"), $flash_vars->lightcolor).'</li>';
        $xhtml .= '<li title="The screen color of the player">'.$ui->make_input("Screen", array("name"=>"screencolor","id"=>"screencolor", "maxlength"=>"6", "size"=>"6"), $flash_vars->screencolor).'</li>';
		
				$xhtml .= '<div class="head" style="background-image: url(/assets/abuty/ui/images/whitetransparent.png); color: #000; font-size: 15px; padding: 0px; margin: 0px; vertical-align: bottom;">Dimensions</div>';
		$xhtml .= '<li>Width & Height:'.$ui->make_input("",array("name"=>"width", "maxlength"=>"4", "size"=>"4"), $player->width,"px").' By '.$ui->make_input("",array("name"=>"height", "maxlength"=>"4", "size"=>"4"), $player->height,"px").'</li>';

        $xhtml .= '<li title="Horizontal & Vertical spacing">HSpace'.$ui->make_input("", array("name"=>"hspace", "size"=>"3", "maxsize"=>"3"), $params->hspace, "").' vs ';
		$xhtml .= 'VSpace'.$ui->make_input("", array("name"=>"vspace", "size"=>"3", "maxsize"=>"3"), $params->vspace, "").'</li>';

		$xhtml .= '</ul>';

		$xhtml .= '<ul>';
		$xhtml .= '<div class="head" style="background-image: url(/assets/abuty/ui/images/whitetransparent.png); color: #000; font-size: 15px; padding: 0px; margin: 0px; vertical-align: bottom;">Layout</div>';
		$xhtml .='<li title="Position of the playlist">'. $ui->make_select("Playlist", "playlist", $flash_vars->playlist, array("none"=>"none (default)","bottom"=>"bottom","over"=>"over","right"=>"right","left"=>"left","top"=>"top")) . '</li>';
        $xhtml .= '<li title="Height or width of the playlist">'.$ui->make_input("Playlist size", array("name"=>"playlistsize","id"=>"playlistsize", "maxlength"=>"6", "size"=>"6"), $flash_vars->playlistsize).'</li>';
		
		$xhtml .= '<div class="head" style="background-image: url(/assets/abuty/ui/images/whitetransparent.png); color: #000; font-size: 15px; padding: 0px; margin: 0px; vertical-align: bottom;">Behavior</div>';
		$xhtml .='<li title="Automatically start playing">'. $ui->make_select("Autostart", "autostart", $flash_vars->autostart, array("false"=>"false (default)","true"=>"true")) . '</li>';
		$xhtml .='<li title="Resize mode of display content">'. $ui->make_select("Stretching", "stretching", $flash_vars->stretching, array("uniform"=>"uniform (default)","fill"=>"fill", "exactfit"=>"exactfit", "none"=>"none")) . '</li>';
		$xhtml .='<li title="Continously repeat playback">'. $ui->make_select("Repeat", "repeat", $flash_vars->autostart, array("none"=>"none (default)","list"=>"list", "always"=>"always")) . '</li>';
		$xhtml .='<li title="Shuffle the playback order">'. $ui->make_select("Shuffle", "shuffle", $flash_vars->gshuffle, array("false"=>"false (default)","true"=>"true")) . '</li>';
		$xhtml .='<li title="Windows Transparentcy Mode">'. $ui->make_select("WMode", "wmode", $flash_vars->wmode, array("transparent"=>"Transparent (default)","Window"=>"window", "Opaque"=>"opaque")) . '</li>';


		$xhtml .='<li title="Align the player">'. $ui->make_select("Align", "align", $flash_vars->autostart, array("left"=>"left (default)","right"=>"right", "top"=>"top", "bottom"=>"bottom")) . '</li>';
		
		$xhtml .= '</ul>';

		$xhtml .= '<ul style="float: right;">';
    	$results = mysql_query("SELECT * FROM `media` WHERE `group` = '".$group."'");
	    if ( mysql_num_rows($results) > 0 ) {
			$xhtml .= '<div class="head" style="background-image: url(/assets/abuty/ui/images/greentransparent.png); color: white; font-size: 13px;">What\'s in this group?</div>';
			$video_extentions = $framework->get_extentions_from_mimetype("video/%");
			$audio_extentions = $framework->get_extentions_from_mimetype("audio/%");
			$image_extentions = $framework->get_extentions_from_mimetype("image/%");
	        while($result = mysql_fetch_object($results)) {
				if ( in_array($result->extention, $video_extentions) ) { $has_video = true;	}
				if ( in_array($result->extention, $audio_extentions) ) { $has_audio = true;	}
				if ( in_array($result->extention, $image_extentions) ) { $has_image = true;	}

    	    	$xhtml .= '<li>'.stripslashes($result->orig_filename).'</li>';
			}

			if ($has_video == true) {
				$xhtml .= '<p>Contains video file(s)</p>';
				// allow fullscreen true/false
				// stretching
			}
			

			
		//	if ($has_audio == true) { $xhtml .= '<p>Contains audio file(s)</p>'; }
		//	if ($has_images == true) { $xhtml .= '<p>Contains image file(s)</p>'; }
			
			//$has_audio = in_array($seen_extentions, $audio_extentions) ? true : false;

    	} else {
            $xhtml .=  '<li>There are no files for "'.$group.'</b>"</li>';
		}
		$xhtml .= '</ul>';

    } else {
        // No presentation data available for this group.
		$xhtml .= '<div class="head">Create a new player presentation</div>';
        $xhtml .=  "<ul>";
		$xhtml .= "<div class=\"head\" style=\"size: 24px; text-align: center;\">" . $group . "</div>";
//		$xhtml .= "This item file $filename appears in this presentations screen because it was assigned with a group called \"".$group.".";
        $xhtml .= "<li>".$ui->make_input("Activate?", array("name"=>"activate_presentation", "size"=>"3", "maxlength"=>"25"), $result->activate_presentation)."</li>";
		$xhtml .= "</ul>";
		$xhtml .= '<ul>';
		
    	$results = mysql_query("SELECT * FROM `media` WHERE `group` = '".$group."'");
	    if ( mysql_num_rows($results) > 0 ) {
			$xhtml .= '<div class="head" style="background-image: url(/assets/abuty/ui/images/greentransparent.png); color: white; font-size: 13px;">What\'s in this group?</div>';
	        while($result = mysql_fetch_object($results)) {
    	    	$xhtml .= '<li>'.stripslashes($result->orig_filename).'</li>';
			}
    	} else {
            $xhtml .=  '<li>There are no files for "'.$group.'</b>"</li>';
		}			
		$xhtml .= '</ul>';
    }
	$javascript = <<<EOF
	
$('input[name=frontcolor]').ColorPicker({
	color: '#0000ff',
	onShow: function (colpkr) {
		$(colpkr).css('z-index','2000000003').fadeIn(500);
		return false;
	},
	onHide: function (colpkr) {
		$(colpkr).fadeOut(500);
		return false;
	},
	onChange: function (hsb, hex, rgb) {
		$('#colorSelector div').css('backgroundColor', '#' + hex);
	}
});	
EOF;
//	$xhtml = '<style>#abuty_leftnav_container {height: 80px;}</style>';
	return array("xhtml"=>$xhtml, "javascript"=>"$javascript");
}

function image_details($ui, $params) {//called from details();
    $results = mysql_query("SELECT * FROM `media` WHERE `id` = '".RECORD."' LIMIT 1;");
    if ( mysql_num_rows($results) == 1 ) {
        $result = mysql_fetch_object($results);
		$xhtml .= '<div id="abuty_toolbar">';
		$xhtml .= '	<a href="/abuty/tab/media/images/preview/'.RECORD.'" id="cmspreview" class="commpreview">Show preview</a>';
		$xhtml .= '	<a href="/abuty/tab/media/images/preview/'.RECORD.'" id="cmspreviewx" class="commpreview">Hide preview</a>';
		$xhtml .= '</div>';
		$xhtml .= '<ul>';
        $xhtml .=  '<li>'.$ui->make_input("Filename:", array("name"=>"orig_filename", "maxlength"=>"25"), $result->orig_filename).'</li>';
        $xhtml .=  '<li>'.$ui->make_input("Assigned Group:", array("name"=>"group", "maxlength"=>"25"), $result->group).'</li>';
        $xhtml .=  '<li>'.$ui->make_textarea("Description:", array("name"=>"description", "rows"=>"3", "cols"=>"25"), $result->description).'</li>';
        $xhtml .= '</ul>';
		$xhtml .= '<ul id="metadata" style="overflow: hidden; max-height: 275;">';
		$xhtml .= 'Uploaded by: '.$result->owner . '';			
		
		/*$exif = exif_read_data($result->real_path.$result->real_filename, 0, true);
		foreach ($exif as $key => $section) {
    		foreach ($section as $name => $val) {
        		$xhtml .='<li>'.$key.$name. $val.'</li>';
    		}
		}
		*/
			
			$xhtml .= '</ul>';

        } else {
            $xhtml .=  "No record for " . RECORD;
        }		
		$record = RECORD;
		$javascript = <<<EOF
		resize_details();
		$(window).resize(function() {
      resize_details();
});
$('#cmspreviewx').hide();
$('#metadata').height('275');
EOF;
//	return $xhtml;
	return array("xhtml"=>"$xhtml", "javascript"=>"$javascript", "preview"=>"Getting preview...");
}

function avdetails($ui, $params) { //called from details();
    $results = mysql_query("SELECT * FROM `media` WHERE `id` = '".RECORD."' LIMIT 1;");
    if ( mysql_num_rows($results) == 1 ) {
            $result = mysql_fetch_object($results);
			$xhtml .= '<div id="abuty_toolbar">';
			$xhtml .= '	<a href="/abuty/tab/media/'.SCREEN.'/preview/'.RECORD.'" id="cmspreview" class="commpreview">Run in media player</a>';
			$xhtml .= '	<a href="/abuty/tab/media/'.SCREEN.'/preview/'.RECORD.'" id="cmspreviewx" class="commpreview">Hide media player</a>';
			$xhtml .= '</div>';

			$xhtml .= '<ul style="width: auto;">';
            $xhtml .= '<li>'.$ui->make_input("Filename:", array("name"=>"orig_filename", "maxlength"=>"25"), stripslashes($result->orig_filename)).'</li>';
            $xhtml .= '<li>'.$ui->make_input("Assigned Group:", array("name"=>"group", "maxlength"=>"25"), $result->group).'</li>';
            $xhtml .= '<li>'.$ui->make_textarea("Description:", array("name"=>"description", "rows"=>"3", "cols"=>"25"), $result->description).'</li>';
			
			
		$xhtml .='<li title="Windows Transparentcy Mode">'. $ui->make_select("WMode", "wmode", $flash_vars->wmode, array("transparent"=>"Transparent (default)","Window"=>"window", "Opaque"=>"opaque")) . '</li>';
			
            $xhtml .= '</ul>';



			$xhtml .= '<ul style="width: 200px;">';
		    $xhtml .= '<li">Uploaded by: '.$result->owner.'</li>';
			$extention = $result->extention;
			$mimetype = $framework->get_mimetype_from_extention("$extention");
			//$xhtml .= '<li>Extention: '.$result->extention.'</li>';
			$xhtml .= '<li>Associated Mimetype: '.$mimetype.'</li>';
			
		    $xhtml .= '</ul>';


        } else {
            $xhtml .= "No record for " . RECORD;
        }
		
		
		
		$record = RECORD;
		$javascript = <<<EOF
		resize_details();
		$(window).resize(function() {
      resize_details();
});

EOF;

	return array("xhtml"=>"$xhtml", "javascript"=>"$javascript");	
}

function avpanel($ui, $params) { // formally the audio and video functions. Now both in one.
	$record = RECORD;
	$panel = PANEL;
	if ( ACTION != "" ) {
		// use switch(ACTION) here.
		switch(ACTION) {
			case 'details':
				$screen_function = ACTION;
				$screen = (object) $screen_function($ui, $params);
				return array("details"=>$screen->xhtml, "javascript"=>$screen->javascript);			
			break;
			case 'preview':
				$screen_function = ACTION;
				$screen = $screen_function($ui, $params);
				echo $screen;
			break;
		}
		exit;
	}
	$extentions = $framework->get_extentions_from_mimetype(SCREEN."/%");	// function name is same as screen.??
	$xhtml .= '<div id="abuty_leftnav_container">';
	if (SCREEN == 'search') {
		$sql = ( defined('RECORD') ) ? "SELECT * FROM `abuty_media` WHERE `extention` IN('".implode("', '", $extentions)."') AND `orig_filename` LIKE '%".RECORD."%' LIMIT 15;"
									 : "SELECT * FROM `abuty_media` WHERE `extention` IN('".implode("', '", $extentions)."') LIMIT 15;";
	} else {	
		$xhtml .= '<div id="abuty_leftnav_search"><input name="abuty_leftnav_search" value="Search"></div>';
	    $xhtml .= '<div id="abuty_leftnav" class="scrollable vertical"><div class="items">'."\n";
		$sql = "SELECT * FROM `abuty_media` WHERE `extention` IN('".implode("', '", $extentions)."') ";
	}
	$results = mysql_query($sql);
	$counter = 0;
    if ( mysql_num_rows($results) > 0 ) {
        while($result = mysql_fetch_object($results)) {
            if (file_exists(PATH."/media/".$result->real_filename) ) {
				if ( $counter == 0  ) { $xhtml .= "\t".'<div>'."\n"; }
				$title = str_replace('.'.$result->extention, "", $result->orig_filename);
				//$title = $framework->wordtruncate($result->orig_filename, 5, ".mp3", "...");
				$xhtml .= "\t\t".'<div class="item"><a id="'.$result->id.'" href="'.$result->real_filename.'" title="'.$result->orig_filename.'">'.stripslashes($title).'</a></div>'."\n";
				if ( $counter > 3 ) { $xhtml .= "\t".'</div>'."\n\t".'<div>'."\n"; }
				if ( $counter == 4 ) { $counter = 0; }
            } else {
                mysql_query("DELETE FROM `abuty_media` WHERE `real_filename`='".$result->real_filename."' LIMIT 1;");
            }
			$counter++;
        }
    } else {
        $xhtml .= "No results available for ".RECORD;
    }
	if (SCREEN == 'search') {
		$javascript = '$(".scrollable").scrollable({ vertical: true, mousewheel: true });';
		return array("leftnav"=>$xhtml, "sql"=>$sql, "javascript"=>"$javascript");
		exit;
	}
    $xhtml .= "\t".'</div>'."\n"; //leftnav
    $xhtml .= "\t".'</div>'."\n"; //leftnav_container
    $xhtml .= <<<EOF
</div>
</div>
<div id="abuty_windows">
	<div id="abuty_details">Please select a file to begin working.</div>
	<div id="abuty_preview"></div>
</div>
EOF;
//$xhtml .= '</textarea>';
$javascript .= <<<EOF
	$('div[id=abuty_actions]').empty();
	$('div[id=abuty_actions]').html('<a class="prev">&laquo; Back</a><a class="next">More results &raquo;</a>');
	$(".scrollable").scrollable({ vertical: true, mousewheel: true });
	$('input[name=abuty_leftnav_search]').each(function() {
	    var default_value = this.value;
    	$(this).focus(function() {
    	    if(this.value == default_value) {
    	        this.value = '';
    	    }
    	});
    	$(this).blur(function() {
    	    if(this.value == '') {
    	        this.value = default_value;
    	    }
    	});
	});
$('div[id=abuty_preview]').hide();
EOF;
    return array("javascript"=>"$javascript", "display"=>"$xhtml");
}



function files_by_association($tag, $query) {
	$results = mysql_query("SELECT * FROM `abuty_media` WHERE `".$tag."` = '".$query."';");
	$files = array();
	if (mysql_num_rows($results)>0) {
		$files[] = $results->orig_filename;
	} else {
		return array();
	}
	return $files;
}

function streams($ui, $params) {
    return array("display"=>"<div id=\"white_form\"><p>Media Streams has not been created yet.</p></div>");
}

function help($ui, $params) {
	return array("display"=>"<ul><li>Help menu not yet available.</li></ul>");
}

function galleries($ui, $params) {
	return array("display"=>"<div id=\"abuty_window\"><ul>panel not available</ul></div>");
}

function images($ui, $params) {
	$record = RECORD;
	if ( ACTION != "" ) {
		// use switch(ACTION) here.
		switch(ACTION) {
			case 'details':
				$screen_function = ACTION;
				$screen = (object) $screen_function($ui, $params);
				return array("details"=>$screen->xhtml, "javascript"=>$screen->javascript);			
			break;
			case 'preview':
				$screen_function = ACTION;
				$screen = $screen_function($framework, $ui, $params);
				echo $screen;
			break;
		}
		exit;
	}
$javascript .=<<<EOF

EOF;

$extentions = get_extentions_from_mimetype("image/%");


	$xhtml .= '<div id="abuty_leftnav_container">';
	if (SCREEN == 'search') {
		$sql = ( defined('RECORD') ) ? "SELECT * FROM `media` WHERE `extention` IN('".implode("', '", $extentions)."') AND `orig_filename` LIKE '%".RECORD."%' LIMIT 15;"
									 : "SELECT * FROM `media` WHERE `extention` IN('".implode("', '", $extentions)."') LIMIT 15;";
	} else {	
		$xhtml .= '<div id="abuty_leftnav_search"><input name="abuty_leftnav_search" value="Search"></div>';
	    $xhtml .= '<div id="abuty_leftnav" class="scrollable vertical"><div class="items">'."\n";
		$sql = "SELECT * FROM `media` WHERE `extention` IN('".implode("', '", $extentions)."') ";
	}

	
	$results = mysql_query("$sql");
	$counter = 0;
    if ( mysql_num_rows($results) > 0 ) {
        while($result = mysql_fetch_object($results)) {
            if (file_exists(PATH."/media/".$result->real_filename) ) {
				if ( $counter == 0  ) { $xhtml .= "\t".'<div>'."\n"; }
				$title = str_replace('.'.$result->extention, "", $result->orig_filename);
				//$title = $framework->wordtruncate($result->orig_filename, 5, ".mp3", "...");
				$xhtml .= "\t\t".'<div class="item"><a id="'.$result->id.'" href="'.$result->real_filename.'" title="'.$result->orig_filename.'">'.stripslashes($title).'</a></div>'."\n";
				if ( $counter > 3 ) { $xhtml .= "\t".'</div>'."\n\t".'<div>'."\n"; }
				if ( $counter == 4 ) { $counter = 0; }
            } else {
                mysql_query("DELETE FROM `media` WHERE `real_filename`='".$result->real_filename."' LIMIT 1;");
            }
			$counter++;
        }
    } else {
        $xhtml .= "No results available for ".RECORD;
    }
	if (SCREEN == 'search') {
		$javascript = '$(".scrollable").scrollable({ vertical: true, mousewheel: true });';
		return array("leftnav"=>$xhtml, "sql"=>$sql, "javascript"=>"$javascript");
		exit;
	}
    $xhtml .= "\t".'</div>'."\n"; //leftnav
    $xhtml .= "\t".'</div>'."\n"; //leftnav_container
    $xhtml .= <<<EOF
</div>
</div>
<div id="abuty_windows">
	<div id="abuty_details">Please select an image file to begin working.</div>
	<div id="abuty_preview"></div>
</div>
EOF;
//$xhtml .= '</textarea>';
$javascript .= <<<EOF
	$('div[id=abuty_actions]').empty();
	$('div[id=abuty_actions]').html('<a class="prev">&laquo; Back</a><a class="next">More images &raquo;</a>');
	$(".scrollable").scrollable({ vertical: true, mousewheel: true });
	$('input[name=abuty_leftnav_search]').each(function() {
	    var default_value = this.value;
    	$(this).focus(function() {
    	    if(this.value == default_value) {
    	        this.value = '';
    	    }
    	});
    	$(this).blur(function() {
    	    if(this.value == '') {
    	        this.value = default_value;
    	    }
    	});
	});
$('div[id=abuty_preview]').show();
EOF;


    return array("javascript"=>"$javascript", "display"=>"$xhtml");
}

function feeds($ui, $params) {
		$xhtml .= '<ul>';
        $xhtml .= '<li>Google News (fetched ever 5 minutes via CRON TAB)</li>';
        $xhtml .= '<li>Local Headlines (fetched every 6 hours via CRON TAB)</li>';
		$xhtml .= '</ul>';	
    return array("display"=>"$xhtml");
	
}


function media_ajax_search($ui, $params) {
	return $results;
}







function images_cms_preview($ui, $params) {
    $query = mysql_query("SELECT * FROM `abuty_media` WHERE `id` = '".RECORD."' LIMIT 1;");
    if ( mysql_num_rows($query) == 1 ) {
        $record = (object) mysql_fetch_assoc($query);
        $image = new Resize_Image;
        //$image->new_width = ;
        $image->image_to_resize = PATH."/media/".$record->real_filename; // Full Path to the file
        if(!file_exists($image->image_to_resize)) {
            //exit("File ".$image->image_to_resize." does not exist.");
        }
        $info = GetImageSize($image->image_to_resize);
        if(empty($info)) {
            //exit("The file ".$image->image_to_resize." doesn't seem to be an image.");
        }
        $width = $info[0];
        $height = $info[1];
        $mime = $info['mime'];
        //echo $width . "x" . $height;
        $image->ratio = true; // Keep Aspect Ratio?
		
        if ( $height > 284 ) {
            $image->new_height = 284; $class = "fullheight";
        } else {
            $image->new_height = $height; $class = "underheight";
        }
        $image->image_to_resize;
        // Name of the new image (optional) - If it's not set a new will be added automatically
        $image->new_image_name = $record->id;
        /* Path where the new image should be saved. If it's not set the script will output the image without saving it */







//$base64_encoded = base64_encode($final_image);

        $image->save_folder = PATH.'/media/cmspreviewcache/';
        $process = (object) $image->resize();

        $xhtml = '<img src="/media/cmspreviewcache/'.$process->name.'" width="'.$image->width.'" height="'.$image->new_height.'" class="'.$class.'">';
/*		
		
		$xhtml = '<img src="data:image/png;base64asdf," width="'.$image->width.'" height="'.$image->new_height.'" class="'.$class.'">';
*/        
		if($process->result && $image->save_folder) {
            //echo 'The new image ('.$process->new_file_path.') has been saved.';
        }
    } else {
        $xhtml = "No image available for :" . RECORD;
    }
	//$width = $image->width || 0;
		
$xhtml .= <<<EOF
<script>
$(document).ready(function() {
resize_details();

});
</script>
EOF;
    return $xhtml; // Add some cool js for manipulating images, etc.
}


class Resize_Image {
	var $image_to_resize;
	var $new_width;
	var $new_height;
	var $ratio;
	var $new_image_name;
	var $save_folder;
	function resize() {
	    if(!file_exists($this->image_to_resize)) {
	        exit("File ".$this->image_to_resize." does not exist.");
	    }
	    $info = GetImageSize($this->image_to_resize);
	    if(empty($info)) {
	        exit("The file ".$this->image_to_resize." doesn't seem to be an image.");
	    }
	    $width = $info[0];
	    $height = $info[1];
	    $mime = $info['mime'];
	/*
	Keep Aspect Ratio?
	Improved, thanks to Larry
	*/
	if($this->ratio) {
	    // if preserving the ratio, only new width or new height
	    // is used in the computation. if both
	    // are set, use width
	    if (isset($this->new_width)) {
	        $factor = (float)$this->new_width / (float)$width;
	        $this->new_height = $factor * $height;
	    } else if (isset($this->new_height)) {
	        $factor = (float)$this->new_height / (float)$height;
	        $this->new_width = $factor * $width;
	    } else {
	        exit("neither new height or new width has been set");
	    }
	}
	// What sort of image?
	$type = substr(strrchr($mime, '/'), 1);
	switch ($type) {
		case 'jpeg':
		    $image_create_func = 'ImageCreateFromJPEG';
		    $image_save_func = 'ImageJPEG';
			$new_image_ext = 'jpg';
		    break;
		case 'png':
		    $image_create_func = 'ImageCreateFromPNG';
		    $image_save_func = 'ImagePNG';
			$new_image_ext = 'png';
		    break;
		case 'bmp':
		    $image_create_func = 'ImageCreateFromBMP';
		    $image_save_func = 'ImageBMP';
			$new_image_ext = 'bmp';
		    break;
		case 'gif':
		    $image_create_func = 'ImageCreateFromGIF';
		    $image_save_func = 'ImageGIF';
			$new_image_ext = 'gif';
		    break;
		case 'vnd.wap.wbmp':
		    $image_create_func = 'ImageCreateFromWBMP';
		    $image_save_func = 'ImageWBMP';
			$new_image_ext = 'bmp';
		    break;
		case 'xbm':
		    $image_create_func = 'ImageCreateFromXBM';
		    $image_save_func = 'ImageXBM';
			$new_image_ext = 'xbm';
		    break;
		default:
			$image_create_func = 'ImageCreateFromJPEG';
		    $image_save_func = 'ImageJPEG';
			$new_image_ext = 'jpg';
		}
		// New Image
		$image_c = ImageCreateTrueColor($this->new_width, $this->new_height);
		$new_image = $image_create_func($this->image_to_resize);
		ImageCopyResampled($image_c, $new_image, 0, 0, 0, 0, $this->new_width, $this->new_height, $width, $height);
		    if($this->save_folder) {
			    if($this->new_image_name) {
		          $new_name = $this->new_image_name.'.'.$new_image_ext;
	      		} else {
	           		$new_name = $this->new_thumb_name( basename($this->image_to_resize) ).'_resized.'.$new_image_ext;
	      		}
	      		$save_path = $this->save_folder.$new_name;
	      	} else {
	        	/* Show the image without saving it to a folder */
	        	header("Content-Type: ".$mime);
	    		$image_save_func($image_c);
				$save_path = '';
			}
			$process = $image_save_func($image_c, $save_path);
			return array('result' => $process, 'new_file_path' => $save_path, "name"=>$new_name);
		}
		function new_thumb_name($filename) {
	    	$string = trim($filename);
	        $string = strtolower($string);
	        $string = trim(ereg_replace("[^ A-Za-z0-9_]", " ", $string));
	        $string = ereg_replace("[ tnr]+", "_", $string);
		    $string = str_replace(" ", '_', $string);
	        $string = ereg_replace("[ _]+", "_", $string);
            return $string;
    	}
}





?>