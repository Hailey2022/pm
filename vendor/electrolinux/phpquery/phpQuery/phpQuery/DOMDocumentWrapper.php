<?php

class DOMDocumentWrapper {
	
	public $document;
	public $id;
	
	public $contentType = '';
	public $xpath;
	public $uuid = 0;
	public $data = array();
	public $dataNodes = array();
	public $events = array();
	public $eventsNodes = array();
	public $eventsGlobal = array();
	
	public $frames = array();
	
	public $root;
	public $isDocumentFragment;
	public $isXML = false;
	public $isXHTML = false;
	public $isHTML = false;
	public $charset;
	public function __construct($markup = null, $contentType = null, $newDocumentID = null) {
		if (isset($markup))
			$this->load($markup, $contentType, $newDocumentID);
		$this->id = $newDocumentID
			? $newDocumentID
			: md5(microtime());
	}
	public function load($markup, $contentType = null, $newDocumentID = null) {
//		phpQuery::$documents[$id] = $this;
		$this->contentType = strtolower($contentType);
		if ($markup instanceof DOMDOCUMENT) {
			$this->document = $markup;
			$this->root = $this->document;
			$this->charset = $this->document->encoding;
			
			$loaded = true;
		} else {
			$loaded = $this->loadMarkup($markup);
		}
		if ($loaded) {
//			$this->document->formatOutput = true;
			$this->document->preserveWhiteSpace = true;
			$this->xpath = new DOMXPath($this->document);
			$this->afterMarkupLoad();
			return true;
			
//			return phpQuery::selectDocument($id);
		}
		return false;
	}
	protected function afterMarkupLoad() {
		if ($this->isXHTML) {
			$this->xpath->registerNamespace("html", "http://www.w3.org/1999/xhtml");
		}
	}
	protected function loadMarkup($markup) {
		$loaded = false;
		if ($this->contentType) {
			self::debug("Load markup for content type {$this->contentType}");
			
			list($contentType, $charset) = $this->contentTypeToArray($this->contentType);
			switch($contentType) {
				case 'text/html':
					phpQuery::debug("Loading HTML, content type '{$this->contentType}'");
					$loaded = $this->loadMarkupHTML($markup, $charset);
				break;
				case 'text/xml':
				case 'application/xhtml+xml':
					phpQuery::debug("Loading XML, content type '{$this->contentType}'");
					$loaded = $this->loadMarkupXML($markup, $charset);
				break;
				default:
					
					if (strpos('xml', $this->contentType) !== false) {
						phpQuery::debug("Loading XML, content type '{$this->contentType}'");
						$loaded = $this->loadMarkupXML($markup, $charset);
					} else
						phpQuery::debug("Could not determine document type from content type '{$this->contentType}'");
			}
		} else {
			
			if ($this->isXML($markup)) {
				phpQuery::debug("Loading XML, isXML() == true");
				$loaded = $this->loadMarkupXML($markup);
				if (! $loaded && $this->isXHTML) {
					phpQuery::debug('Loading as XML failed, trying to load as HTML, isXHTML == true');
					$loaded = $this->loadMarkupHTML($markup);
				}
			} else {
				phpQuery::debug("Loading HTML, isXML() == false");
				$loaded = $this->loadMarkupHTML($markup);
			}
		}
		return $loaded;
	}
	protected function loadMarkupReset() {
		$this->isXML = $this->isXHTML = $this->isHTML = false;
	}
	protected function documentCreate($charset, $version = '1.0') {
		if (! $version)
			$version = '1.0';
		$this->document = new DOMDocument($version, $charset);
		$this->charset = $this->document->encoding;
//		$this->document->encoding = $charset;
		$this->document->formatOutput = true;
		$this->document->preserveWhiteSpace = true;
	}
	protected function loadMarkupHTML($markup, $requestedCharset = null) {
		if (phpQuery::$debug)
			phpQuery::debug('Full markup load (HTML): '.substr($markup, 0, 250));
		$this->loadMarkupReset();
		$this->isHTML = true;
		if (!isset($this->isDocumentFragment))
			$this->isDocumentFragment = self::isDocumentFragmentHTML($markup);
		$charset = null;
		$documentCharset = $this->charsetFromHTML($markup);
		$addDocumentCharset = false;
		if ($documentCharset) {
			$charset = $documentCharset;
			$markup = $this->charsetFixHTML($markup);
		} else if ($requestedCharset) {
			$charset = $requestedCharset;
		}
		if (! $charset)
			$charset = phpQuery::$defaultCharset;
		
		
		if (! $documentCharset) {
			$documentCharset = 'ISO-8859-1';
			$addDocumentCharset = true;	
		}
		
		
		$requestedCharset = strtoupper($requestedCharset);
		$documentCharset = strtoupper($documentCharset);
		phpQuery::debug("DOC: $documentCharset REQ: $requestedCharset");
		if ($requestedCharset && $documentCharset && $requestedCharset !== $documentCharset) {
			phpQuery::debug("CHARSET CONVERT");
			
			
			if (function_exists('mb_detect_encoding')) {
				$possibleCharsets = array($documentCharset, $requestedCharset, 'AUTO');
				$docEncoding = mb_detect_encoding($markup, implode(', ', $possibleCharsets));
				if (! $docEncoding)
					$docEncoding = $documentCharset; 
				phpQuery::debug("DETECTED '$docEncoding'");
				
				if ($docEncoding !== $documentCharset) {
					
				}
				if ($docEncoding !== $requestedCharset) {
					phpQuery::debug("CONVERT $docEncoding => $requestedCharset");
					$markup = mb_convert_encoding($markup, $requestedCharset, $docEncoding);
					$markup = $this->charsetAppendToHTML($markup, $requestedCharset);
					$charset = $requestedCharset;
				}
			} else {
				phpQuery::debug("TODO: charset conversion without mbstring...");
			}
		}
		$return = false;
		if ($this->isDocumentFragment) {
			phpQuery::debug("Full markup load (HTML), DocumentFragment detected, using charset '$charset'");
			$return = $this->documentFragmentLoadMarkup($this, $charset, $markup);
		} else {
			if ($addDocumentCharset) {
				phpQuery::debug("Full markup load (HTML), appending charset: '$charset'");
				$markup = $this->charsetAppendToHTML($markup, $charset);
			}
			phpQuery::debug("Full markup load (HTML), documentCreate('$charset')");
			$this->documentCreate($charset);
			$return = phpQuery::$debug === 2
				? $this->document->loadHTML($markup)
				: @$this->document->loadHTML($markup);
			if ($return)
				$this->root = $this->document;
		}
		if ($return && ! $this->contentType)
			$this->contentType = 'text/html';
		return $return;
	}
	protected function loadMarkupXML($markup, $requestedCharset = null) {
		if (phpQuery::$debug)
			phpQuery::debug('Full markup load (XML): '.substr($markup, 0, 250));
		$this->loadMarkupReset();
		$this->isXML = true;
		
		$isContentTypeXHTML = $this->isXHTML();
		$isMarkupXHTML = $this->isXHTML($markup);
		if ($isContentTypeXHTML || $isMarkupXHTML) {
			self::debug('Full markup load (XML), XHTML detected');
			$this->isXHTML = true;
		}
		
		if (! isset($this->isDocumentFragment))
			$this->isDocumentFragment = $this->isXHTML
				? self::isDocumentFragmentXHTML($markup)
				: self::isDocumentFragmentXML($markup);
		
		$charset = null;
		
		$documentCharset = $this->charsetFromXML($markup);
		if (! $documentCharset) {
			if ($this->isXHTML) {
				
				$documentCharset = $this->charsetFromHTML($markup);
				if ($documentCharset) {
					phpQuery::debug("Full markup load (XML), appending XHTML charset '$documentCharset'");
					$this->charsetAppendToXML($markup, $documentCharset);
					$charset = $documentCharset;
				}
			}
			if (! $documentCharset) {
				
				$charset = $requestedCharset;
			}
		} else if ($requestedCharset) {
			$charset = $requestedCharset;
		}
		if (! $charset) {
			$charset = phpQuery::$defaultCharset;
		}
		if ($requestedCharset && $documentCharset && $requestedCharset != $documentCharset) {
			
//			$charset = $requestedCharset;
		}
		$return = false;
		if ($this->isDocumentFragment) {
			phpQuery::debug("Full markup load (XML), DocumentFragment detected, using charset '$charset'");
			$return = $this->documentFragmentLoadMarkup($this, $charset, $markup);
		} else {
			
			if ($isContentTypeXHTML && ! $isMarkupXHTML)
			if (! $documentCharset) {
				phpQuery::debug("Full markup load (XML), appending charset '$charset'");
				$markup = $this->charsetAppendToXML($markup, $charset);
			}
			
			
			
	//		$this->document->resolveExternals = true;
			
			
			$this->documentCreate($charset);
			if (phpversion() < 5.1) {
				$this->document->resolveExternals = true;
				$return = phpQuery::$debug === 2
					? $this->document->loadXML($markup)
					: @$this->document->loadXML($markup);
			} else {
				
				$libxmlStatic = phpQuery::$debug === 2
					? LIBXML_DTDLOAD|LIBXML_DTDATTR|LIBXML_NONET
					: LIBXML_DTDLOAD|LIBXML_DTDATTR|LIBXML_NONET|LIBXML_NOWARNING|LIBXML_NOERROR;
				$return = $this->document->loadXML($markup, $libxmlStatic);


			}
			if ($return)
				$this->root = $this->document;
		}
		if ($return) {
			if (! $this->contentType) {
				if ($this->isXHTML)
					$this->contentType = 'application/xhtml+xml';
				else
					$this->contentType = 'text/xml';
			}
			return $return;
		} else {
			throw new Exception("Error loading XML markup");
		}
	}
	protected function isXHTML($markup = null) {
		if (! isset($markup)) {
			return strpos($this->contentType, 'xhtml') !== false;
		}
		
		return strpos($markup, "<!DOCTYPE html") !== false;
//		return stripos($doctype, 'xhtml') !== false;
//		$doctype = isset($dom->doctype) && is_object($dom->doctype)
//			? $dom->doctype->publicId
//			: self::$defaultDoctype;
	}
	protected function isXML($markup) {
//		return strpos($markup, '<?xml') !== false && stripos($markup, 'xhtml') === false;
		return strpos(substr($markup, 0, 100), '<'.'?xml') !== false;
	}

