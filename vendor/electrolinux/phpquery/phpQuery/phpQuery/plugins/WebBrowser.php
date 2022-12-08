<?php

class phpQueryObjectPlugin_WebBrowser {
	
	public static $phpQueryMethods = null;
	
	public static function WebBrowser($self, $callback = null, $location = null) {
		$self = $self->_clone()->toRoot();
		$location = $location
			? $location
			
			: $self->document->xhr->getUri(true);
		
		$self->document->WebBrowserCallback = $callback;
		if (! $location)
			throw new Exception('Location needed to activate WebBrowser plugin !');
		else {
			$self->bind('click', array($location, $callback), array('phpQueryPlugin_WebBrowser', 'hadleClick'));
			$self->bind('submit', array($location, $callback), array('phpQueryPlugin_WebBrowser', 'handleSubmit'));
		}
	}
	public static function browser($self, $callback = null, $location = null) {
		return $self->WebBrowser($callback, $location);
	}
	public static function downloadTo($self, $dir = null, $filename = null) {
		$url = null;
		if ($self->is('a[href]'))
			$url = $self->attr('href');
		else if ($self->find('a')->length)
			$url = $self->find('a')->attr('href');
		if ($url) {
			$url = resolve_url($self->document->location, $url);
			if (! $dir)
				$dir = getcwd();
			
			if (! $filename) {
				$matches = null;
				preg_match('@/([^/]+)$@', $url, $matches);
				$filename = $matches[1];
			}
			//print $url;
			$path = rtrim($dir, '/').'/'.$filename;
			phpQuery::debug("Requesting download of $url\n");
			
			file_put_contents($path, file_get_contents($url));
		}
		return $self;
	}
	
	public static function location($self, $url = null) {
		
		$xhr = isset($self->document->xhr)
			? $self->document->xhr
			: null;
		$xhr = phpQuery::ajax(array(
			'url' => $url,
		), $xhr);
		$return = false;
		if ($xhr->getLastResponse()->isSuccessful()) {
			$return = phpQueryPlugin_WebBrowser::browserReceive($xhr);
			if (isset($self->document->WebBrowserCallback))
				phpQuery::callbackRun(
					$self->document->WebBrowserCallback,
					array($return)
				);
		}
		return $return;
	}
        
        
        public static function download($self, $url = null) {
            $xhr = isset($self->document->xhr)
			? $self->document->xhr
			: null;
		$xhr = phpQuery::ajax(array(
			'url' => $url,
		), $xhr);
		$return = false;
		if ($xhr->getLastResponse()->isSuccessful()) {
			$return = phpQueryPlugin_WebBrowser::browserDownload($xhr);
			if (isset($self->document->WebBrowserCallback))
				phpQuery::callbackRun(
					$self->document->WebBrowserCallback,
					array($return)
				);
		}
		return $return;
        }
}
class phpQueryPlugin_WebBrowser {
	
