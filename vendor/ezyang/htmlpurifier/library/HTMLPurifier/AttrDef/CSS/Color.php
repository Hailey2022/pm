<?php


class HTMLPurifier_AttrDef_CSS_Color extends HTMLPurifier_AttrDef
{

    
    protected $alpha;

    public function __construct()
    {
        $this->alpha = new HTMLPurifier_AttrDef_CSS_AlphaValue();
    }

    
    public function validate($color, $config, $context)
    {
        static $colors = null;
        if ($colors === null) {
            $colors = $config->get('Core.ColorKeywords');
        }

        $color = trim($color);
        if ($color === '') {
            return false;
        }

        $lower = strtolower($color);
        if (isset($colors[$lower])) {
            return $colors[$lower];
        }

        if (preg_match('#(rgb|rgba|hsl|hsla)\(#', $color, $matches) === 1) {
            $length = strlen($color);
            if (strpos($color, ')') !== $length - 1) {
                return false;
            }

            
            $function = $matches[1];

            $parameters_size = 3;
            $alpha_channel = false;
            if (substr($function, -1) === 'a') {
                $parameters_size = 4;
                $alpha_channel = true;
            }

            
            $allowed_types = array(
                1 => array('percentage' => 100, 'integer' => 255),
                2 => array('percentage' => 100, 'integer' => 255),
                3 => array('percentage' => 100, 'integer' => 255),
            );
            $allow_different_types = false;

            if (strpos($function, 'hsl') !== false) {
                $allowed_types = array(
                    1 => array('integer' => 360),
                    2 => array('percentage' => 100),
                    3 => array('percentage' => 100),
                );
                $allow_different_types = true;
            }

            $values = trim(str_replace($function, '', $color), ' ()');

            $parts = explode(',', $values);
            if (count($parts) !== $parameters_size) {
                return false;
            }

            $type = false;
            $new_parts = array();
            $i = 0;

            foreach ($parts as $part) {
                $i++;
                $part = trim($part);

                if ($part === '') {
                    return false;
                }

                
                if ($alpha_channel === true && $i === count($parts)) {
                    $result = $this->alpha->validate($part, $config, $context);

                    if ($result === false) {
                        return false;
                    }

                    $new_parts[] = (string)$result;
                    continue;
                }

                if (substr($part, -1) === '%') {
                    $current_type = 'percentage';
                } else {
                    $current_type = 'integer';
                }

                if (!array_key_exists($current_type, $allowed_types[$i])) {
                    return false;
                }

                if (!$type) {
                    $type = $current_type;
                }

                if ($allow_different_types === false && $type != $current_type) {
                    return false;
                }

                $max_value = $allowed_types[$i][$current_type];

                if ($current_type == 'integer') {
                    
                    $new_parts[] = (int)max(min($part, $max_value), 0);
                } elseif ($current_type == 'percentage') {
                    $new_parts[] = (float)max(min(rtrim($part, '%'), $max_value), 0) . '%';
                }
            }

            $new_values = implode(',', $new_parts);

            $color = $function . '(' . $new_values . ')';
        } else {
            
            if ($color[0] === '#') {
                $hex = substr($color, 1);
            } else {
                $hex = $color;
                $color = '#' . $color;
            }
            $length = strlen($hex);
            if ($length !== 3 && $length !== 6) {
                return false;
            }
            if (!ctype_xdigit($hex)) {
                return false;
            }
        }
        return $color;
    }

}


