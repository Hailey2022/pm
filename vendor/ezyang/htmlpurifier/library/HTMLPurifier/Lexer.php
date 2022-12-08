<?php


class HTMLPurifier_Lexer
{

    
    public $tracksLineNumbers = false;

    

    
    public static function create($config)
    {
        if (!($config instanceof HTMLPurifier_Config)) {
            $lexer = $config;
            trigger_error(
                "Passing a prototype to
                HTMLPurifier_Lexer::create() is deprecated, please instead
                use %Core.LexerImpl",
                E_USER_WARNING
            );
        } else {
            $lexer = $config->get('Core.LexerImpl');
        }

        $needs_tracking =
            $config->get('Core.MaintainLineNumbers') ||
            $config->get('Core.CollectErrors');

        $inst = null;
        if (is_object($lexer)) {
            $inst = $lexer;
        } else {
            if (is_null($lexer)) {
                do {
                    
                    if ($needs_tracking) {
                        $lexer = 'DirectLex';
                        break;
                    }

                    if (class_exists('DOMDocument', false) &&
                        method_exists('DOMDocument', 'loadHTML') &&
                        !extension_loaded('domxml')
                    ) {
                        
                        
                        
                        
                        $lexer = 'DOMLex';
                    } else {
                        $lexer = 'DirectLex';
                    }
                } while (0);
            } 

            
            switch ($lexer) {
                case 'DOMLex':
                    $inst = new HTMLPurifier_Lexer_DOMLex();
                    break;
                case 'DirectLex':
                    $inst = new HTMLPurifier_Lexer_DirectLex();
                    break;
                case 'PH5P':
                    $inst = new HTMLPurifier_Lexer_PH5P();
                    break;
                default:
                    throw new HTMLPurifier_Exception(
                        "Cannot instantiate unrecognized Lexer type " .
                        htmlspecialchars($lexer)
                    );
            }
        }

        if (!$inst) {
            throw new HTMLPurifier_Exception('No lexer was instantiated');
        }

        
        
        if ($needs_tracking && !$inst->tracksLineNumbers) {
            throw new HTMLPurifier_Exception(
                'Cannot use lexer that does not support line numbers with ' .
                'Core.MaintainLineNumbers or Core.CollectErrors (use DirectLex instead)'
            );
        }

        return $inst;

    }

    

    public function __construct()
    {
        $this->_entity_parser = new HTMLPurifier_EntityParser();
    }

    
    protected $_special_entity2str =
        array(
            '&quot;' => '"',
            '&amp;' => '&',
            '&lt;' => '<',
            '&gt;' => '>',
            '&#39;' => "'",
            '&#039;' => "'",
            '&#x27;' => "'"
        );

    public function parseText($string, $config) {
        return $this->parseData($string, false, $config);
    }

    public function parseAttr($string, $config) {
        return $this->parseData($string, true, $config);
    }

    
    public function parseData($string, $is_attr, $config)
    {
        
        if ($string === '') {
            return '';
        }

        
        $num_amp = substr_count($string, '&') - substr_count($string, '& ') -
            ($string[strlen($string) - 1] === '&' ? 1 : 0);

        if (!$num_amp) {
            return $string;
        } 
        $num_esc_amp = substr_count($string, '&amp;');
        $string = strtr($string, $this->_special_entity2str);

        
        $num_amp_2 = substr_count($string, '&') - substr_count($string, '& ') -
            ($string[strlen($string) - 1] === '&' ? 1 : 0);

        if ($num_amp_2 <= $num_esc_amp) {
            return $string;
        }

        
        if ($config->get('Core.LegacyEntityDecoder')) {
            $string = $this->_entity_parser->substituteSpecialEntities($string);
        } else {
            if ($is_attr) {
                $string = $this->_entity_parser->substituteAttrEntities($string);
            } else {
                $string = $this->_entity_parser->substituteTextEntities($string);
            }
        }
        return $string;
    }

    
    public function tokenizeHTML($string, $config, $context)
    {
        trigger_error('Call to abstract class', E_USER_ERROR);
    }

    
    protected static function escapeCDATA($string)
    {
        return preg_replace_callback(
            '/<!\[CDATA\[(.+?)\]\]>/s',
            array('HTMLPurifier_Lexer', 'CDATACallback'),
            $string
        );
    }

    
    protected static function escapeCommentedCDATA($string)
    {
        return preg_replace_callback(
            '#<!--//--><!\[CDATA\[//><!--(.+?)//--><!\]\]>#s',
            array('HTMLPurifier_Lexer', 'CDATACallback'),
            $string
        );
    }

    
    protected static function removeIEConditional($string)
    {
        return preg_replace(
            '#<!--\[if [^>]+\]>.*?<!\[endif\]-->#si', 
            '',
            $string
        );
    }

    
    protected static function CDATACallback($matches)
    {
        
        return htmlspecialchars($matches[1], ENT_COMPAT, 'UTF-8');
    }

    
    public function normalize($html, $config, $context)
    {
        
        if ($config->get('Core.NormalizeNewlines')) {
            $html = str_replace("\r\n", "\n", $html);
            $html = str_replace("\r", "\n", $html);
        }

        if ($config->get('HTML.Trusted')) {
            
            $html = $this->escapeCommentedCDATA($html);
        }

        
        $html = $this->escapeCDATA($html);

        $html = $this->removeIEConditional($html);

        
        if ($config->get('Core.ConvertDocumentToFragment')) {
            $e = false;
            if ($config->get('Core.CollectErrors')) {
                $e =& $context->get('ErrorCollector');
            }
            $new_html = $this->extractBody($html);
            if ($e && $new_html != $html) {
                $e->send(E_WARNING, 'Lexer: Extracted body');
            }
            $html = $new_html;
        }

        
        if ($config->get('Core.LegacyEntityDecoder')) {
            $html = $this->_entity_parser->substituteNonSpecialEntities($html);
        }

        
        
        
        $html = HTMLPurifier_Encoder::cleanUTF8($html);

        
        if ($config->get('Core.RemoveProcessingInstructions')) {
            $html = preg_replace('#<\?.+?\?>#s', '', $html);
        }

        $hidden_elements = $config->get('Core.HiddenElements');
        if ($config->get('Core.AggressivelyRemoveScript') &&
            !($config->get('HTML.Trusted') || !$config->get('Core.RemoveScriptContents')
            || empty($hidden_elements["script"]))) {
            $html = preg_replace('#<script[^>]*>.*?</script>#i', '', $html);
        }

        return $html;
    }

    
    public function extractBody($html)
    {
        $matches = array();
        $result = preg_match('|(.*?)<body[^>]*>(.*)</body>|is', $html, $matches);
        if ($result) {
            
            $comment_start = strrpos($matches[1], '<!--');
            $comment_end   = strrpos($matches[1], '-->');
            if ($comment_start === false ||
                ($comment_end !== false && $comment_end > $comment_start)) {
                return $matches[2];
            }
        }
        return $html;
    }
}