	public static function browserGet($url, $callback,
		$param1 = null, $param2 = null, $param3 = null) {
		phpQuery::debug("[WebBrowser] GET: $url");
		self::authorizeHost($url);
		$xhr = phpQuery::ajax(array(
			'type' => 'GET',
			'url' => $url,
			'dataType' => 'html',
		));
		$paramStructure = null;
		if (func_num_args() > 2) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 2);
		}
		if ($xhr->getLastResponse()->isSuccessful()) {
			phpQuery::callbackRun($callback,
				array(self::browserReceive($xhr)->WebBrowser()),
				$paramStructure
			);
//			phpQuery::callbackRun($callback, array(
//				self::browserReceive($xhr)//->WebBrowser($callback)
//			));
			return $xhr;
		} else {
			throw new Exception("[WebBrowser] GET request failed; url: $url");
			return false;
		}
	}
	
	public static function browserPost($url, $data, $callback,
		$param1 = null, $param2 = null, $param3 = null) {
		self::authorizeHost($url);
		$xhr = phpQuery::ajax(array(
			'type' => 'POST',
			'url' => $url,
			'dataType' => 'html',
			'data' => $data,
		));
		$paramStructure = null;
		if (func_num_args() > 3) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 3);
		}
		if ($xhr->getLastResponse()->isSuccessful()) {
			phpQuery::callbackRun($callback,
				array(self::browserReceive($xhr)->WebBrowser()),
				$paramStructure
			);
//			phpQuery::callbackRun($callback, array(
//				self::browserReceive($xhr)//->WebBrowser($callback)
//			));
			return $xhr;
		} else
			return false;
	}
	
	public static function browser($ajaxSettings, $callback,
		$param1 = null, $param2 = null, $param3 = null) {
		self::authorizeHost($ajaxSettings['url']);
		$xhr = phpQuery::ajax(
			self::ajaxSettingsPrepare($ajaxSettings)
		);
		$paramStructure = null;
		if (func_num_args() > 2) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 2);
		}
		if ($xhr->getLastResponse()->isSuccessful()) {
			phpQuery::callbackRun($callback,
				array(self::browserReceive($xhr)->WebBrowser()),
				$paramStructure
			);
//			phpQuery::callbackRun($callback, array(
//				self::browserReceive($xhr)//->WebBrowser($callback)
//			));
			return $xhr;
		} else
			return false;
	}
	protected static function authorizeHost($url) {
		$host = parse_url($url, PHP_URL_HOST);
		if ($host)
			phpQuery::ajaxAllowHost($host);
	}
	protected static function ajaxSettingsPrepare($settings) {
		unset($settings['success']);
		unset($settings['error']);
		return $settings;
	}
	
	public static function browserReceive($xhr) {
		phpQuery::debug("[WebBrowser] Received from ".$xhr->getUri(true));
		
		$body = $xhr->getLastResponse()->getBody();

		
		if (strpos($body, '<!doctype html>') !== false) {
			$body = '<html>'
				.str_replace('<!doctype html>', '', $body)
				.'</html>';
		}
		$pq = phpQuery::newDocument($body);
		$pq->document->xhr = $xhr;
		$pq->document->location = $xhr->getUri(true);
		$refresh = $pq->find('meta[http-equiv=refresh]')
			->add('meta[http-equiv=Refresh]');
		if ($refresh->size()) {
//			print htmlspecialchars(var_export($xhr->getCookieJar()->getAllCookies(), true));
//			print htmlspecialchars(var_export($xhr->getLastResponse()->getHeader('Set-Cookie'), true));
			phpQuery::debug("Meta redirect... '{$refresh->attr('content')}'\n");
			
			$content = $refresh->attr('content');
			$urlRefresh = substr($content, strpos($content, '=')+1);
			$urlRefresh = trim($urlRefresh, '\'"');
			
			phpQuery::ajaxAllowURL($urlRefresh);
//			$urlRefresh = urldecode($urlRefresh);
			
			$xhr = phpQuery::ajax(array(
				'type' => 'GET',
				'url' => $urlRefresh,
				'dataType' => 'html',
			), $xhr);
			if ($xhr->getLastResponse()->isSuccessful()) {
				
				return call_user_func_array(
					array('phpQueryPlugin_WebBrowser', 'browserReceive'), array($xhr)
				);
			}
		} else
			return $pq;
	}
        
        
	public static function browserDownload($xhr) {
		phpQuery::debug("[WebBrowser] Received from ".$xhr->getUri(true));
		
		$body = $xhr->getLastResponse()->getBody();

		return $body;
	}
	
	public static function hadleClick($e, $callback = null) {
		$node = phpQuery::pq($e->target);
		$type = null;
		if ($node->is('a[href]')) {
			
			$xhr = isset($node->document->xhr)
				? $node->document->xhr
				: null;
			$xhr = phpQuery::ajax(array(
				'url' => resolve_url($e->data[0], $node->attr('href')),
				'referer' => $node->document->location,
			), $xhr);
			if ((! $callback || !($callback instanceof Callback)) && $e->data[1])
				$callback = $e->data[1];
			if ($xhr->getLastResponse()->isSuccessful() && $callback)
				phpQuery::callbackRun($callback, array(
					self::browserReceive($xhr)
				));
		} else if ($node->is(':submit') && $node->parents('form')->size())
			$node->parents('form')->trigger('submit', array($e));
	}
	
	public static function handleSubmit($e, $callback = null) {
		$node = phpQuery::pq($e->target);
		if (!$node->is('form') || !$node->is('[action]'))
			return;
		
		$xhr = isset($node->document->xhr)
			? $node->document->xhr
			: null;
		$submit = pq($e->relatedTarget)->is(':submit')
			? $e->relatedTarget
				
//			: $node->find(':submit:first')->get(0);
			: $node->find('*:submit:first')->get(0);
		$data = array();
		foreach($node->serializeArray($submit) as $r)
		
//		foreach($node->serializeArray($submit) as $r)
			$data[ $r['name'] ] = $r['value'];
		$options = array(
			'type' => $node->attr('method')
				? $node->attr('method')
				: 'GET',
			'url' => resolve_url($e->data[0], $node->attr('action')),
			'data' => $data,
			'referer' => $node->document->location,
//			'success' => $e->data[1],
		);
		if ($node->attr('enctype'))
			$options['contentType'] = $node->attr('enctype');
		$xhr = phpQuery::ajax($options, $xhr);
		if ((! $callback || !($callback instanceof Callback)) && $e->data[1])
			$callback = $e->data[1];
		if ($xhr->getLastResponse()->isSuccessful() && $callback)
			phpQuery::callbackRun($callback, array(
				self::browserReceive($xhr)
			));
	}
}

