<?php

function initialize() {
	global $f3;
	global $ui;
	$subnavigation  = '<ul id="bfSecondaryTabs">';
	$subnavigation .= '<li class="abuty_ui_left"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/content/basics" title="Give this resource a title, some keyworks and a short description so search engines can easily find this page.">Basic/SEO Information</a></li>';
	$subnavigation .= '<li class="abuty_ui_middle"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/content/extended" title="Control who, what, when where and why resource becomes available.">Distribution Controls</a></li>';
	$subnavigation .= '<li class="abuty_ui_right"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/content/navigation" title="Specify the navigational &quot;targets&quot; where this resource can be made available.">Visibility in Navigation</a></li>';
	$subnavigation .= '<li class="abuty_ui_seperator"></li>';
	$subnavigation .= '<li class="abuty_ui_left"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/content/revisions" title="A running list of archived versions of document.">Revisions</a></li>';
	$subnavigation .= '<li class="abuty_ui_middle"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/content/drafts" title="Saved drafts">Drafts</a></li>';
	$subnavigation .= '<li class="abuty_ui_right"><a href="/'.$f3->get("SESSION.tmpuuid").'/tab/content/notes" title="Notes about this document.">Notes</a></li>';
	$subnavigation .= '</ul>';
	$first_screen = (object) Welcome($ui);
	$complete = $subnavigation . $first_screen->display;
	return array("SecondaryTabs"=>$subnavigation, "display"=>$complete);
}

function Welcome($ui) {
	$xhtml  = '<div class="head">Content Overview</div>';
	$xhtml .= "<p>Welcome to the Content panel. From here you can manage everything related to this specific web page.</p>";
	return array("display"=>$xhtml);
}

function resource_selection($ui) {
	global $dbh;
	global $f3;
	$sth = $dbh->prepare('SELECT * FROM `content` WHERE `virtual_path` LIKE :virtual_path LIMIT 1');
	$sth->bindValue(":virtual_path", $f3->get("requested_vpath"), PDO::PARAM_STR);
	$sth->execute();
	$sth->setFetchMode(PDO::FETCH_OBJ);
	$content = $sth->fetch();

	if ($content->virtual_path) {
		$basics = (object) basics($ui, $params);
		$xhtml = $xhtml . $basics->display;
	} else {
		$fixed_parent_parts = explode("/", dirname($f3->get("requested_vpath")));
		array_pop($fixed_parent_parts);
		$fixed_parent = implode("/", $fixed_parent_parts)."/";
		
		$xhtml .= '<script>$(\'ul[id=control_panel_subnav_tabs]\').html("");</script>';
		$xhtml .= '<ul>';
		$xhtml .= '<div class="head">This virtual path <b>'.$f3->get("requested_vpath").'</b> not yet defined.</div>';
		$xhtml .= '<li>Initially, this resource will be filed under "'.$fixed_parent.'".</li>';
		$xhtml .= '<li>Would you like to create a record here?'.'</li>';
		
		$xhtml .= "<li>Type \"yes\" to activate:" . $ui->make_input("", array("name"=>"vpath_activation", "maxlength"=>"3"), "").'</li>';
		$xhtml .= '</ui>';
	}
	return array("display"=>$xhtml);
}

function editor($ui) {
	// create a special div to land toolbar from editor into.
	return array("display"=>"$xhtml"); 
}

function basics($ui) {
	global $dbh;
	global $f3;
	$sth = $dbh->prepare('SELECT * FROM `content` WHERE `virtual_path` = :virtual_path LIMIT 1');
	$sth->bindValue(":virtual_path", $f3->get("requested_vpath"), PDO::PARAM_STR);
	$sth->execute();
	$sth->setFetchMode(PDO::FETCH_OBJ);
	$content = $sth->fetch();
	$security = json_decode($content->security);
	$meta = json_decode($content->meta_data);
	if ( $content->virtual_path ) {
		$f3->set("page_title", $content->page_title);
		$f3->set("internal_path", $content->internal_path);
		$f3->set("keywords", $meta->keywords);
		$f3->set("description", $meta->description);
		$xhtml = Template::instance()->resolve(file_get_contents(__DIR__.'/content/basics.html'));
	} else {
		$xhtml = Template::instance()->resolve(file_get_contents(__DIR__.'/content/basics.create_new.html'));
	}
	return array("display"=>"$xhtml"); 
}

