<?php

global $dbh;
global $f3;
global $ui;

$sth = $dbh->prepare('SELECT * FROM `content` WHERE `virtual_path` LIKE :virtual_path LIMIT 1');
$sth->bindValue(":virtual_path", $f3->get("requested_vpath"), PDO::PARAM_STR);
$sth->execute();
$sth->setFetchMode(PDO::FETCH_OBJ);
$content = $sth->fetch();

if ( in_array("update", $params)  ) {
	if ( isset($content->virtual_path) ) { // Ensure there is indeed a record before attempting to update.
		$updated_content = trim(str_replace("\\r\\n", "\r\n", $f3->get("REQUEST.content")));
		$updated_js      = trim(str_replace("\\r\\n", "\r\n", $f3->get("REQUEST.js")));
		$updated_css     = trim(str_replace("\\r\\n", "\r\n", $f3->get("REQUEST.css")));

		if (  strlen($updated_content) > 0 ) {
			try {
				$dbh->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
				$sql = "UPDATE `content` SET `content` = :content WHERE `content`.`id` = :id";
				$stmt = $dbh->prepare($sql);
				$stmt->execute(["id"=>$content->id, "content"=>addslashes($updated_content)]);
				 if( $stmt->rowCount() > 0 ) {
					$callback = array("ok", "The changes are now live.");
					//return;
				 } else {
					$callback = array("error", "No changes have been made.");
				 }

			} catch (PDOException $e) {
				$callback = array("error", $e->getCode() . ": ".$e->getMessage());
			}
			if ( $callback['0'] == "ok" ) {
				// $javascript = ( strlen($f3->get("POST.value")) == 0 )
				//	? "$('title').html(\"Untitled Document\");"
				//	: "$('title').html(\"".$f3->get("POST.value")."\");";
			}
			echo json_encode(array(
				 "announce" => $ui->callback($callback['0'], $callback['1'])
				// ,"javascript" => "$javascript"
			));
			exit;
		}
		
		if ( strlen($updated_js) > 0 ) {
			$sql = "UPDATE `content` SET `javascript`='" . addslashes($updated_js) . "' WHERE `virtual_path`='" . $f3->get("requested_vpath") . "' LIMIT 1;";
			echo "JS found\n";
		}

		if ( strlen($updated_js) > 0 ) {
			$sql = "UPDATE `content` SET `stylesheet`='" . addslashes($updated_css) . "' WHERE `virtual_path`='" . $f3->get("requested_vpath") . "' LIMIT 1;";
			echo "CSS found\n";
		}
		exit;
	} else {
		echo "No records found.";
		exit;
	}
	
}


$html_from_db = ( $content->virtual_path ) ? stripslashes($content->content) : "";
$javascript_from_db = ( $content->virtual_path ) ? stripslashes($content->javascripts) : "";
$stylesheet_from_db = ( $content->virtual_path ) ? stripslashes($content->stylesheets) : "";

$width = (strlen($width)>0) ? $width . "px" : 'auto';
$height = $f3->get("REQUEST.height") -75;

