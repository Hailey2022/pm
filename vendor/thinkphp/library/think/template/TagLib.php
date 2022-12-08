<?php










namespace think\template;

use think\Exception;


class TagLib
{

    
    protected $xml  = '';
    protected $tags = []; 
    
    protected $tagLib = '';

    
    protected $tagList = [];

    
    protected $parse = [];

    
    protected $valid = false;

    
    protected $tpl;

    protected $comparison = [' nheq ' => ' !== ', ' heq ' => ' === ', ' neq ' => ' != ', ' eq ' => ' == ', ' egt ' => ' >= ', ' gt ' => ' > ', ' elt ' => ' <= ', ' lt ' => ' < '];

    
    public function __construct($template)
    {
        $this->tpl = $template;
    }

    
    public function parseTag(&$content, $lib = '')
    {
        $tags = [];
        $lib  = $lib ? strtolower($lib) . ':' : '';

        foreach ($this->tags as $name => $val) {
            $close                      = !isset($val['close']) || $val['close'] ? 1 : 0;
            $tags[$close][$lib . $name] = $name;
            if (isset($val['alias'])) {
                
                $array = (array) $val['alias'];
                foreach (explode(',', $array[0]) as $v) {
                    $tags[$close][$lib . $v] = $name;
                }
            }
        }

        
        if (!empty($tags[1])) {
            $nodes = [];
            $regex = $this->getRegex(array_keys($tags[1]), 1);
            if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                $right = [];
                foreach ($matches as $match) {
                    if ('' == $match[1][0]) {
                        $name = strtolower($match[2][0]);
                        
                        if (!empty($right[$name])) {
                            
                            $nodes[$match[0][1]] = [
                                'name'  => $name,
                                'begin' => array_pop($right[$name]), 
                                'end'   => $match[0], 
                            ];
                        }
                    } else {
                        
                        $right[strtolower($match[1][0])][] = $match[0];
                    }
                }
                unset($right, $matches);
                
                krsort($nodes);
            }

            $break = '<!--###break###--!>';
            if ($nodes) {
                $beginArray = [];
                
                foreach ($nodes as $pos => $node) {
                    
                    $name  = $tags[1][$node['name']];
                    $alias = $lib . $name != $node['name'] ? ($lib ? strstr($node['name'], $lib) : $node['name']) : '';

                    
                    $attrs  = $this->parseAttr($node['begin'][0], $name, $alias);
                    $method = 'tag' . $name;

                    
                    $replace = explode($break, $this->$method($attrs, $break));

                    if (count($replace) > 1) {
                        while ($beginArray) {
                            $begin = end($beginArray);
                            
                            if ($node['end'][1] > $begin['pos']) {
                                break;
                            } else {
                                
                                $begin = array_pop($beginArray);
                                
                                $content = substr_replace($content, $begin['str'], $begin['pos'], $begin['len']);
                            }
                        }
                        
                        $content = substr_replace($content, $replace[1], $node['end'][1], strlen($node['end'][0]));
                        
                        $beginArray[] = ['pos' => $node['begin'][1], 'len' => strlen($node['begin'][0]), 'str' => $replace[0]];
                    }
                }

                while ($beginArray) {
                    $begin = array_pop($beginArray);
                    
                    $content = substr_replace($content, $begin['str'], $begin['pos'], $begin['len']);
                }
            }
        }
        
        if (!empty($tags[0])) {
            $regex   = $this->getRegex(array_keys($tags[0]), 0);
            $content = preg_replace_callback($regex, function ($matches) use (&$tags, &$lib) {
                
                $name  = $tags[0][strtolower($matches[1])];
                $alias = $lib . $name != $matches[1] ? ($lib ? strstr($matches[1], $lib) : $matches[1]) : '';
                
                $attrs  = $this->parseAttr($matches[0], $name, $alias);
                $method = 'tag' . $name;
                return $this->$method($attrs, '');
            }, $content);
        }