	protected function contentTypeToArray($contentType) {
        $test = null;
        $test =
		$matches = explode(';', trim(strtolower($contentType)));
		if (isset($matches[1])) {
			$matches[1] = explode('=', $matches[1]);
			
			$matches[1] = isset($matches[1][1]) && trim($matches[1][1])
				? $matches[1][1]
				: $matches[1][0];
		} else
			$matches[1] = null;
		return $matches;
	}
	
	protected function contentTypeFromHTML($markup) {
		$matches = array();
		
		preg_match('@<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i',
			$markup, $matches
		);
		if (! isset($matches[0]))
			return array(null, null);
		
		preg_match('@content\\s*=\\s*(["|\'])(.+?)\\1@', $matches[0], $matches);
		if (! isset($matches[0]))
			return array(null, null);
		return $this->contentTypeToArray($matches[2]);
	}
	protected function charsetFromHTML($markup) {
		$contentType = $this->contentTypeFromHTML($markup);
		return $contentType[1];
	}
	protected function charsetFromXML($markup) {
		$matches;
		
		preg_match('@<'.'?xml[^>]+encoding\\s*=\\s*(["|\'])(.*?)\\1@i',
			$markup, $matches
		);
		return isset($matches[2])
			? strtolower($matches[2])
			: null;
	}
	
