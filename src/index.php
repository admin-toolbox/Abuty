<?php
class Abuty extends Prefab {
    
    public static $assets;

    public function run() {
        $fw = \Base::instance();

        $this->assets = (object)["meta" => [], "index" => [], "javascripts" => (object)[], "stylesheets" => (object)[], ];

        $device = new Mobile_Detect();
        $fw->set("deviceType", $device->isMobile() ? ($device->isTablet() ? "tablet" : "phone") : "desktop");
        unset($device);

        Sugar\Event::instance()->emit("device_detect");

        $fw->set("CACHE", true);
        $fw->set("ONREROUTE", function(){ return true; }); // Disable SEO trailing slash redirect problem.
        
        $fw->set("QUIET", true);

        // Running with recommended dev mode environment flag?
        if ( isset($_SERVER['NODE_ENV']) && strtolower($_SERVER['NODE_ENV']) == "dev") {
            $fw->set("DEV", true);
            $fw->set("CACHE", false);
            $fw->set("DEBUG", 3);
            Falsum\Run::handler(true);
        }
        
        $fw->set("ONERROR", function ($args) {
            // $TODO: Return true for Error 404, with content set.
            $fw = \Base::instance();
            $fw->set("ESCAPE", false);
            $fw->set("page_title", "Error " . $fw->get("ERROR.code"));

            // clear output buffer
            while (ob_get_level()) ob_end_clean();

            // Fancier error page
            if ($fw->get('ERROR.code') == 500 && $fw->DEV && !$fw->CLI) {
                Falsum\Run::handler(true);
                $fw->call($fw->ONERROR, $fw);
                exit();
            }

            if ($fw->AJAX) {
                header("Content-Type: application/json");
                die(json_encode(array('error'=>$fw->get('ERROR.text'))));

            } elseif ($fw->CLI) {
                print_r($fw->get('ERROR'));
                exit();
            } else {
                $fw->set("content", "<p>".$fw->get("ERROR.text")."</p>");
                $fw->set("RESPONSE", "<pre>" . $fw->get("ERROR.text") . "\n" . $fw->get("ERROR.trace") . "</pre>");
            }

            $fw->set("UI", "Templates/");
            echo Template::instance()->render("default.html");

            return false;
        });
        

        try {
            $this->dbh = new DB\SQL($fw->get("mysql.dsn"), $fw->get("mysql.user"), $fw->get("mysql.pass"), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_PERSISTENT => false, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, ]);
            $count = count(explode("/", $fw->PATH));
            $query = $this->dbh->prepare("SELECT * FROM content WHERE virtual_path = :path LIMIT 1");
            $query->execute([":path" => $fw->PATH]);
            $sql = "";

            if ($query->rowCount() == 0) {
                for ($i = $count;$i >= 1;$i--) {
                    if (dirname($fw->PATH) == "/") continue;
                    if (dirname($fw->PATH) != "/") $sql = 'WHERE virtual_path = "' . dirname($fw->PATH) . '/ " ';
                    $query = $this->dbh->prepare("SELECT * FROM content " . $sql . " LIMIT 1");
                    $query->execute();
                    if ($query->rowCount() == 1) {
                        $this->content = $query->fetchAll()[0];
                        break;
                    }
                }
            } else {
                $this->content = $query->fetchAll()[0];
            }
        } catch(PDOException $e) {
            Sugar\Event::instance()->emit("database_error", $e);
            $fw->error("500", $e->getMessage());
            exit();
        }

		$query = $this->dbh->prepare("SELECT `pid`,`virtual_path`, `protected`, `navPlacement`, `weight`, `page_title`, `nav_title` FROM `content` WHERE 1 ORDER BY `weight`, `virtual_path`");
		$query->execute();
		$this->nav = (array) $query->fetchAll(PDO::FETCH_ASSOC);

        // Initialize Plugins
        if ( $fw->exists("plugins") && is_array($fw->plugins) ) {
            $fw->set("plugins", array_filter($fw->plugins));
            $fw->set("PLUGINS", (($fw->exists("PLUGINS") )?$fw->PLUGINS:"plugins/"));
            foreach (glob($fw->ROOT.'/'.$fw->PLUGINS .'*/index.php') as $script ) {
                $key = basename(dirname($script));
                if ( !$fw->exists("plugins.".$key)) continue;
                $this->plugin_paths[$key] = $fw->PLUGINS . $key . '/';
                include($script);
            }
        }

        // If you need to autoload controllers, you must define your routes within in the `init` event.
        Sugar\Event::instance()->emit("init");

        $routes = array_keys($fw->get("ROUTES"));