if ( $f3->get("REQUEST.mode") == "editor" ) {
$content_to_return = <<<EOF
<div id="bfContentEditorContainer" style="width: $width; height: $height;">
    <ul id="bfContentEditorTabs" style="width: $width;">
        <li class="bf_ui_left"><a name="cms_content" href="#">Content</a></li>
        <li class="bf_ui_middle"><a name="cms_javascript" href="#">Javascript</a></li>
        <li class="bf_ui_right"><a name="cms_stylesheet" href="#">Stylesheet</a></li>
        <a id="save" href="#" style="margin-left: 10px;">Save changes.</a>
        <li class="close" style="float: right; display: inline-block;"><a test="x" href="#">X</a></li>
    </ul>
    <div id="bfContentEditorTextAreas">
		<div name="cms_content" ><textarea id="cms_content" name="cms_content" class="editor" style="width: $width; height: $height; color: black; background-color: transparent;">$html_from_db</textarea></div>
		<div name="cms_javascript" ><textarea id="cms_javascript" name="cms_javascript" style="width: $width; height: $height; color: black; background-color: transparent;">$javascript_from_db</textarea></div>
        <div name="cms_stylesheet" ><textarea id="cms_stylesheet" name="cms_stylesheet" style="width: $width; height: $height; color: black; background-color: transparent;">$stylesheet_from_db</textarea></div>
	</div>
</div>
EOF;

$vpath =  $f3->get("requested_vpath");
$uuid = $f3->get("SESSION.tmpuuid");

$javascript .= <<<EOF
	$('div[id=bfContentEditorTabs]').css('width', '$width');
	$('div[id=bfContentEditorContainer] div[name=cms_javascript]').hide();
	$('div[id=bfContentEditorContainer] div[name=cms_stylesheet]').hide();
	$('div[id=bfContentEditorContainer] a[name=cms_content]').css('color', 'blue');
	$('div[id=bfContentEditorContainer] a[name=cms_javascript]').css('color', 'black');
	$('div[id=bfContentEditorContainer] a[name=cms_stylesheet]').css('color', 'black');
	$('div[id=bfContentEditorContainer] a').css('text-decoration', 'none');
	/* $('div[id=bfContentEditorTextAreas]').css('border','0px'); */
	$('div[id=bfContentEditorContainer] a').on('click',function(){
		var clicked_tab = $(this).attr('name');
		if ( clicked_tab != "" ) {
			if (clicked_tab == "cms_content") {
            	$('div[id=bfContentEditorContainer] div[name=cms_content]').show();
	            $('div[id=bfContentEditorContainer] div[name=cms_javascript]').hide();
                $('div[id=bfContentEditorContainer] div[name=cms_stylesheet]').hide();
            }
			if (clicked_tab == "cms_javascript") {
            	 $('div[id=bfContentEditorContainer] div[name=cms_content]').hide();
	            $('div[id=bfContentEditorContainer] div[name=cms_javascript]').show();
                $('div[id=bfContentEditorContainer] div[name=cms_stylesheet]').hide();
            }
			if (clicked_tab == "cms_stylesheet") {
            	$('div[id=bfContentEditorContainer] div[name=cms_content]').hide();
	            $('div[id=bfContentEditorContainer] div[name=cms_javascript]').hide();
                $('div[id=bfContentEditorContainer] div[name=cms_stylesheet]').show();
            }
			$('div[id=bfContentEditorContainer] a[name=cms_content]').css('color', 'black');
			$('div[id=bfContentEditorContainer] a[name=cms_javascript]').css('color', 'black');
			$('div[id=bfContentEditorContainer] a[name=cms_stylesheet]').css('color', 'black');
			$('div[id=bfContentEditorContainer] a[name='+clicked_tab+']').css('color', 'blue');
        	return false;
		}
    });
   
$(function(){
     var editor = CodeMirror.fromTextArea(document.getElementById("cms_javascript"), { mode: 'css', lineNumbers: true });
});
  

   
EOF;
	//exit;
}

/* Code should be added to change out CKEditor for TinyMCE, based on preferences and/or config etc. */
$javascriptToLoadCKEditor =  Web::instance()->minify("loader.ckeditor.js", null, true, __DIR__."/editors/ckeditor/");
$javascriptToLoadCKEditor = str_replace("{{uuid}}", $f3->get("SESSION.tmpuuid"), $javascriptToLoadCKEditor);
$javascriptToLoadCKEditor = str_replace("{{vpath}}", $f3->get("requested_vpath"), $javascriptToLoadCKEditor);
$javascriptToLoadCKEditor = str_replace("{{height}}", $height, $javascriptToLoadCKEditor);
$javascript .= $javascriptToLoadCKEditor . "\n";

if ( $f3->get("REQUEST.mode") == "editor" ) {
	echo json_encode(array("display"=>$content_to_return, "javascript"=>"$javascript"));
} else {
	$javascript = <<<EOF
	$('<div class="rpc_msg_warn">Sorry, you do not have editing permissions.</div>').fadeIn(300).insertAfter($('body')).delay(2000).animate({"top":"-=80px"},1500).animate({"top":"-=0px"},1000).animate({"opacity":"0"},700);
EOF;
	echo json_encode(array("display"=>$html_from_db, "javascript"=>"$javascript"));
}

?>