	protected function charsetFixHTML($markup) {
		$matches = array();
		
		preg_match('@\s*<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i',
			$markup, $matches, PREG_OFFSET_CAPTURE
		);
		if (! isset($matches[0]))
			return;
		$metaContentType = $matches[0][0];
		$markup = substr($markup, 0, $matches[0][1])
			.substr($markup, $matches[0][1]+strlen($metaContentType));
		$headStart = stripos($markup, '<head>');
		$markup = substr($markup, 0, $headStart+6).$metaContentType
			.substr($markup, $headStart+6);
		return $markup;
	}
	protected function charsetAppendToHTML($html, $charset, $xhtml = false) {
		
		$html = preg_replace('@\s*<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i', '', $html);
		$meta = '<meta http-equiv="Content-Type" content="text/html;charset='
			.$charset.'" '
			.($xhtml ? '/' : '')
			.'>';
		if (strpos($html, '<head') === false) {
			if (strpos($html, '<html') === false) {
				return $meta.$html;
			} else {
				return preg_replace(
					'@<html(.*?)(?(?<!\?)>)@s',
					"<html\\1><head>{$meta}</head>",
					$html
				);
			}
		} else {
			return preg_replace(
				'@<head(.*?)(?(?<!\?)>)@s',
				'<head\\1>'.$meta,
				$html
			);
		}
	}
	protected function charsetAppendToXML($markup, $charset) {
		$declaration = '<'.'?xml version="1.0" encoding="'.$charset.'"?'.'>';
		return $declaration.$markup;
	}
	public static function isDocumentFragmentHTML($markup) {
		return stripos($markup, '<html') === false && stripos($markup, '<!doctype') === false;
	}
	public static function isDocumentFragmentXML($markup) {
		return stripos($markup, '<'.'?xml') === false;
	}
	public static function isDocumentFragmentXHTML($markup) {
		return self::isDocumentFragmentHTML($markup);
	}
	public function importAttr($value) {
		
	}
	
