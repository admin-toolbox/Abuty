<?php

include(__DIR__ ."/interface.class.php");

Base::instance()->get("HOOKS")->add_action('security', function() {
	
	// $handle = Bigfoot::CMS()->HideRealFile($fake, $real); // Returns temperary handle.
	$ui = new screen; // Currently out of scope.
	Base::instance()->set("ui", $ui);
	
	$auth = Base::instance()->get("auth");
	if ( Base::instance()->get("auth") instanceof Delight\Auth\Auth ) {
		if (Base::instance()->get("auth")->hasAnyRole(Delight\Auth\Role::ADMIN, Delight\Auth\Role::EDITOR)) {
			
			if ( Base::instance()->get("REQUEST.vpath") ) { Base::instance()->set("requested_vpath", Base::instance()->get("REQUEST.vpath")); }
			
			if ( empty(Base::instance()->get("SESSION.uuid")) ) {
				$uuid = Delight\Auth\Auth::createUuid();
				Base::instance()->set("uuid", $uuid);
				$_SESSION['uuid'] = $uuid;
			}
			
			if ( !empty($_SESSION['MAGIC']) ) {
				foreach($_SESSION['MAGIC'] as $symbol=>$link) {
					Base::instance()->route('GET|POST '.$symbol, function($f3) use ($symbol, $link) {	
						$ext = pathinfo($link, PATHINFO_EXTENSION);
						$filename = basename($link);
						$directory = $f3->get("ROOT").dirname($link)."/";
						if ( $ext == "js" ) {
							$js =  Web::instance()->minify($filename, null, true, $directory);
							$js = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', '', $js);
							$js = str_replace("{{uuid}}", $f3->get("SESSION.uuid"), $js);
							$js = str_replace("{{vpath}}", $f3->get("vpath"), $js);
							header("Content-type: application/x-javascript");
							echo $js;
							exit;
						} elseif( $ext == "css" ) {
							header("Content-type: text/css");
							
						} else {
							header("Content-type: text/css");
							$css = file_get_contents($directory.$filename);
							$js = str_replace("{{uuid}}", $f3->get("SESSION.uuid"), $js);
							$scss = new Compiler();
							echo $scss->compile($css);
							exit;
						}
						echo Template::instance()->resolve(Template::instance()->parse(file_get_contents($f3->get("ROOT").'/'.$link)));
						exit;
						
					});
				}
				
				Base::instance()->route('POST /bigfootcms/init [ajax]', function($f3) { //POST /init [ajax]
					$js  = Web::instance()->minify("ui.js", null, true, __DIR__.'/js/')."\n\n";
					$js = str_replace("{{requested_vpath}}", Base::instance()->get("requested_vpath"), $js);
					$js = str_replace("{{uuid}}", Base::instance()->get("SESSION.uuid"), $js);
					echo json_encode(array(
						"a"=>Template::instance()->resolve(Template::instance()->parse(file_get_contents(__DIR__.'/pages/ui.html'))),
						"b"=>'<script>'.$js.'</script>'
					));
					exit;
				});
				
			} else {
				/* Creation of session held association of real filesnames and their temperary symbolic links */
				foreach(["js", "css", "scss"] as $ext) {
					foreach(glob(__DIR__.'/'.$ext.'/*.'.$ext) as $file) {
						$basename = basename($file);
						$_SESSION['MAGIC'][Base::instance()->set("$basename", '/'.$ext.'/'.$basename)] = str_replace(Base::instance()->get('ROOT'), "", __DIR__.'/'.$ext.'/'.$basename);
					}
				}
			}
			
			Base::instance()->get("HOOKS")->add_action("end_of_dom", function($html) {
				$fragment = new DOMDocument();
				$fragment->loadHTML('<script body src="/js/bigfootcms.js"></script>');
				$html->scan($fragment->saveHTML());
				return $html;
			});
			
			Base::instance()->route(array('POST /'.Base::instance()->get("SESSION.uuid").'/content', 'POST /'.Base::instance()->get("SESSION.uuid").'/content/@action'), function($f3, $params) {
				require(__DIR__."/editor.php");
				exit;
			}, 0);
			
			Base::instance()->route(array(
				  'GET /' . Base::instance()->get("SESSION.uuid").'/tab/@tab'
				, 'GET /' . Base::instance()->get("SESSION.uuid").'/tab/@tab/@screen'
				, 'POST /'. Base::instance()->get("SESSION.uuid").'/tab/@tab/@screen/@function/*'
			), function($f3, $params) {
				$tabFunctionsScript = ($_SERVER['REQUEST_METHOD'] == "POST" )
					? __DIR__."/panels/".$f3->get('PARAMS.tab').'.rpc.php'
					: __DIR__."/panels/".$f3->get('PARAMS.tab').'.php';
				if (file_exists($tabFunctionsScript)) {
					require($tabFunctionsScript);
				} else {
					echo json_encode(array("display"=>'<div class="head">This panel is not available: </div><h5>Missing "'.str_replace($f3->get("ROOT"), "", $tabFunctionsScript).'"</h5>'));
					exit;
				}

				if ($_SERVER['REQUEST_METHOD'] == "POST" ) { // POST		
					$function = $f3->get('REQUEST.name');
					if (function_exists($function)) {
						$response = $function($ui);
						echo json_encode($response);
					} else {
						echo json_encode(array("display"=>$ui->callback("error", 'There is no function for this panel in "'.$tabFunctionsScript.'"')));
						exit;
					}
				} else {
					$function = ( $f3->get('PARAMS.screen') ) ? $f3->get('PARAMS.screen') :  $f3->get('PARAMS.tab');
					if ( function_exists($function) ) {
						$response = $function($ui);
						$response['display'] .= '';
						echo json_encode($response);
					} else {
						if ( !function_exists($function) ) {
							if (  $f3->get('PARAMS.screen') == "" ) {
								$initialized = initialize();
								echo (is_array($initialized)) ? json_encode($initialized) : json_decode($initialized);
							} else {
								echo json_encode(array("display"=>'<div class="head">This panel is not available: </div><h5>There is no function defined to create this panel view in "'.str_replace($f3->get("ROOT"), "", $tabFunctionsScript).'"</h5>'));
							}
						} else {
							$response = $function();
							echo json_encode($response);
						}

					}
				}
				exit;
			}, 0, 1);
			
		}
	} else {
		die("The CMS plugin requires a working instance of Delight\Auth\Auth available as the $auth object.");
	}
});