function extended($ui) {
	global $dbh;
	global $f3;
	$vpath  = $f3->get("requested_vpath");
	define("TEMPLATES", $f3->get("ROOT").'/Templates/');
	$sth = $dbh->prepare('SELECT * FROM `content` WHERE `virtual_path` = :virtual_path LIMIT 1');
	$sth->bindValue(":virtual_path", $f3->get("requested_vpath"), PDO::PARAM_STR);
	$sth->execute();
	$sth->setFetchMode(PDO::FETCH_OBJ);
	$content = $sth->fetch();	
	
	$security = (object) unserialize($content->security);
	$meta = (object) unserialize($content->meta_data);
	$theme = (object) unserialize($content->theme);

	if ( $content->virtual_path ) {
		
		
		//$xhtml = Template::instance()->render('plugins/CMS/tabs/content/extended.html');
		
		$f3->set("select_AuthRequired", $ui->make_select("", "auth_required", $security->auth_required, array("O"=>"may always","Y"=>"must be logged in to")));
		
		$xhtml .= '	<div class="head">Control how the resources are accessed and presented to the public.</div>';
		$xhtml .= '		<div class="left">';
		$xhtml .= '			<div class="panel sm-margin">
								<div class="grid-x">';
		$xhtml .='					<div class="left">Visitors to this page &nbsp;</div>
									<div class="left">{{@select_AuthRequired}}</div>
									<div class="left"> &nbsp;access it.</div>
									<div id="clear">&nbsp;</div>
								</div>';
		$xhtml .= '			</div>';
		$xhtml .= '		</div>';
		$xhtml .= '		<div class="left">';
		$xhtml .= '			<div class="panel sm-margin">';
		
		
		
		
		
		
		$dh = opendir($f3->get("ROOT"));
		
		define("TEMPLATES", $f3->get("ROOT")."/Templates/");
		
		//$xhtml .= '<li>' . $ui->make_select("Templates?", "ssl_required", $theme->template, array("O"=>"Optional","Y"=>"Always")) . '</li>';
		while (false !== ($file = readdir($dh))) {
			if (!is_dir(TEMPLATES."$file")) {
				$file = preg_replace('/\..*$/', '', $file);
				//$templates[$file] =  htmlspecialchars(ucfirst());
				if ( file_exists(TEMPLATES . $file . ".html") ) {
				$contents = file_get_contents(TEMPLATES . $file . ".html");
				preg_match('/<title>([^>]*)<\/title>/si', $contents, $match );
				if (isset($match) && is_array($match) && count($match) > 0) {
					$templates[$file.'.html'] = strip_tags($match[1]);
				}
			}
		}
        }
        closedir($dh); unset($dh); unset($file);
        if ( !$theme->template ) {
            $xhtml .= '<div>'.$ui->make_select("Template:", "template", "default.html", $templates); $xhtml .= "</div>";
        } else {
            if ( file_exists(TEMPLATES . $theme->template) ) {
                $contents = file_get_contents(TEMPLATES . $theme->template);
                preg_match('/<title>([^>]*)<\/title>/si', $contents, $match );
                if (isset($match) && is_array($match) && count($match) > 0) {
                    $title = strip_tags($match[1]);
                    $xhtml .= ''.$ui->make_select("<div class=\"left\">Template:&nbsp;</div><div class=\"left\">", "template", $theme->template, $templates).'</div><div id="clear">&nbsp;</div>';
                } else {
                    $xhtml .= '<div>'.$ui->make_select("Template:", "template", "default.html", $templates).'</div>';
                }
				
				
				
				
				$xhtml .= '<div class="left">Flag this resource as &nbsp;</div><div class="left">'. $ui->make_select("", "enable_in_navigation", $content->enable_in_navigation, array("Y"=>"enabled","N"=>"disabled")) . '</div><div class="left">&nbsp; in navigation.</div><div id="clear">&nbsp;</div>';
				
			$xhtml .='</div>';
            }
			
        }
		$xhtml .= '</div>';
		return array("display"=>$xhtml);
    } else {
		/* No virtual path is defined. */
		return array("display"=>_noContentResource($ui));
    }
}