        // Hardcoded virtual paths in database always take precedence.
        if ( isset($this->content) ) {

            // Preset content with HTML form content field.
            if ( strlen($this->content->content)>5 ) {
                $fw->set("ESCAPE", false);
                $fw->set("content", htmlspecialchars_decode($this->content->content));
            }

            // Swap content for F3 routes supplied content (if available)
            if ( in_array($fw->get("PATH"), $routes) ) {
                Sugar\Event::instance()->emit("run");
                $fw->run();
            }

            // Swap content for sandbox supplied content (if available)
            if ( file_exists($fw->ROOT . $this->content->internal_path) ) {
                $ui = $fw->get("UI");
                $fw->set("UI", "./");
                $buffer = View::instance()->render($this->content->internal_path);
                if (strlen($buffer) > 4) $fw->set("content", $buffer);
                $fw->set("UI", $ui);
            }
        } elseif (in_array($fw->get("PATH"), $routes)) {
            // A hard route is set somewhere. Emit `run` and invoke framework.
            Sugar\Event::instance()->emit("run");
            $fw->run();
        } else {
            // Things get a bit messy here, but it's required to find a route with tokens.
            // TODO: Prevent false matches by also checking extension, pull path.
            foreach($routes as $route) {
                if ( strpos($route, '@') ) {
                    preg_match_all('#/(@[^/]*)#', $route, $matches);
                    $pattern = str_replace($matches[1], '*', $route);
                    if (fnmatch($pattern, $route)) {
                        Sugar\Event::instance()->emit("run");
                        $fw->run();
                        break;
                    }
                }
            }
        }

        // Preset page title from db if available.
        if (isset($this->content->page_title)) $fw->set("page_title", $this->content->page_title);

        // TODO: Hook before and after.
        if ( !$fw->exists("RESPONSE") ) {
            $fw->error(404);
        }

        // If a theme has been defined, use it.
        if (!$fw->exists("template") && !empty($this->content->theme)) {
            $theme = json_decode($this->content->theme);
            if (!empty($theme->template)) {
                $fw->set("template", $theme->template);
            }
        }

        // TODO: Add template hunter function.
        if (!$fw->exists("template")) $fw->set("template", "default.html");

        // Pull template HTML assets.
        $template = View::instance()->render($fw->get("template"));

        // Read the template assets, set in HIVE, reset, then move onto content. (We inject the values later before page render)
        $this->scan_document($template);
        $this->template = $this->clean_document($template);
        $this->template_assets = $this->assets;
        $this->assets = (object)[];
        $this->assets->index = $this->assets->metatags = [];
        $this->assets->javascripts = $this->assets->stylesheets = [];

        libxml_use_internal_errors(true);
        $html = new DOMDocument();
        $html->loadHTML($this->template);
        $fw->set("ESCAPE", false);
        $fw->set("ESC", false);
        
        // The magic of dynamics starts here.
        $ui = $fw->get("UI");
        $fw->set("UI", "dynamics/");
        $fw->set("dynamics", array_merge(glob($fw->UI . "*/" . $fw->get("deviceType") . ".php"), glob($fw->UI . "*.php")));
        $this->html = $html->saveHTML();

        $fw->set("processed", []);
        $dynamics = (function ($markup) use ($fw, &$dom) {

            $tmpl = new DOMDocument("1.0", "UTF-8");
            $tmpl->loadHTML($markup, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);

            foreach ($tmpl->getElementsByTagName("*") as $tag) {
                $id = $tag->getAttribute("id");
                if (empty($id) || in_array($id, $fw->processed)) continue;

                $fw->processed[] = $id;
                if ($tag->hasAttributes()) {
                    $fw->set("DYNAMIC", []);
                    foreach ($tag->attributes as $attr) $fw->DYNAMIC[$attr->nodeName] = $attr->nodeValue;
                } else {
                    $fw->clear("DYNAMIC");
                }

                // Try folder/<deviceType>.php, then <id>.php.
                $try = [
                      $fw->UI . $id . "/" . $fw->get("deviceType") . ".php"
                    , $fw->UI . $id . ".php"
                ];

                // Determine what is available to render for $id.
                $available = array_intersect($try, $fw->get("dynamics"));
                if (count($available) == 0) continue; // Nothing? NEXT $id, please!

                // F3 renders from the set UI path. Modify accordingly.
                $first_available = substr(array_shift(array_values($available)), strlen($fw->UI));
                $widget = count($available) > 0 ? $first_available : "";
                
                $widget = View::instance()->render($widget);
                if (empty($widget)) continue;

                $this->scan_document($widget);
                $widget = $this->clean_document($widget);

                if (strlen($widget) > 0) {
                    $tmp = new DOMDocument();
                    $tmp->loadHTML($widget, LIBXML_HTML_NODEFDTD);
                    while ($tag->childNodes->length) $tag->removeChild($tag->firstChild);
                    $xpath = new DOMXPath($tmp);
                    $body = $xpath->query("/html/body");
                    $dynamic = $tmpl->createDocumentFragment();
                    $body = $tmp->saveXml($body->item(0));
                    $dynamic->appendXML($body);
                    $tag->appendChild($dynamic);
                    $markup = $tmpl->saveHTML();
                }

            }

            return $markup;
        });