function glue_url($parsed)
    {
    if (! is_array($parsed)) return false;
    $uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '':'//'): '';
    $uri .= isset($parsed['user']) ? $parsed['user'].($parsed['pass']? ':'.$parsed['pass']:'').'@':'';
    $uri .= isset($parsed['host']) ? $parsed['host'] : '';
    $uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
    if(isset($parsed['path']))
        {
        $uri .= (substr($parsed['path'],0,1) == '/')?$parsed['path']:'/'.$parsed['path'];
        }
    $uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
    $uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
    return $uri;
    }

function resolve_url($base, $url) {
        if (!strlen($base)) return $url;
        
        if (!strlen($url)) return $base;
        
        if (preg_match('!^[a-z]+:!i', $url)) return $url;
        $base = parse_url($base);
        if ($url{0} == "#") {
                
                $base['fragment'] = substr($url, 1);
                return unparse_url($base);
        }
        unset($base['fragment']);
        unset($base['query']);
        if (substr($url, 0, 2) == "//") {
                
                return unparse_url(array(
                        'scheme'=>$base['scheme'],
                        'path'=>substr($url,2),
                ));
        } else if ($url{0} == "/") {
                
                $base['path'] = $url;
        } else {
                
                $path = explode('/', $base['path']);
                $url_path = explode('/', $url);
                
                array_pop($path);
                
                
                $end = array_pop($url_path);
                foreach ($url_path as $segment) {
                        if ($segment == '.') {
                                
                        } else if ($segment == '..' && $path && $path[sizeof($path)-1] != '..') {
                                array_pop($path);
                        } else {
                                $path[] = $segment;
                        }
                }
                
                if ($end == '.') {
                        $path[] = '';
                } else if ($end == '..' && $path && $path[sizeof($path)-1] != '..') {
                        $path[sizeof($path)-1] = '';
                } else {
                        $path[] = $end;
                }
                
                $base['path'] = join('/', $path);

        }
        
        return glue_url($base);
}
