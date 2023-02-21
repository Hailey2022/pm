<?php
class HTMLPurifier_EntityParser
{
    protected $_entity_lookup;
    protected $_textEntitiesRegex;
    protected $_attrEntitiesRegex;
    protected $_semiOptionalPrefixRegex;
    public function __construct() {
        $semi_optional = "quot|QUOT|lt|LT|gt|GT|amp|AMP|AElig|Aacute|Acirc|Agrave|Aring|Atilde|Auml|COPY|Ccedil|ETH|Eacute|Ecirc|Egrave|Euml|Iacute|Icirc|Igrave|Iuml|Ntilde|Oacute|Ocirc|Ograve|Oslash|Otilde|Ouml|REG|THORN|Uacute|Ucirc|Ugrave|Uuml|Yacute|aacute|acirc|acute|aelig|agrave|aring|atilde|auml|brvbar|ccedil|cedil|cent|copy|curren|deg|divide|eacute|ecirc|egrave|eth|euml|frac12|frac14|frac34|iacute|icirc|iexcl|igrave|iquest|iuml|laquo|macr|micro|middot|nbsp|not|ntilde|oacute|ocirc|ograve|ordf|ordm|oslash|otilde|ouml|para|plusmn|pound|raquo|reg|sect|shy|sup1|sup2|sup3|szlig|thorn|times|uacute|ucirc|ugrave|uml|uuml|yacute|yen|yuml";
        $this->_semiOptionalPrefixRegex = "/&()()()($semi_optional)/";
        $this->_textEntitiesRegex =
            '/&(?:'.
            '[#]x([a-fA-F0-9]+);?|'.
            '[#]0*(\d+);?|'.
            '([A-Za-z_:][A-Za-z0-9.\-_:]*);|'.
            "($semi_optional)".
            ')/';
        $this->_attrEntitiesRegex =
            '/&(?:'.
            '[#]x([a-fA-F0-9]+);?|'.
            '[#]0*(\d+);?|'.
            '([A-Za-z_:][A-Za-z0-9.\-_:]*);|'.
            "($semi_optional)(?![=;A-Za-z0-9])".
            ')/';
    }
    public function substituteTextEntities($string)
    {
        return preg_replace_callback(
            $this->_textEntitiesRegex,
            array($this, 'entityCallback'),
            $string
        );
    }
    public function substituteAttrEntities($string)
    {
        return preg_replace_callback(
            $this->_attrEntitiesRegex,
            array($this, 'entityCallback'),
            $string
        );
    }
    protected function entityCallback($matches)
    {
        $entity = $matches[0];
        $hex_part = @$matches[1];
        $dec_part = @$matches[2];
        $named_part = empty($matches[3]) ? (empty($matches[4]) ? "" : $matches[4]) : $matches[3];
        if ($hex_part !== NULL && $hex_part !== "") {
            return HTMLPurifier_Encoder::unichr(hexdec($hex_part));
        } elseif ($dec_part !== NULL && $dec_part !== "") {
            return HTMLPurifier_Encoder::unichr((int) $dec_part);
        } else {
            if (!$this->_entity_lookup) {
                $this->_entity_lookup = HTMLPurifier_EntityLookup::instance();
            }
            if (isset($this->_entity_lookup->table[$named_part])) {
                return $this->_entity_lookup->table[$named_part];
            } else {
                if (!empty($matches[3])) {
                    return preg_replace_callback(
                        $this->_semiOptionalPrefixRegex,
                        array($this, 'entityCallback'),
                        $entity
                    );
                }
                return $entity;
            }
        }
    }
    protected $_substituteEntitiesRegex =
        '/&(?:[#]x([a-fA-F0-9]+)|[#]0*(\d+)|([A-Za-z_:][A-Za-z0-9.\-_:]*));?/';
    protected $_special_dec2str =
            array(
                    34 => '"',
                    38 => '&',
                    39 => "'",
                    60 => '<',
                    62 => '>'
            );
    protected $_special_ent2dec =
            array(
                    'quot' => 34,
                    'amp'  => 38,
                    'lt'   => 60,
                    'gt'   => 62
            );
    public function substituteNonSpecialEntities($string)
    {
        return preg_replace_callback(
            $this->_substituteEntitiesRegex,
            array($this, 'nonSpecialEntityCallback'),
            $string
        );
    }
    protected function nonSpecialEntityCallback($matches)
    {
        $entity = $matches[0];
        $is_num = (@$matches[0][1] === '#');
        if ($is_num) {
            $is_hex = (@$entity[2] === 'x');
            $code = $is_hex ? hexdec($matches[1]) : (int) $matches[2];
            if (isset($this->_special_dec2str[$code])) {
                return $entity;
            }
            return HTMLPurifier_Encoder::unichr($code);
        } else {
            if (isset($this->_special_ent2dec[$matches[3]])) {
                return $entity;
            }
            if (!$this->_entity_lookup) {
                $this->_entity_lookup = HTMLPurifier_EntityLookup::instance();
            }
            if (isset($this->_entity_lookup->table[$matches[3]])) {
                return $this->_entity_lookup->table[$matches[3]];
            } else {
                return $entity;
            }
        }
    }
    public function substituteSpecialEntities($string)
    {
        return preg_replace_callback(
            $this->_substituteEntitiesRegex,
            array($this, 'specialEntityCallback'),
            $string
        );
    }
    protected function specialEntityCallback($matches)
    {
        $entity = $matches[0];
        $is_num = (@$matches[0][1] === '#');
        if ($is_num) {
            $is_hex = (@$entity[2] === 'x');
            $int = $is_hex ? hexdec($matches[1]) : (int) $matches[2];
            return isset($this->_special_dec2str[$int]) ?
                $this->_special_dec2str[$int] :
                $entity;
        } else {
            return isset($this->_special_ent2dec[$matches[3]]) ?
                $this->_special_dec2str[$this->_special_ent2dec[$matches[3]]] :
                $entity;
        }
    }
}