function notes() {
	
	return array("display"=>"NO notes yet");
}

function navigation($ui) {
	global $dbh;
	global $f3;
	$sth = $dbh->prepare('SELECT * FROM `content` WHERE `virtual_path` = :virtual_path LIMIT 1');
	$sth->bindValue(":virtual_path", $f3->get("requested_vpath"), PDO::PARAM_STR);
	$sth->execute();
	$sth->setFetchMode(PDO::FETCH_OBJ);
	$content = $sth->fetch();	
	if ( $content->enable_in_navigation == "Y" ) {
		$navigation = (object) $framework->general_query("navigation", "SELECT * FROM abuty_navigation WHERE virtual_path = '".VPATH."'");
		$xhtml .= '	<div class="head">Controls <i>where</i> this resource is available in navigation.</div>';
		$xhtml .= '		<div class="left">';
		$xhtml .= '			<div class="panel sm-margin">';
		$xhtml .= '				<div class="row">'; 
		$xhtml .= '				' . $ui->make_input("<div class=\"left\">Navigation Title:&nbsp;</div><div class=\"left\">", array("name"=>"nav_title",  "maxlength"=>"75"), $content->nav_title).'</div><div id="clear">&nbsp;</div>';
		$xhtml .= '				<div class="large-12 columns"><p>If you are using a drop-down navigation system please set this page\'s setting, default setting is parent</p></div>';
		$xhtml .= '				'. $ui->make_select("<div class=\"left\">Drop-down menu setting&nbsp;</div><div class=\"left\">", "navlvl", $navigation->navlvl, array("topnav"=>"parent","subnav"=>"child")) . '</div><div id="clear">&nbsp;</div>';
		$xhtml .= '				</div>';
		$xhtml .= '			</div>';
		$xhtml .= '		</div>';
		$xhtml .= '		<div class="left">';
		$xhtml .= '			<div class="panel sm-margin">';
		$xhtml .= '				<div class="row">';
		$xhtml .= '					<p>Please select where you would like the link to show up in your navigation.</p>';
		$xhtml .= '					<div class="large-6 columns">';
		$xhtml .= '						<li>'. $ui->make_select("Upper/Top", "top", $navigation->top, array("N"=>"not available","Y"=>"available")) . '</li>';
		$xhtml .= '						<li>'. $ui->make_select("Lower/Bottom", "bottom", $navigation->bottom, array("N"=>"not available","Y"=>"available")) . '</li>';
		$xhtml .= '					</div>';
		$xhtml .= '					<div class="large-6 columns">';
		$xhtml .= '						<li>'. $ui->make_select("Left side", "left", $navigation->left, array("N"=>"not available","Y"=>"available")) . '</li>';
		$xhtml .= '						<li>'. $ui->make_select("Right side", "right", $navigation->right, array("N"=>"not available","Y"=>"available")) . '</li>';
		$xhtml .= '					</div>';
		$xhtml .='				</div>';
		$xhtml .='			</div>';
		$xhtml .='		</div>';
	} else {
		//$xhtml .= '<ul>';
		$xhtml .= '<div class="head">Navigation is disabled.</div>';
		$xhtml .= '<p>To manage the locations in your templates where navigational links to this resource are available, <a href="/bigfoot/tab/content/extended">click here</a>.</p>';
		//$xhtml .='</ul>';
	}
	$javascript = "";
	return array("display"=>"$xhtml", "javascript"=>"$javascript");
}

?>