        $this->html = $dynamics($this->html);

        // If F3 provided a response, let's overwrite the content placeholder
        if ($fw->exists("RESPONSE") && strlen($fw->get("RESPONSE")) > 0) {
            $fw->set("content", $dynamics($fw->get("RESPONSE")));
        } elseif ($fw->exists("content") && strlen($fw->get("content")) > 0) {
            $fw->set("content", $dynamics($fw->get("content")));
        } else {

            echo $fw->content;
            echo $fw->RESPONSE;
            $fw->set("content", "<p>No content could be rendered</p>");
        }
        
        //  Assemble the main template.
        $html = new DOMDocument();
        $html->loadHTML($this->html);
        $fw->set("UI", $ui);
        $fw->set("ESCAPE", false);
        $this->assets->index = (object)$this->template_assets->index;
        $this->assets->metatags = (object)$this->template_assets->metatags;
        $this->assets->javascripts = (object)array_merge((array)$this->template_assets->javascripts, (array)$this->assets->javascripts);
        $this->assets->stylesheets = (object)array_merge((array)$this->template_assets->stylesheets, (array)$this->assets->stylesheets);
        unset($this->template_assets);

        // Set F3 template variable for page titles.
        $html->getElementsByTagName("title")->item(0)->nodeValue = "{{@page_title}}";

        // HOOK: Compiled DOM Tree
        Sugar\Event::instance()->emit("end_of_dom", $html);
        $html = $this->rebuild($html);

        // HOOK: Last opportunity to adjust page titles
        Sugar\Event::instance()->emit("page_title", $fw->get("page_title"));
        $html = Template::instance()->resolve($html);