	public function import($source, $sourceCharset = null) {
		
		$return = array();
		if ($source instanceof DOMNODE && !($source instanceof DOMNODELIST))
			$source = array($source);
//		if (is_array($source)) {
//			foreach($source as $node) {
//				if (is_string($node)) {
//					
//					$fake = $this->documentFragmentCreate($node, $sourceCharset);
//					if ($fake === false)
//						throw new Exception("Error loading documentFragment markup");
//					else
//						$return = array_merge($return, 
//							$this->import($fake->root->childNodes)
//						);
//				} else {
//					$return[] = $this->document->importNode($node, true);
//				}
//			}
//			return $return;
//		} else {
//			
//			$fake = $this->documentFragmentCreate($source, $sourceCharset);
//			if ($fake === false)
//				throw new Exception("Error loading documentFragment markup");
//			else
//				return $this->import($fake->root->childNodes);
//		}
		if (is_array($source) || $source instanceof DOMNODELIST) {
			
			self::debug('Importing nodes to document');
			foreach($source as $node)
				$return[] = $this->document->importNode($node, true);
		} else {
			
			$fake = $this->documentFragmentCreate($source, $sourceCharset);
			if ($fake === false)
				throw new Exception("Error loading documentFragment markup");
			else
				return $this->import($fake->root->childNodes);
		}
		return $return;
	}
	
	protected function documentFragmentCreate($source, $charset = null) {
		$fake = new DOMDocumentWrapper();
		$fake->contentType = $this->contentType;
		$fake->isXML = $this->isXML;
		$fake->isHTML = $this->isHTML;
		$fake->isXHTML = $this->isXHTML;
		$fake->root = $fake->document;
		if (! $charset)
			$charset = $this->charset;
//	$fake->documentCreate($this->charset);
		if ($source instanceof DOMNODE && !($source instanceof DOMNODELIST))
			$source = array($source);
		if (is_array($source) || $source instanceof DOMNODELIST) {
			
			
			if (! $this->documentFragmentLoadMarkup($fake, $charset))
				return false;
			$nodes = $fake->import($source);
			foreach($nodes as $node)
				$fake->root->appendChild($node);
		} else {
			
			$this->documentFragmentLoadMarkup($fake, $charset, $source);
		}
		return $fake;
	}
	
	private function documentFragmentLoadMarkup($fragment, $charset, $markup = null) {
		
		
		
		$fragment->isDocumentFragment = false;
		if ($fragment->isXML) {
			if ($fragment->isXHTML) {
				
				$fragment->loadMarkupXML('<?xml version="1.0" encoding="'.$charset.'"?>'
					.'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" '
					.'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
					.'<fake xmlns="http://www.w3.org/1999/xhtml">'.$markup.'</fake>');
				$fragment->root = $fragment->document->firstChild->nextSibling;
			} else {
				$fragment->loadMarkupXML('<?xml version="1.0" encoding="'.$charset.'"?><fake>'.$markup.'</fake>');
				$fragment->root = $fragment->document->firstChild;
			}
		} else {
			$markup2 = phpQuery::$defaultDoctype.'<html><head><meta http-equiv="Content-Type" content="text/html;charset='
				.$charset.'"></head>';
			$noBody = strpos($markup, '<body') === false;
			if ($noBody)
				$markup2 .= '<body>';
			$markup2 .= $markup;
			if ($noBody)
				$markup2 .= '</body>';
			$markup2 .= '</html>';
			$fragment->loadMarkupHTML($markup2);
			
			$fragment->root = $noBody
				? $fragment->document->firstChild->nextSibling->firstChild->nextSibling
				: $fragment->document->firstChild->nextSibling->firstChild->nextSibling;
		}
		if (! $fragment->root)
			return false;
		$fragment->isDocumentFragment = true;
		return true;
	}
	protected function documentFragmentToMarkup($fragment) {
		phpQuery::debug('documentFragmentToMarkup');
		$tmp = $fragment->isDocumentFragment;
		$fragment->isDocumentFragment = false;
		$markup = $fragment->markup();
		if ($fragment->isXML) {
			$markup = substr($markup, 0, strrpos($markup, '</fake>'));
			if ($fragment->isXHTML) {
				$markup = substr($markup, strpos($markup, '<fake')+43);
			} else {
				$markup = substr($markup, strpos($markup, '<fake>')+6);
			}
		} else {
				$markup = substr($markup, strpos($markup, '<body>')+6);
				$markup = substr($markup, 0, strrpos($markup, '</body>'));
		}
		$fragment->isDocumentFragment = $tmp;
		if (phpQuery::$debug)
			phpQuery::debug('documentFragmentToMarkup: '.substr($markup, 0, 150));
		return $markup;
	}
	
