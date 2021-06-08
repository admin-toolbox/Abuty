<?php

function myEach(&$arr) {
    $key = key($arr);
    $result = ($key === null) ? false : [$key, current($arr), 'key' => $key, 'value' => current($arr)];
    next($arr);
    return $result;
}

class screen {
    function make_input($label, $elements, $default_value, $spantext="") {
	if ($label != "") {
		$xhtml  = '<label for="'.$elements['name'].'">'.$label.'</label>';
	}
	$xhtml .= '<input ';
	while ( list ( $name, $value ) = myEach ( $elements ) ) {
		$xhtml .= $name . '="' . $value . '" ';
	}
	$xhtml .= 'value="'.$default_value.'">';
		if ($spantext != "") {
			$xhtml .= '<span>'.$spantext.'</span>';	
		}
        return $xhtml;
    }
    function make_checkbox($label, $name, $default_value) {
		$xhtml  = '<label for="'.$name.'"">'.$label.'</label>';
		$xhtml .= '<input type="checkbox" name="'.$name.'"';
		$xhtml .= ($default_value != "") ? ' checked="yes"' : '';
		
		if ( $default_value != "checked" ) {
			
		}
		
		$xhtml .= 'value="'.$default_value.'">';
		return $xhtml;
    }
	
    function make_select($label="", $field_name, $default_value, $options) {

		if ($label != ""){
			$xhtml  = '<label for="'.$field_name.'">'.$label.'</label>' . "\n";
		}
		$xhtml .= '<select name="'. $field_name. '">' . "\n";
		while ( list($option_value, $option_name ) = myEach ( $options ) ) {
			if ( $default_value == $option_value ) {
				$xhtml .= '<option value="'.$option_value.'" selected>' . $options[$option_value]. '</option>' . "\n";
			} else {
				$xhtml .= '<option value="'.$option_value.'">' . $options[$option_value] .  '</option>' . "\n";
			}
		}
		$xhtml .= '</select>'."\n";
        return $xhtml;
    }
    function make_parent_id_selector($label, $parent_id) {
		$xhtml  = '<label for="'.$parent_id.'">'.$label.'</label>' . "\n";
		$results = mysql_query("SELECT virtual_path, page_title, parent_id FROM `commnetivity_content` WHERE 1");
		$xhtml .= '<select name="'.parent_id.'">';
		while($result=mysql_fetch_array($results)){
		if ( $result['parent_id'] == VPATH ) {
			$xhtml .= "<option value=\"$result[virtual_path]\" selected=\"selected\">** top **</option>\n";
		} else {
			if ( $result['parent_id'] == "$parent_id" ) {
				$xhtml .= "<option value=\"$result[virtual_path]\" selected=\"selected\">$result[page_title]</option>\n";
			} else {
				$xhtml .= "<option value=\"$result[virtual_path]\">$result[page_title]</option>\n";
			}
		}
	}
	$xhtml .= "</select>";
        return $xhtml;
    }
    function make_textarea($label, $elements, $default_value) {
		$xhtml = '<label for="'.$elements['name'].'">'.$label.'</label>' . "\n";
		$xhtml .= '<textarea id="textarea" ';
		while ( list ( $name, $value ) = myEach ( $elements ) ) {
			$xhtml .= " ". $name . '="' . $value . '"';
		}
		$xhtml .= '>'.$default_value.'</textarea>'."\n\n";
        return $xhtml;
    }
	function make_audio_player($framework, $ui, $params) {
	       $results = mysql_query("SELECT * FROM `commnetivity_media` WHERE `id` = '".RECORD."' LIMIT 1;");
	   	if ( mysql_num_rows($results) ==1 ) {
	    	$result = mysql_fetch_object($results);
	    	$resource = '/media/' . $result->real_filename;
	    	$filename = $result->id;
			$player_id = "Audio_". hash('md5', $result->real_filename);
			$xhtml .= <<<EOF
	<script src="/assets/javascripts/swfobject.js" type="text/javascript"></script> 
	<player id="$player_id"></player>
    <p style="margin: 0px; padding: 0px; font-size: 12px; color: #900; text-align: center;">*player is for preview purposes only.</p>
    <p style="margin: 0px; padding: 0px; font-size: 11px; color: #033; text-align: center;">Want this in your page?<br />Drag & drop it into the editor!</p>
    <p style="margin: 0px; padding: 0px; font-size: 11px; color: #033; text-align: center;">Or <a href="#copy">click here</a> to copy the code to clipboard.</p>
	<script>
		var flashvars = {
			'file':               '$resource',
			'title':              'Some Title',
			'image': 'http://t0.gstatic.com/images?q=tbn:ANd9GcT5rJAA2X4Wb066L-4E2rnrUSAWsj9aoeUsswj4bRLUtm2FHkRDug',
			'type':               'sound',
        	'autostart':          'true',
			'plugins': 'spectrumvisualizer-1',
			'skin': '/assets/jwplayer/glow.zip',
			'abouttext': 'Commnetivity&aboutlink=http://www.commnetivity.com'
		};
		var params = {
			'allowfullscreen':    'false',
			'allowscriptaccess':  'always',
			'WMODE': 'transparent'
		};
        swfobject.embedSWF('/assets/player.swf', '$player_id', '275', '220', '9.0.124', false, flashvars, params);
		$('a[id=cmspreviewx]').show();
		resize_details();
	</script>
    
EOF;
   } else {
       $xhtml .= "No audio file available.";
   }
	return $xhtml;
}

function jwplayer_buildup($name="", $vars=array()) {
    return str_replace("\n'};", "'\n};", str_replace("',","',\n\t", str_replace("={", "= {\n\t", str_replace("\/", "/", 'var '.$name.' =' . str_replace('"', "'", json_encode($vars)) . ';'))));
}

function make_video_player($framework, $ui, $params) {
    $results = mysql_query("SELECT * FROM `commnetivity_media` WHERE `id` = '".RECORD."' LIMIT 1;");
    if ( mysql_num_rows($results) == 1 ) {
    $result = mysql_fetch_object($results);
    $resource = '/media/' . $result->real_filename;
    $filename = $result->id;
    $player_id = "Video_". hash('md5', $result->real_filename);
    $panel = PANEL;
$xhtml .= <<<EOF
	<div id="$player_id"></div>
    <p style="margin: 0px; padding: 0px; font-size: 12px; color: #900; text-align: center;">*player is for preview purposes only.</p>
    <p style="margin: 0px; padding: 0px; font-size: 11px; color: #033; text-align: center;">Want this in your page?<br />Drag & drop it into the editor!</p>
    <p style="margin: 0px; padding: 0px; font-size: 11px; color: #033; text-align: center;">Or <a href="#copy">click here</a> to copy the code to clipboard.</p>
    <script>
    var flashvars = {
		'file':               '$resource',
		'type':               'video',
		'frontcolor':         'ffffff',  // text & icons                  (green)
		'backcolor':          '747b5a',  // playlist background           (blue)
    	'lightcolor':         'ffffff',  // selected text/track highlight (red)
        'screencolor':        'ffffff',  // screen background             (yellow)
        'autostart':          'false',
		'image': 'http://www.legalmoviesdownloads.com/film-reel.jpg',
		'abouttext': 'Commnetivity&aboutlink=http://www.commnetivity.com'
	};
	var params = {
    	'allowfullscreen':    'true',
        'allowscriptaccess':  'always',
        'WMODE': 'transparent'
	};
    swfobject.embedSWF('/assets/player.swf', '$player_id', '220', '220', '9.0.124', false, flashvars, params);
    resize_details();
    </script>
EOF;
	} else {
		$xhtml .= "No audio file available.";
	}
	return $xhtml;
}

		
	function callback($type, $messege) {
		if ( $type == 'error' ) {
			return "<div class=\"rpc_msg_err\">$messege</div>";
		} elseif ( $type == 'warn' ){
			return "<div class=\"rpc_msg_warn\">$messege</div>";
		} else {
			return "<div class=\"rpc_msg_ok\">$messege</div>";
		}
	}



}
?>