        // HOOK: Compiled HTML Document
        $fw->clear("processed");
        $fw->clear("DYNAMIC");
        exit($html);
    }

    public function sitemap($nav="") {
        if ( !is_array($nav) ) return array();
        // Convert one dimensional array into tree-like multidimensional array.
        $index = array_map(function($v){
            if ( $fw->PATH == $v['virtual_path'] ) {
                $v['isVisiting'] = true;
            }
            if ( $v['pid'] == $v['virtual_path'] ) {
                $v['isParent'] = true;
            }
            if ( $v['nav_title'] == "" && $v['page_title'] != "" ) {
                $v['nav_title'] = $v['page_title'];
            }
            return $v;
        }, $nav);

        foreach ($index as $k=>$v) {
            $index[$v['virtual_path']] = $v;
            unset($index[$k]);
        }
        foreach ($index as $k=>&$v) {
            $index[$v['pid']]['children'][$k] = &$v;
            if ( isset($index[$k]['children']) ) {
                $roots[] = $v['children'];
                unset($index[$k]['children']);
            }
            unset($v);
        }
        foreach ($roots as $v) {
            $sitemap[key($v)] = $v[key($v)];
        }
        unset($index, $roots, $k, $v);
        return $sitemap;
    }

    private function scan_document($html, $target = "") {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom->loadHTML($html);
        $blocks = $target == "body" ? ["body"] : ($target == "head" ? ["head"] : ["body", "head"]);
        foreach ($blocks as $block) {
            $block = $dom->getElementsByTagName($block)->item(0);
            if (!empty($block)) {
                $assets = $block->getElementsByTagName("link");
                for ($i = 0;$i < $assets->length;$i++) {
                    $resource = $assets->item($i);
                    $attributes = [];
                    foreach ($resource->attributes as $attribute => $value) {
                        if (strlen($value->nodeValue) > 0) $attributes[$value->nodeName] = trim($value->nodeValue);
                    }
                    ksort($attributes);
                    $key = md5(serialize($attributes));
                    if (!in_array($key, $this->assets->index)) {
                        if ($resource->hasAttribute("body")) {
                            $this->assets->stylesheets->body[] = $attributes;
                        } else {
                            $this->assets->stylesheets->head[] = $attributes;
                        }
                        $this->assets->index[] = $key;
                    }
                }
                $assets = $block->getElementsByTagName("meta");
                for ($i = 0;$i < $assets->length;$i++) {
                    $resource = $assets->item($i);
                    $attributes = [];
                    foreach ($resource->attributes as $attribute => $value) {
                        if (strlen($value->nodeValue) > 0) $attributes[$value->nodeName] = trim($value->nodeValue);
                    }
                    ksort($attributes);
                    $key = md5(serialize($attributes));
                    if (!in_array($key, $this->assets->index)) {
                        $this->assets->metatags[] = $attributes;
                        $this->assets->index[] = $key;
                    }
                }
                $assets = $block->getElementsByTagName("script");
                for ($i = 0;$i < $assets->length;$i++) {
                    $resource = $assets->item($i);
                    if (strlen(trim($resource->textContent)) > 12) {
                        $key = md5(serialize(trim($resource->textContent)));
                        if (!in_array($key, $this->assets->index)) {
                            if ($resource->hasAttribute("body")) {
                                $this->assets->javascripts->body[] = rtrim($resource->textContent);
                            } else {
                                $this->assets->javascripts->head[] = rtrim($resource->textContent);
                            }
                            $this->index[] = $key;
                        }
                    } else {
                        $attributes = [];
                        foreach ($resource->attributes as $attribute => $value) {
                            if (strlen($value->nodeValue) > 0) {
                                $attributes[$value->nodeName] = trim($value->nodeValue);
                            }
                        }
                        ksort($attributes);
                        $key = md5(serialize($attributes));
                        if (!in_array($key, $this->assets->index)) {
                            if ($resource->hasAttribute("body")) {
                                $this->assets->javascripts->body[] = $attributes;
                            } else {
                                $this->assets->javascripts->head[] = $attributes;
                            }
                            $this->assets->index[] = $key;
                        }
                    }
                }
                $assets = $block->getElementsByTagName("style");
                for ($i = 0;$i < $assets->length;$i++) {
                    $resource = $assets->item($i);
                    if (strlen(trim($resource->textContent)) > 6) {
                        // *{v:1;}
                        $key = md5(serialize(trim($resource->textContent)));
                        if (!in_array($key, $this->assets->index)) {
                            if ($resource->hasAttribute("body")) {
                                $this->assets->stylesheets->body[] = rtrim($resource->textContent);
                            } else {
                                $this->assets->stylesheets->head[] = rtrim($resource->textContent);
                            }
                            $this->assets->index[] = $key;
                        }
                    }
                }
            }
        }
        return true;
    }

    // Function to remove any specific HTML asset resource and meta tags.
    private function clean_document($html) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom->loadHTML($html, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
        $removeNode = function (&$node) {
            $parent = $node->parentNode;
            $removeChildren = function (&$node) {
                while ($node->firstChild) {
                    while ($node->firstChild->firstChild) $removeChildren($node->firstChild);
                    $node->removeChild($node->firstChild);
                }
            };
            $removeChildren($node);
            $parent->removeChild($node);
        };

        foreach (["meta", "link", "style", "script"] as $tag) {
            $nodes = $dom->getElementsByTagName($tag);
            while ($nodes->length > 0) $removeNode($nodes->item(0));
        }
        return $dom->saveHTML();
    }

    public function rebuild($dom) {
        $nodes = $dom->getElementsByTagName("meta");
        while ($nodes->length > 0) {
            $node = $nodes->item(0);
            $this->removeNode($node);
        }

        foreach ($this->assets->metatags as $resources) {
            $domDocument = new DOMDocument();
            $domElement = $domDocument->createElement("meta");
            foreach ($resources as $element => $value) {
                $domAttribute = $domDocument->createAttribute($element);
                $domAttribute->value = $value;
                $domElement->appendChild($domAttribute);
            }
            $domDocument->appendChild($domElement);
            $frag = $dom->createDocumentFragment();
            $items = explode("\n", $domDocument->saveXML());
            array_shift($items);
            $frag->appendXML(implode("\n", $items));
            $dom->getElementsByTagName("head")->item(0)->appendChild($frag);
        }

        foreach ($this->assets->stylesheets as $dom_location => $resources) {
            foreach ($resources as $resource) {
                $domDocument = new DOMDocument();
                if (is_array($resource)) {
                    $domElement = $domDocument->createElement("link");
                    $keys = array_keys($resource);
                    $sorted_resource = array_merge(array_flip(["href", "integrity", "crossorigin", "hreflang", "defer", "rel", "media", "sizes", "type", ]), $resource);
                    foreach ($sorted_resource as $element => $value) {
                        if (!in_array($element, $keys)) continue;
                        $domAttribute = $domDocument->createAttribute($element);
                        $domAttribute->value = $value;
                        $domElement->appendChild($domAttribute);
                    }
                    $domDocument->appendChild($domElement);
                    $frag = $dom->createDocumentFragment();
                    $items = explode("\n", $domDocument->saveXML());
                    array_shift($items);
                    $frag->appendXML(implode("\n", $items));
                    if ($dom_location == "body") {
                        $dom->getElementsByTagName("body")->item(0)->appendChild($frag);
                    } else {
                        $dom->getElementsByTagName("head")->item(0)->appendChild($frag);
                    }
                }
            }
        }

        foreach ($this->assets->javascripts as $dom_location => $resources) {
            foreach ($resources as $resource) {
                $domDocument = new DOMDocument();
                if (is_array($resource)) {
                    $domElement = $domDocument->createElement("script");
                    $keys = array_keys($resource);
                    $sorted_resource = array_merge(array_flip(["src", "integrity", "crossorigin", "async", "defer", "charset", "type", ]), $resource);
                    foreach ($sorted_resource as $element => $value) {
                        if (!in_array($element, $keys)) continue;
                        $domAttribute = $domDocument->createAttribute($element);
                        $domAttribute->value = $value;
                        $domElement->appendChild($domAttribute);
                    }
                    $domDocument->appendChild($domElement);
                    $items = explode("\n", $domDocument->saveXML());
                    array_shift($items);
                    $frag = $dom->createDocumentFragment();
                    $frag->appendXML(implode("\n", $items));
                    if ($dom_location == "body") {
                        $dom->getElementsByTagName("body")->item(0)->appendChild($frag);
                    } else {
                        $dom->getElementsByTagName("head")->item(0)->appendChild($frag);
                    }
                }

                if (is_string($resource) && strlen(trim($resource)) > 6) {
                    $domElement = $domDocument->createElement("script", trim($resource));
                    $domDocument->appendChild($domElement);
                    $items = explode("\n", $domDocument->saveXML());
                    array_shift($items);
                    $frag = $dom->createDocumentFragment();
                    $frag->appendXML(implode("\n", $items));
                    if ($dom_location == "body") {
                        $dom->getElementsByTagName("body")->item(0)->appendChild($frag);
                    } else {
                        $dom->getElementsByTagName("head")->item(0)->appendChild($frag);
                    }
                }
            }
        }

        foreach ($this->assets->stylesheets as $dom_location => $resources) {
            foreach ($resources as $resource) {
                $domDocument = new DOMDocument();
                if (!is_array($resource)) {
                    if (strlen(trim($resource)) > 0) {
                        $domElement = $domDocument->createElement("style", trim($resource));
                        $domDocument->appendChild($domElement);
                        $items = explode("\n", $domDocument->saveXML());
                        array_shift($items);
                        $frag = $dom->createDocumentFragment();
                        $frag->appendXML(implode("\n", $items));
                        if ($dom_location == "body") {
                            $dom->getElementsByTagName("body")->item(0)->appendChild($frag);
                        } else {
                            $dom->getElementsByTagName("head")->item(0)->appendChild($frag);
                        }
                    }
                }
            }
        }

        // Format the output
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query("//text()") as $node) {
            $node->nodeValue = preg_replace(["/^[\s\r\n]+/", "/[\s\r\n]+$/"], "", $node->nodeValue);
            if (strlen($node->nodeValue) == 0) $node->parentNode->removeChild($node);
        }

        $format = (function ($dom, $currentNode = false, $depth = 0) use (&$format) {
            if ($currentNode === false) {
                $dom->removeChild($dom->firstChild);
                $currentNode = $dom;
            }
            $indentCurrent = $currentNode->nodeType == XML_TEXT_NODE && $currentNode->parentNode->childNodes->length == 1 ? false : true;
            if ($indentCurrent && $depth > 1) {
                $textNode = $dom->createTextNode("\n" . str_repeat("  ", $depth));
                $currentNode->parentNode->insertBefore($textNode, $currentNode);
            }
            if ($currentNode->childNodes) {
                foreach ($currentNode->childNodes as $childNode) $indentClosingTag = $format($dom, $childNode, $depth + 1);
                if ($indentClosingTag) {
                    $textNode = isset($currentNode->tagName) && $currentNode->tagName != "html" ? $dom->createTextNode("\n" . str_repeat("  ", $depth)) : $dom->createTextNode("\n");
                    $currentNode->appendChild($textNode);
                }
            }
            return $indentCurrent;
        });

        $format($dom);

        return $dom->saveHTML();
    }
}

?>