	public function markup($nodes = null, $innerMarkup = false) {
		if (isset($nodes) && count($nodes) == 1 && $nodes[0] instanceof DOMDOCUMENT)
			$nodes = null;
		if (isset($nodes)) {
			$markup = '';
			if (!is_array($nodes) && !($nodes instanceof DOMNODELIST) )
				$nodes = array($nodes);
			if ($this->isDocumentFragment && ! $innerMarkup)
				foreach($nodes as $i => $node)
					if ($node->isSameNode($this->root)) {
					//	var_dump($node);
						$nodes = array_slice($nodes, 0, $i)
							+ phpQuery::DOMNodeListToArray($node->childNodes)
							+ array_slice($nodes, $i+1);
						}
			if ($this->isXML && ! $innerMarkup) {
				self::debug("Getting outerXML with charset '{$this->charset}'");
				
				
				foreach($nodes as $node)
					$markup .= $this->document->saveXML($node);
			} else {
				$loop = array();
				if ($innerMarkup)
					foreach($nodes as $node) {
						if ($node->childNodes)
							foreach($node->childNodes as $child)
								$loop[] = $child;
						else
							$loop[] = $node;
					}
				else
					$loop = $nodes;
				self::debug("Getting markup, moving selected nodes (".count($loop).") to new DocumentFragment");
				$fake = $this->documentFragmentCreate($loop);
				$markup = $this->documentFragmentToMarkup($fake);
			}
			if ($this->isXHTML) {
				self::debug("Fixing XHTML");
				$markup = self::markupFixXHTML($markup);
			}
			self::debug("Markup: ".substr($markup, 0, 250));
			return $markup;
		} else {
			if ($this->isDocumentFragment) {
				
				self::debug("Getting markup, DocumentFragment detected");
//				return $this->markup(
////					$this->document->getElementsByTagName('body')->item(0)
//					$this->document->root, true
//				);
				$markup = $this->documentFragmentToMarkup($this);
				
				return $markup;
			} else {
				self::debug("Getting markup (".($this->isXML?'XML':'HTML')."), final with charset '{$this->charset}'");
				$markup = $this->isXML
					? $this->document->saveXML()
					: $this->document->saveHTML();
				if ($this->isXHTML) {
					self::debug("Fixing XHTML");
					$markup = self::markupFixXHTML($markup);
				}
				self::debug("Markup: ".substr($markup, 0, 250));
				return $markup;
			}
		}
	}
	protected static function markupFixXHTML($markup) {
		$markup = self::expandEmptyTag('script', $markup);
		$markup = self::expandEmptyTag('select', $markup);
		$markup = self::expandEmptyTag('textarea', $markup);
		return $markup;
	}
	public static function debug($text) {
		phpQuery::debug($text);
	}
	
	public static function expandEmptyTag($tag, $xml){
        $indice = 0;
        while ($indice< strlen($xml)){
            $pos = strpos($xml, "<$tag ", $indice);
            if ($pos){
                $posCierre = strpos($xml, ">", $pos);
                if ($xml[$posCierre-1] == "/"){
                    $xml = substr_replace($xml, "></$tag>", $posCierre-1, 2);
                }
                $indice = $posCierre;
            }
            else break;
        }
        return $xml;
	}
}