        return;
    }

    
    public function getRegex($tags, $close)
    {
        $begin   = $this->tpl->config('taglib_begin');
        $end     = $this->tpl->config('taglib_end');
        $single  = strlen(ltrim($begin, '\\')) == 1 && strlen(ltrim($end, '\\')) == 1 ? true : false;
        $tagName = is_array($tags) ? implode('|', $tags) : $tags;

        if ($single) {
            if ($close) {
                
                $regex = $begin . '(?:(' . $tagName . ')\b(?>[^' . $end . ']*)|\/(' . $tagName . '))' . $end;
            } else {
                $regex = $begin . '(' . $tagName . ')\b(?>[^' . $end . ']*)' . $end;
            }
        } else {
            if ($close) {
                
                $regex = $begin . '(?:(' . $tagName . ')\b(?>(?:(?!' . $end . ').)*)|\/(' . $tagName . '))' . $end;
            } else {
                $regex = $begin . '(' . $tagName . ')\b(?>(?:(?!' . $end . ').)*)' . $end;
            }
        }

        return '/' . $regex . '/is';
    }

    
    public function parseAttr($str, $name, $alias = '')
    {
        $regex  = '/\s+(?>(?P<name>[\w-]+)\s*)=(?>\s*)([\"\'])(?P<value>(?:(?!\\2).)*)\\2/is';
        $result = [];

        if (preg_match_all($regex, $str, $matches)) {
            foreach ($matches['name'] as $key => $val) {
                $result[$val] = $matches['value'][$key];
            }

            if (!isset($this->tags[$name])) {
                
                foreach ($this->tags as $key => $val) {
                    if (isset($val['alias'])) {
                        $array = (array) $val['alias'];
                        if (in_array($name, explode(',', $array[0]))) {
                            $tag           = $val;
                            $type          = !empty($array[1]) ? $array[1] : 'type';
                            $result[$type] = $name;
                            break;
                        }
                    }
                }
            } else {
                $tag = $this->tags[$name];
                
                if (!empty($alias) && isset($tag['alias'])) {
                    $type          = !empty($tag['alias'][1]) ? $tag['alias'][1] : 'type';
                    $result[$type] = $alias;
                }
            }

            if (!empty($tag['must'])) {
                $must = explode(',', $tag['must']);
                foreach ($must as $name) {
                    if (!isset($result[$name])) {
                        throw new Exception('tag attr must:' . $name);
                    }
                }
            }
        } else {
            
            if (!empty($this->tags[$name]['expression'])) {
                static $_taglibs;
                if (!isset($_taglibs[$name])) {
                    $_taglibs[$name][0] = strlen($this->tpl->config('taglib_begin_origin') . $name);
                    $_taglibs[$name][1] = strlen($this->tpl->config('taglib_end_origin'));
                }
                $result['expression'] = substr($str, $_taglibs[$name][0], -$_taglibs[$name][1]);
                
                $result['expression'] = rtrim($result['expression'], '/');
                $result['expression'] = trim($result['expression']);
            } elseif (empty($this->tags[$name]) || !empty($this->tags[$name]['attr'])) {
                throw new Exception('tag error:' . $name);
            }
        }

        return $result;
    }

    
    public function parseCondition($condition)
    {
        if (!strpos($condition, '::') && strpos($condition, ':')) {
            $condition = ' ' . substr(strstr($condition, ':'), 1);
        }

        $condition = str_ireplace(array_keys($this->comparison), array_values($this->comparison), $condition);
        $this->tpl->parseVar($condition);

        
        return $condition;
    }

    
    public function autoBuildVar(&$name)
    {
        $flag = substr($name, 0, 1);

        if (':' == $flag) {
            
            $name = substr($name, 1);
        } elseif ('$' != $flag && preg_match('/[a-zA-Z_]/', $flag)) {
            
            
            if (defined($name)) {
                return $name;
            }

            
            $name = '$' . $name;
        }

        $this->tpl->parseVar($name);
        $this->tpl->parseVarFunction($name, false);

        return $name;
    }

    
    public function getTags()
    {
        return $this->tags;
    }
}
