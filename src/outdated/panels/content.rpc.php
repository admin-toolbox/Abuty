<?php

/* CONTENT 

$security = unserialize($content->security);
$meta = unserialize($content->meta_data);
$theme = unserialize($content->theme);
$bigfoot_content = array("nav_title", "bookmarkability", "enable_in_navigation", "internal_path", "internal_notes", "page_title", "parent_id", "ssl_required", "auth_required", "keywords", "description", "template" );
//// Used for serialization.$security_fields_available = array("ssl_required","auth_required");
$meta_fields_available = array("keywords", "description");
$theme_fields_available = array("template");

/* */

function vpath_activation($ui) {
	global $f3;
	global $dbh;
	global $auth;	
    $fixed_parent_parts = explode("/", dirname($f3->get("requested_vpath")));
    array_pop($fixed_parent_parts);
    $fixed_parent = strtolower(implode("/", $fixed_parent_parts)."/");
	if ( $f3->get("POST.value") != "" && $f3->get("POST.value") == "yes" ) {
		try {
			$sth = $dbh->prepare('SELECT weight FROM content WHERE pid = :pid');
			$sth->execute(["pid"=>$fixed_parent]);
			$sth->setFetchMode(PDO::FETCH_ASSOC);
			$weights = $sth->fetchAll();
			$next_weight = max(array_values($weights[0]))+0.05;
			$sql = "INSERT INTO `content` (`virtual_path`, `date_recorded`, `pid`, `weight`) VALUES ('".$f3->get("requested_vpath")."', '".date('Y-m-d H:i:s')."', '".$fixed_parent."', '".$next_weight."');";	
			$sth = $dbh->prepare($sql); 
			$result = $sth->execute();
			$javascript = '		$(\'title\').html("Untitled New Document");'."\n";
			$javascript .= '	$(\'a[id=editor]\').html("<a id=\"editor\" class=\"edit_link\" href=\"'.$f3->get("requested_vpath").'\">Use an editor</a></li>"); ';
			$javascript .= '	$(\'#content\').html("<h3>Awesome!</h3><p>You may now edit this document!</p>");'."\n";
			return array("announce"=>$ui->callback($response[0], "Activated. You may now use the WSIWYG Editor to create your page."), "screen"=>$f3->get("tmpuuid")."/tab/content/basics", "javascript"=>"$javascript");
		} catch(Exception $e) {
			return array("announce"=>$ui->callback($response[0], "SQL Error: ".$e->getCode(). " (".$e->getMessage().")"));
		}
	} else {
		return array("announce"=>$ui->callback($response[0], "You must type \"yes\" to activate this virtual path. $fixed_parent"));
	}
}

function page_title() {
	global $f3;
	global $dbh;
	global $ui;
	if (  $f3->get("POST.value") == "erase" || $f3->get("POST.remove") || $f3->get("POST.value") == "delete" ) {
		try{
			$numRows = $dbh->exec("DELETE FROM `content` WHERE `virtual_path` = '".$f3->get("requested_vpath")."'");
			$response = ($numRows > 0) ? array("ok", "This page has been removed but is still in revision history.") : array("error", "Content resource could not be removed.");
			 if ( $callback['0'] == "ok" ) {
				$javascript  = '$(\'title\').html("Removed!!!");'."\n";
				$javascript .= '$(\'a[id=editor]\').html("<a id=\"editor\" href=\"'.$f3->get("requested_vpath").'\"></a>"); ';
				$javascript .= '$(\'div[id=content]\').html("<h3>Removed!!!</h3>\n<p>This virtual path and content resource has been removed from the content table. Any revisions will still available for re-publishing or review at any time.!</p>");'."\n";
				$javascript .= '$(\'ul[id=control_panel_subnav_tabs]\').fadeOut(300, function(){$(this).html("")});';
			}
			return array(
				  "announce"=>$ui->callback($response['0'], $response['1'])
				, "javascript"=>"$javascript"
			);
		} catch(PDOException $e){
			echo 'Error : '.$e->getMessage();
		}
	} else {
		$response = _replace_field();
    	if ( $response['0'] == "ok" ) {
			$javascript = ( strlen($f3->get("POST.value")) == 0 ) ? "$('title').html(\"Untitled Document\");" : "$('title').html(\"".$f3->get("POST.value")."\");";
    	}
		return array(
			 "announce" => $ui->callback($response['0'], $response['1'])
			,"javascript" => "$javascript"
		);
	}
}

function enable_in_navigation($ui) {
	global $f3;
	$response = _replace_field("enable_in_navigation", $f3->get("POST.value"));
	return array("announce"=>$ui->callback($response[0], $response[1]));	
}	

function keywords($ui) {
	global $f3;
	$response = _replace_serialized_element("keywords", "meta_data", $f3->get("POST.value"));
	return array("announce"=>$ui->callback($response[0], $response[1]));
}

function description($ui) {
	$response = _replace_serialized_element("description", "meta_data", $f3->get("POST.value"));
	return array("announce"=>$ui->callback($response[0], $response[1]));	
}

function ssl_required($ui) {
	global $f3;
	$response = _replace_serialized_element("ssl_required", "security", $f3->get("POST.value"));
	return array("announce"=>$ui->callback($response[0], $response[1]));	
}

function template($ui) {
	global $f3;
	$response = _replace_serialized_element("template", "theme", $f3->get("POST.value"));
	return array("announce"=>$ui->callback($response[0], $response[1]));	
}

function left($ui) {
	$response = _update_navigation($f3->get("POST.name"), $f3->get("POST.value"));
	return array("announce"=>$ui->callback($response[0], $response[1]));	
}

function right($ui) {
	global $f3;
	$response = _update_navigation($f3->get("POST.name"), $f3->get("POST.value"));
	return array("announce"=>$ui->callback($response[0], $response[1]));	
}

function top($ui) {
	global $f3;
	$response = _update_navigation($f3->get("POST.name"), $f3->get("POST.value"));
	return array("announce"=>$ui->callback($response[0], $response[1].$params->value));	
}

function bottom($ui) {
	global $f3;
	$response = _update_navigation($f3->get("POST.name"), $f3->get("POST.value"));
	return array("announce"=>$ui->callback($response[0], $response[1]));	
}

function internal_notes() {
	$response = _replace_field();
	return array("announce"=>$ui->callback($response[0], $response[1]));
}

function internal_path() {
	global $f3;
	$vpath = $f3->get("requested_vpath");
	$response = _replace_field();
	$uuid = $f3->get("tmpuuid");
	if ($f3->get("POST.value") == "") {
		$javascript = <<<EOF
		$.ajax({
			  url: "/$uuid/content?vpath=$vpath", type: "GET", cache: false, dataType: "json"
			, success: function(response) {
				$('#content').html(response.display);
			}
		});
EOF;
	} else {
		$javascript = <<<EOF
		$.ajax({
			  url: "/$uuid/script_content?internal_path=$value"
			, type: "GET", cache: false, dataType: "html"
			, success: function(results) {
				var content = results;
				$('#content').html(content);
				var content = null;
			}
		});
EOF;
	}
	return array("announce"=>$ui->callback($response[0], $response[1]), "javascript"=>$javascript);
}

function nav_title() {
	$response = _replace_field();
	return array("announce"=>$ui->callback($response[0], $response[1]));	
}

function _replace_field() {
	global $f3;
	global $dbh;
	global $ui;
	$sth = $dbh->prepare('SELECT * FROM `content` WHERE `virtual_path` = :virtual_path LIMIT 1');
	$sth->bindValue(":virtual_path", $f3->get("requested_vpath"), PDO::PARAM_STR);
	$sth->execute();
	$sth->setFetchMode(PDO::FETCH_OBJ);
	$content = $sth->fetch();
	if( $sth->rowCount() > 0 ){
		try {
			$sth = $dbh->prepare("UPDATE `content` SET `".$f3->get("POST.name")."` = '".$f3->get("POST.value")."' WHERE `virtual_path` = '".$f3->get("requested_vpath")."'");  
			$result = $sth->execute();
			return array("ok", "Updated field named ".$f3->get("POST.name")."with a new value.");
		} catch(Exception $e) {
			return array("error", "There was an error with the value you provided.");
		}
	} else {
		return array("error", "You must first create a page.");
	}
}

function _update_navigation($name, $value) {
	$sql = "SELECT * FROM `bigfoot_navigation` WHERE `virtual_path`='" . VPATH . "'";
	$result = mysql_query($sql);
	if(mysql_num_rows($result) > 0 ){
		$sql = "UPDATE `bigfoot_navigation` SET `".$name."`='".$value."' WHERE `virtual_path`='".VPATH."'";
		$result = mysql_query($sql) ;
		if(mysql_affected_rows() > 0){
			$response = array("ok", "The $name navigation parameter has been changed.");
		} else {
			$response = array("error", "No records were updated.");
		}
	} else {
		$result = mysql_query("INSERT INTO `bigfoot_navigation` (`virtual_path`) VALUES ('".VPATH."');");
		$sql = "UPDATE `bigfoot_navigation` SET `".$name."`='".$value."' WHERE `virtual_path`='".VPATH."'";
		$result = mysql_query($sql) ;
		if(mysql_affected_rows() > 0){
			$response = array("ok", "The $name navigation parameter has been created.");
		} else {
			$response = array("error", "Wow... that is one hell of a fail on bigfoot's part. Must be a big on your Windows&trade;.");
		}
	}
	return $response;
}

function _replace_serialized_element($field, $in, $value) {
	global $f3;
	$sql = "SELECT $in FROM `bigfoot_content` WHERE `virtual_path`='".$f3->get("requested_vpath")."'";
	$result = mysql_query($sql);
	$record = mysql_fetch_assoc($result);
	if(mysql_num_rows($result) > 0 ){
		$unpacked = unserialize($record[$in]);
		if ( array($unpacked) ) {
			if ( strlen($value) > 0 ) {
				$unpacked[$field] = "$value";
			} else {
				if ($field != $value) {
					
				} else {
					unset($unpacked[$field]);
				}
				
			}
		} else { // No array setup.
			if ( strlen($value) > 0 ) {
				$unpacked = array("$field"=>"$value");
			} else {
				$unpacked = array();
			}
		}
		$packed = serialize($unpacked);
		$sql = "UPDATE `bigfoot_content` SET `".$in."`='".$packed."' WHERE `virtual_path`='".VPATH."';";
		$result = mysql_query($sql);
		$rows_affected = mysql_affected_rows();
		if(mysql_affected_rows() > 0){
			$response = array("okay", ucfirst($in) . " updated.");
		} else {
			$response = array("error", ucfirst($in) . " not updated.");
		}
	} else {
		if ( strlen($value) > 0 ) {
			$unpacked = array("$field"=>"$value");
		} else {
			$unpacked = array();
		}
		$packed = serialize($unpacked);
		$sql = "UPDATE `bigfoot_content` SET `".$in."`='".$packed."' WHERE `virtual_path`='".VPATH."';";
		$result = mysql_query($sql);
		if(mysql_num_rows($result) > 0){
			$response = array("okay", ucfirst($in) . " updated.");
		} else {
			$response = array("error", ucfirst($in) . " not updated.");
		}
	}
	return $response;
}



?>