<?php










namespace think;

use think\exception\TemplateNotFoundException;


class Template
{
    protected $app;
    
    protected $data = [];

    
    protected $config = [
        'view_path'          => '', 
        'view_base'          => '',
        'view_suffix'        => 'html', 
        'view_depr'          => DIRECTORY_SEPARATOR,
        'cache_suffix'       => 'php', 
        'tpl_deny_func_list' => 'echo,exit', 
        'tpl_deny_php'       => false, 
        'tpl_begin'          => '{', 
        'tpl_end'            => '}', 
        'strip_space'        => false, 
        'tpl_cache'          => true, 
        'compile_type'       => 'file', 
        'cache_prefix'       => '', 
        'cache_time'         => 0, 
        'layout_on'          => false, 
        'layout_name'        => 'layout', 
        'layout_item'        => '{__CONTENT__}', 
        'taglib_begin'       => '{', 
        'taglib_end'         => '}', 
        'taglib_load'        => true, 
        'taglib_build_in'    => 'cx', 
        'taglib_pre_load'    => '', 
        'display_cache'      => false, 
        'cache_id'           => '', 
        'tpl_replace_string' => [],
        'tpl_var_identify'   => 'array', 
        'default_filter'     => 'htmlentities', 
    ];

    
    private $literal = [];

    
    private $includeFile = [];

    
    protected $storage;

    
    public function __construct(App $app, array $config = [])
    {
        $this->app                  = $app;
        $this->config['cache_path'] = $app->getRuntimePath() . 'temp/';
        $this->config               = array_merge($this->config, $config);

        $this->config['taglib_begin_origin'] = $this->config['taglib_begin'];
        $this->config['taglib_end_origin']   = $this->config['taglib_end'];

        $this->config['taglib_begin'] = preg_quote($this->config['taglib_begin'], '/');
        $this->config['taglib_end']   = preg_quote($this->config['taglib_end'], '/');
        $this->config['tpl_begin']    = preg_quote($this->config['tpl_begin'], '/');
        $this->config['tpl_end']      = preg_quote($this->config['tpl_end'], '/');

        
        $type = $this->config['compile_type'] ? $this->config['compile_type'] : 'File';

        $this->storage = Loader::factory($type, '\\think\\template\\driver\\', null);
    }

    public static function __make(Config $config)
    {
        return new static($config->pull('template'));
    }

    
    public function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }
    }

    
    public function __set($name, $value)
    {
        $this->config[$name] = $value;
    }

    
    public function config($config)
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        } elseif (isset($this->config[$config])) {
            return $this->config[$config];
        }
    }

    
    public function get($name = '')
    {
        if ('' == $name) {
            return $this->data;
        }

        $data = $this->data;

        foreach (explode('.', $name) as $key => $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                $data = null;
                break;
            }
        }

        return $data;
    }

    
    public function fetch($template, $vars = [], $config = [])
    {
        if ($vars) {
            $this->data = $vars;
        }

        if ($config) {
            $this->config($config);
        }

        $cache = $this->app['cache'];

        if (!empty($this->config['cache_id']) && $this->config['display_cache']) {
            
            $cacheContent = $cache->get($this->config['cache_id']);

            if (false !== $cacheContent) {
                echo $cacheContent;
                return;
            }
        }

        $template = $this->parseTemplateFile($template);

        if ($template) {
            $cacheFile = $this->config['cache_path'] . $this->config['cache_prefix'] . md5($this->config['layout_on'] . $this->config['layout_name'] . $template) . '.' . ltrim($this->config['cache_suffix'], '.');

            if (!$this->checkCache($cacheFile)) {
                
                $content = file_get_contents($template);
                $this->compiler($content, $cacheFile);
            }

            
            ob_start();
            ob_implicit_flush(0);

            
            $this->storage->read($cacheFile, $this->data);

            
            $content = ob_get_clean();

            if (!empty($this->config['cache_id']) && $this->config['display_cache']) {
                
                $cache->set($this->config['cache_id'], $content, $this->config['cache_time']);
            }

            echo $content;
        }
    }

    
    public function display($content, $vars = [], $config = [])
    {
        if ($vars) {
            $this->data = $vars;
        }

        if ($config) {
            $this->config($config);
        }

        $cacheFile = $this->config['cache_path'] . $this->config['cache_prefix'] . md5($content) . '.' . ltrim($this->config['cache_suffix'], '.');

        if (!$this->checkCache($cacheFile)) {
            
            $this->compiler($content, $cacheFile);
        }

        
        $this->storage->read($cacheFile, $this->data);
    }

    
    public function layout($name, $replace = '')
    {
        if (false === $name) {
            
            $this->config['layout_on'] = false;
        } else {
            
            $this->config['layout_on'] = true;

            
            if (is_string($name)) {
                $this->config['layout_name'] = $name;
            }

            if (!empty($replace)) {
                $this->config['layout_item'] = $replace;
            }
        }

        return $this;
    }

    
    private function checkCache($cacheFile)
    {
        if (!$this->config['tpl_cache'] || !is_file($cacheFile) || !$handle = @fopen($cacheFile, "r")) {
            return false;
        }

        
        preg_match('/\/\*(.+?)\*\//', fgets($handle), $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $includeFile = unserialize($matches[1]);

        if (!is_array($includeFile)) {
            return false;
        }

        
        foreach ($includeFile as $path => $time) {
            if (is_file($path) && filemtime($path) > $time) {
                
                return false;
            }
        }

        
        return $this->storage->check($cacheFile, $this->config['cache_time']);
    }

    
    public function isCache($cacheId)
    {
        if ($cacheId && $this->config['display_cache']) {
            
            return $this->app['cache']->has($cacheId);
        }

        return false;
    }

    
    private function compiler(&$content, $cacheFile)
    {
        
        if ($this->config['layout_on']) {
            if (false !== strpos($content, '{__NOLAYOUT__}')) {
                
                $content = str_replace('{__NOLAYOUT__}', '', $content);
            } else {
                
                $layoutFile = $this->parseTemplateFile($this->config['layout_name']);

                if ($layoutFile) {
                    
                    $content = str_replace($this->config['layout_item'], $content, file_get_contents($layoutFile));
                }
            }
        } else {
            $content = str_replace('{__NOLAYOUT__}', '', $content);
        }

        
        $this->parse($content);

        if ($this->config['strip_space']) {
            
            $find    = ['~>\s+<~', '~>(\s+\n|\r)~'];
            $replace = ['><', '>'];
            $content = preg_replace($find, $replace, $content);
        }

        
        $content = preg_replace('/\?>\s*<\?php\s(?!echo\b|\bend)/s', '', $content);

        
        $replace = $this->config['tpl_replace_string'];
        $content = str_replace(array_keys($replace), array_values($replace), $content);

        
        $content = '<?php  ?>' . "\n" . $content;
        
        $this->storage->write($cacheFile, $content);

        $this->includeFile = [];
    }

    
    public function parse(&$content)
    {
        
        if (empty($content)) {
            return;
        }

        
        $this->parseLiteral($content);

        
        $this->parseExtend($content);

        
        $this->parseLayout($content);

        
        $this->parseInclude($content);

        
        $this->parseLiteral($content);

        
        $this->parsePhp($content);

        
        
        
        
        
        if ($this->config['taglib_load']) {
            $tagLibs = $this->getIncludeTagLib($content);

            if (!empty($tagLibs)) {
                
                foreach ($tagLibs as $tagLibName) {
                    $this->parseTagLib($tagLibName, $content);
                }
            }
        }

        
        if ($this->config['taglib_pre_load']) {
            $tagLibs = explode(',', $this->config['taglib_pre_load']);

            foreach ($tagLibs as $tag) {
                $this->parseTagLib($tag, $content);
            }
        }

        
        $tagLibs = explode(',', $this->config['taglib_build_in']);

        foreach ($tagLibs as $tag) {
            $this->parseTagLib($tag, $content, true);
        }

        
        $this->parseTag($content);

        
        $this->parseLiteral($content, true);
    }

    
    private function parsePhp(&$content)
    {
        
        $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>' . "\n", $content);

        
        if ($this->config['tpl_deny_php'] && false !== strpos($content, '<?php')) {
            throw new Exception('not allow php tag');
        }
    }

    
    private function parseLayout(&$content)
    {
        
        if (preg_match($this->getRegex('layout'), $content, $matches)) {
            
            $content = str_replace($matches[0], '', $content);
            
            $array = $this->parseAttr($matches[0]);

            if (!$this->config['layout_on'] || $this->config['layout_name'] != $array['name']) {
                
                $layoutFile = $this->parseTemplateFile($array['name']);

                if ($layoutFile) {
                    $replace = isset($array['replace']) ? $array['replace'] : $this->config['layout_item'];
                    
                    $content = str_replace($replace, $content, file_get_contents($layoutFile));
                }
            }
        } else {
            $content = str_replace('{__NOLAYOUT__}', '', $content);
        }
    }

    
    private function parseInclude(&$content)
    {
        $regex = $this->getRegex('include');
        $func  = function ($template) use (&$func, &$regex, &$content) {
            if (preg_match_all($regex, $template, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $array = $this->parseAttr($match[0]);
                    $file  = $array['file'];
                    unset($array['file']);

                    
                    $parseStr = $this->parseTemplateName($file);

                    foreach ($array as $k => $v) {
                        
                        if (0 === strpos($v, '$')) {
                            $v = $this->get(substr($v, 1));
                        }

                        $parseStr = str_replace('[' . $k . ']', $v, $parseStr);
                    }

                    $content = str_replace($match[0], $parseStr, $content);
                    
                    $func($parseStr);
                }
                unset($matches);
            }
        };

        
        $func($content);
    }

    
    private function parseExtend(&$content)
    {
        $regex  = $this->getRegex('extend');
        $array  = $blocks  = $baseBlocks  = [];
        $extend = '';

        $func = function ($template) use (&$func, &$regex, &$array, &$extend, &$blocks, &$baseBlocks) {
            if (preg_match($regex, $template, $matches)) {
                if (!isset($array[$matches['name']])) {
                    $array[$matches['name']] = 1;
                    
                    $extend = $this->parseTemplateName($matches['name']);

                    
                    $func($extend);

                    
                    $blocks = array_merge($blocks, $this->parseBlock($template));

                    return;
                }
            } else {
                
                $baseBlocks = $this->parseBlock($template, true);

                if (empty($extend)) {
                    
                    $extend = $template;
                }
            }
        };

        $func($content);

        if (!empty($extend)) {
            if ($baseBlocks) {
                $children = [];
                foreach ($baseBlocks as $name => $val) {
                    $replace = $val['content'];

                    if (!empty($children[$name])) {
                        
                        foreach ($children[$name] as $key) {
                            $replace = str_replace($baseBlocks[$key]['begin'] . $baseBlocks[$key]['content'] . $baseBlocks[$key]['end'], $blocks[$key]['content'], $replace);
                        }
                    }

                    if (isset($blocks[$name])) {
                        
                        $replace = str_replace(['{__BLOCK__}', '{__block__}'], $replace, $blocks[$name]['content']);

                        if (!empty($val['parent'])) {
                            
                            $parent = $val['parent'];

                            if (isset($blocks[$parent])) {
                                $blocks[$parent]['content'] = str_replace($blocks[$name]['begin'] . $blocks[$name]['content'] . $blocks[$name]['end'], $replace, $blocks[$parent]['content']);
                            }

                            $blocks[$name]['content'] = $replace;
                            $children[$parent][]      = $name;

                            continue;
                        }
                    } elseif (!empty($val['parent'])) {
                        
                        $children[$val['parent']][] = $name;
                        $blocks[$name]              = $val;
                    }

                    if (!$val['parent']) {
                        
                        $extend = str_replace($val['begin'] . $val['content'] . $val['end'], $replace, $extend);
                    }
                }
            }

            $content = $extend;
            unset($blocks, $baseBlocks);
        }
    }

    
    private function parseLiteral(&$content, $restore = false)
    {
        $regex = $this->getRegex($restore ? 'restoreliteral' : 'literal');

        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            if (!$restore) {
                $count = count($this->literal);

                
                foreach ($matches as $match) {
                    $this->literal[] = substr($match[0], strlen($match[1]), -strlen($match[2]));
                    $content         = str_replace($match[0], "<!--###literal{$count}###-->", $content);
                    $count++;
                }
            } else {
                
                foreach ($matches as $match) {
                    $content = str_replace($match[0], $this->literal[$match[1]], $content);
                }

                
                $this->literal = [];
            }

            unset($matches);
        }
    }

    
    private function parseBlock(&$content, $sort = false)
    {
        $regex  = $this->getRegex('block');
        $result = [];

        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            $right = $keys = [];

            foreach ($matches as $match) {
                if (empty($match['name'][0])) {
                    if (count($right) > 0) {
                        $tag    = array_pop($right);
                        $start  = $tag['offset'] + strlen($tag['tag']);
                        $length = $match[0][1] - $start;

                        $result[$tag['name']] = [
                            'begin'   => $tag['tag'],
                            'content' => substr($content, $start, $length),
                            'end'     => $match[0][0],
                            'parent'  => count($right) ? end($right)['name'] : '',
                        ];

                        $keys[$tag['name']] = $match[0][1];
                    }
                } else {
                    
                    $right[] = [
                        'name'   => $match[2][0],
                        'offset' => $match[0][1],
                        'tag'    => $match[0][0],
                    ];
                }
            }

            unset($right, $matches);

            if ($sort) {
                
                array_multisort($keys, $result);
            }
        }

        return $result;
    }

    
    private function getIncludeTagLib(&$content)
    {
        
        if (preg_match($this->getRegex('taglib'), $content, $matches)) {
            
            $content = str_replace($matches[0], '', $content);

            return explode(',', $matches['name']);
        }
    }

    
    public function parseTagLib($tagLib, &$content, $hide = false)
    {
        if (false !== strpos($tagLib, '\\')) {
            
            $className = $tagLib;
            $tagLib    = substr($tagLib, strrpos($tagLib, '\\') + 1);
        } else {
            $className = '\\think\\template\\taglib\\' . ucwords($tagLib);
        }

        $tLib = new $className($this);

        $tLib->parseTag($content, $hide ? '' : $tagLib);
    }

    
    public function parseAttr($str, $name = null)
    {
        $regex = '/\s+(?>(?P<name>[\w-]+)\s*)=(?>\s*)([\"\'])(?P<value>(?:(?!\\2).)*)\\2/is';
        $array = [];

        if (preg_match_all($regex, $str, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $array[$match['name']] = $match['value'];
            }
            unset($matches);
        }

        if (!empty($name) && isset($array[$name])) {
            return $array[$name];
        }

        return $array;
    }

    
    private function parseTag(&$content)
    {
        $regex = $this->getRegex('tag');

        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $str  = stripslashes($match[1]);
                $flag = substr($str, 0, 1);

                switch ($flag) {
                    case '$':
                        
                        
                        if (false !== $pos = strpos($str, '?')) {
                            $array = preg_split('/([!=]={1,2}|(?<!-)[><]={0,1})/', substr($str, 0, $pos), 2, PREG_SPLIT_DELIM_CAPTURE);
                            $name  = $array[0];

                            $this->parseVar($name);
                            //$this->parseVarFunction($name);

                            $str = trim(substr($str, $pos + 1));
                            $this->parseVar($str);
                            $first = substr($str, 0, 1);

                            if (strpos($name, ')')) {
                                
                                if (isset($array[1])) {
                                    $this->parseVar($array[2]);
                                    $name .= $array[1] . $array[2];
                                }

                                switch ($first) {
                                    case '?':
                                        $this->parseVarFunction($name);
                                        $str = '<?php echo (' . $name . ') ? ' . $name . ' : ' . substr($str, 1) . '; ?>';
                                        break;
                                    case '=':
                                        $str = '<?php if(' . $name . ') echo ' . substr($str, 1) . '; ?>';
                                        break;
                                    default:
                                        $str = '<?php echo ' . $name . '?' . $str . '; ?>';
                                }
                            } else {
                                if (isset($array[1])) {
                                    $express = true;
                                    $this->parseVar($array[2]);
                                    $express = $name . $array[1] . $array[2];
                                } else {
                                    $express = false;
                                }

                                if (in_array($first, ['?', '=', ':'])) {
                                    $str = trim(substr($str, 1));
                                    if ('$' == substr($str, 0, 1)) {
                                        $str = $this->parseVarFunction($str);
                                    }
                                }

                                
                                switch ($first) {
                                    case '?':
                                        
                                        $str = '<?php echo ' . ($express ?: 'isset(' . $name . ')') . ' ? ' . $this->parseVarFunction($name) . ' : ' . $str . '; ?>';
                                        break;
                                    case '=':
                                        
                                        $str = '<?php if(' . ($express ?: '!empty(' . $name . ')') . ') echo ' . $str . '; ?>';
                                        break;
                                    case ':':
                                        
                                        $str = '<?php echo ' . ($express ?: '!empty(' . $name . ')') . ' ? ' . $this->parseVarFunction($name) . ' : ' . $str . '; ?>';
                                        break;
                                    default:
                                        if (strpos($str, ':')) {
                                            
                                            $array = explode(':', $str, 2);

                                            $array[0] = '$' == substr(trim($array[0]), 0, 1) ? $this->parseVarFunction($array[0]) : $array[0];
                                            $array[1] = '$' == substr(trim($array[1]), 0, 1) ? $this->parseVarFunction($array[1]) : $array[1];

                                            $str = implode(' : ', $array);
                                        }
                                        $str = '<?php echo ' . ($express ?: '!empty(' . $name . ')') . ' ? ' . $str . '; ?>';
                                }
                            }
                        } else {
                            $this->parseVar($str);
                            $this->parseVarFunction($str);
                            $str = '<?php echo ' . $str . '; ?>';
                        }
                        break;
                    case ':':
                        
                        $str = substr($str, 1);
                        $this->parseVar($str);
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case '~':
                        
                        $str = substr($str, 1);
                        $this->parseVar($str);
                        $str = '<?php ' . $str . '; ?>';
                        break;
                    case '-':
                    case '+':
                        
                        $this->parseVar($str);
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case '/':
                        
                        $flag2 = substr($str, 1, 1);
                        if ('/' == $flag2 || ('*' == $flag2 && substr(rtrim($str), -2) == '*/')) {
                            $str = '';
                        }
                        break;
                    default:
                        
                        $str = $this->config['tpl_begin'] . $str . $this->config['tpl_end'];
                        break;
                }

                $content = str_replace($match[0], $str, $content);
            }

            unset($matches);
        }
    }

    
    public function parseVar(&$varStr)
    {
        $varStr = trim($varStr);

        if (preg_match_all('/\$[a-zA-Z_](?>\w*)(?:[:\.][0-9a-zA-Z_](?>\w*))+/', $varStr, $matches, PREG_OFFSET_CAPTURE)) {
            static $_varParseList = [];

            while ($matches[0]) {
                $match = array_pop($matches[0]);

                //如果已经解析过该变量字串，则直接返回变量值
                if (isset($_varParseList[$match[0]])) {
                    $parseStr = $_varParseList[$match[0]];
                } else {
                    if (strpos($match[0], '.')) {
                        $vars  = explode('.', $match[0]);
                        $first = array_shift($vars);

                        if ('$Think' == $first) {
                            
                            $parseStr = $this->parseThinkVar($vars);
                        } elseif ('$Request' == $first) {
                            
                            $method = array_shift($vars);
                            if (!empty($vars)) {
                                $params = implode('.', $vars);
                                if ('true' != $params) {
                                    $params = '\'' . $params . '\'';
                                }
                            } else {
                                $params = '';
                            }

                            $parseStr = 'app(\'request\')->' . $method . '(' . $params . ')';
                        } else {
                            switch ($this->config['tpl_var_identify']) {
                                case 'array': 
                                    $parseStr = $first . '[\'' . implode('\'][\'', $vars) . '\']';
                                    break;
                                case 'obj': 
                                    $parseStr = $first . '->' . implode('->', $vars);
                                    break;
                                default: 
                                    $parseStr = '(is_array(' . $first . ')?' . $first . '[\'' . implode('\'][\'', $vars) . '\']:' . $first . '->' . implode('->', $vars) . ')';
                            }
                        }
                    } else {
                        $parseStr = str_replace(':', '->', $match[0]);
                    }

                    $_varParseList[$match[0]] = $parseStr;
                }

                $varStr = substr_replace($varStr, $parseStr, $match[1], strlen($match[0]));
            }
            unset($matches);
        }
    }

    
    public function parseVarFunction(&$varStr, $autoescape = true)
    {
        if (!$autoescape && false === strpos($varStr, '|')) {
            return $varStr;
        } elseif ($autoescape && !preg_match('/\|(\s)?raw(\||\s)?/i', $varStr)) {
            $varStr .= '|' . $this->config['default_filter'];
        }

        static $_varFunctionList = [];

        $_key = md5($varStr);

        //如果已经解析过该变量字串，则直接返回变量值
        if (isset($_varFunctionList[$_key])) {
            $varStr = $_varFunctionList[$_key];
        } else {
            $varArray = explode('|', $varStr);

            
            $name = trim(array_shift($varArray));

            
            $length = count($varArray);

            
            $template_deny_funs = explode(',', $this->config['tpl_deny_func_list']);

            for ($i = 0; $i < $length; $i++) {
                $args = explode('=', $varArray[$i], 2);

                
                $fun = trim($args[0]);
                if (in_array($fun, $template_deny_funs)) {
                    continue;
                }

                switch (strtolower($fun)) {
                    case 'raw':
                        break;
                    case 'date':
                        $name = 'date(' . $args[1] . ',!is_numeric(' . $name . ')? strtotime(' . $name . ') : ' . $name . ')';
                        break;
                    case 'first':
                        $name = 'current(' . $name . ')';
                        break;
                    case 'last':
                        $name = 'end(' . $name . ')';
                        break;
                    case 'upper':
                        $name = 'strtoupper(' . $name . ')';
                        break;
                    case 'lower':
                        $name = 'strtolower(' . $name . ')';
                        break;
                    case 'format':
                        $name = 'sprintf(' . $args[1] . ',' . $name . ')';
                        break;
                    case 'default': 
                        if (false === strpos($name, '(')) {
                            $name = '(isset(' . $name . ') && (' . $name . ' !== \'\')?' . $name . ':' . $args[1] . ')';
                        } else {
                            $name = '(' . $name . ' ?: ' . $args[1] . ')';
                        }
                        break;
                    default: 
                        if (isset($args[1])) {
                            if (strstr($args[1], '###')) {
                                $args[1] = str_replace('###', $name, $args[1]);
                                $name    = "$fun($args[1])";
                            } else {
                                $name = "$fun($name,$args[1])";
                            }
                        } else {
                            if (!empty($args[0])) {
                                $name = "$fun($name)";
                            }
                        }
                }
            }

            $_varFunctionList[$_key] = $name;
            $varStr                  = $name;
        }
        return $varStr;
    }

    
    public function parseThinkVar($vars)
    {
        $type  = strtoupper(trim(array_shift($vars)));
        $param = implode('.', $vars);

        if ($vars) {
            switch ($type) {
                case 'SERVER':
                    $parseStr = 'app(\'request\')->server(\'' . $param . '\')';
                    break;
                case 'GET':
                    $parseStr = 'app(\'request\')->get(\'' . $param . '\')';
                    break;
                case 'POST':
                    $parseStr = 'app(\'request\')->post(\'' . $param . '\')';
                    break;
                case 'COOKIE':
                    $parseStr = 'app(\'cookie\')->get(\'' . $param . '\')';
                    break;
                case 'SESSION':
                    $parseStr = 'app(\'session\')->get(\'' . $param . '\')';
                    break;
                case 'ENV':
                    $parseStr = 'app(\'request\')->env(\'' . $param . '\')';
                    break;
                case 'REQUEST':
                    $parseStr = 'app(\'request\')->request(\'' . $param . '\')';
                    break;
                case 'CONST':
                    $parseStr = strtoupper($param);
                    break;
                case 'LANG':
                    $parseStr = 'app(\'lang\')->get(\'' . $param . '\')';
                    break;
                case 'CONFIG':
                    $parseStr = 'app(\'config\')->get(\'' . $param . '\')';
                    break;
                default:
                    $parseStr = '\'\'';
                    break;
            }
        } else {
            switch ($type) {
                case 'NOW':
                    $parseStr = "date('Y-m-d g:i a',time())";
                    break;
                case 'VERSION':
                    $parseStr = 'app()->version()';
                    break;
                case 'LDELIM':
                    $parseStr = '\'' . ltrim($this->config['tpl_begin'], '\\') . '\'';
                    break;
                case 'RDELIM':
                    $parseStr = '\'' . ltrim($this->config['tpl_end'], '\\') . '\'';
                    break;
                default:
                    if (defined($type)) {
                        $parseStr = $type;
                    } else {
                        $parseStr = '';
                    }
            }
        }

        return $parseStr;
    }

    
    private function parseTemplateName($templateName)
    {
        $array    = explode(',', $templateName);
        $parseStr = '';

        foreach ($array as $templateName) {
            if (empty($templateName)) {
                continue;
            }

            if (0 === strpos($templateName, '$')) {
                //支持加载变量文件名
                $templateName = $this->get(substr($templateName, 1));
            }

            $template = $this->parseTemplateFile($templateName);

            if ($template) {
                
                $parseStr .= file_get_contents($template);
            }
        }

        return $parseStr;
    }

    
    private function parseTemplateFile($template)
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            if (strpos($template, '@')) {
                list($module, $template) = explode('@', $template);
            }

            if (0 !== strpos($template, '/')) {
                $template = str_replace(['/', ':'], $this->config['view_depr'], $template);
            } else {
                $template = str_replace(['/', ':'], $this->config['view_depr'], substr($template, 1));
            }

            if ($this->config['view_base']) {
                $module = isset($module) ? $module : $this->app['request']->module();
                $path   = $this->config['view_base'] . ($module ? $module . DIRECTORY_SEPARATOR : '');
            } else {
                $path = isset($module) ? $this->app->getAppPath() . $module . DIRECTORY_SEPARATOR . basename($this->config['view_path']) . DIRECTORY_SEPARATOR : $this->config['view_path'];
            }

            $template = $path . $template . '.' . ltrim($this->config['view_suffix'], '.');
        }

        if (is_file($template)) {
            
            $this->includeFile[$template] = filemtime($template);

            return $template;
        }

        throw new TemplateNotFoundException('template not exists:' . $template, $template);
    }

    
    private function getRegex($tagName)
    {
        $regex = '';
        if ('tag' == $tagName) {
            $begin = $this->config['tpl_begin'];
            $end   = $this->config['tpl_end'];

            if (strlen(ltrim($begin, '\\')) == 1 && strlen(ltrim($end, '\\')) == 1) {
                $regex = $begin . '((?:[\$]{1,2}[a-wA-w_]|[\:\~][\$a-wA-w_]|[+]{2}[\$][a-wA-w_]|[-]{2}[\$][a-wA-w_]|\/[\*\/])(?>[^' . $end . ']*))' . $end;
            } else {
                $regex = $begin . '((?:[\$]{1,2}[a-wA-w_]|[\:\~][\$a-wA-w_]|[+]{2}[\$][a-wA-w_]|[-]{2}[\$][a-wA-w_]|\/[\*\/])(?>(?:(?!' . $end . ').)*))' . $end;
            }
        } else {
            $begin  = $this->config['taglib_begin'];
            $end    = $this->config['taglib_end'];
            $single = strlen(ltrim($begin, '\\')) == 1 && strlen(ltrim($end, '\\')) == 1 ? true : false;

            switch ($tagName) {
                case 'block':
                    if ($single) {
                        $regex = $begin . '(?:' . $tagName . '\b\s+(?>(?:(?!name=).)*)\bname=([\'\"])(?P<name>[\$\w\-\/\.]+)\\1(?>[^' . $end . ']*)|\/' . $tagName . ')' . $end;
                    } else {
                        $regex = $begin . '(?:' . $tagName . '\b\s+(?>(?:(?!name=).)*)\bname=([\'\"])(?P<name>[\$\w\-\/\.]+)\\1(?>(?:(?!' . $end . ').)*)|\/' . $tagName . ')' . $end;
                    }
                    break;
                case 'literal':
                    if ($single) {
                        $regex = '(' . $begin . $tagName . '\b(?>[^' . $end . ']*)' . $end . ')';
                        $regex .= '(?:(?>[^' . $begin . ']*)(?>(?!' . $begin . '(?>' . $tagName . '\b[^' . $end . ']*|\/' . $tagName . ')' . $end . ')' . $begin . '[^' . $begin . ']*)*)';
                        $regex .= '(' . $begin . '\/' . $tagName . $end . ')';
                    } else {
                        $regex = '(' . $begin . $tagName . '\b(?>(?:(?!' . $end . ').)*)' . $end . ')';
                        $regex .= '(?:(?>(?:(?!' . $begin . ').)*)(?>(?!' . $begin . '(?>' . $tagName . '\b(?>(?:(?!' . $end . ').)*)|\/' . $tagName . ')' . $end . ')' . $begin . '(?>(?:(?!' . $begin . ').)*))*)';
                        $regex .= '(' . $begin . '\/' . $tagName . $end . ')';
                    }
                    break;
                case 'restoreliteral':
                    $regex = '<!--###literal(\d+)###-->';
                    break;
                case 'include':
                    $name = 'file';
                case 'taglib':
                case 'layout':
                case 'extend':
                    if (empty($name)) {
                        $name = 'name';
                    }
                    if ($single) {
                        $regex = $begin . $tagName . '\b\s+(?>(?:(?!' . $name . '=).)*)\b' . $name . '=([\'\"])(?P<name>[\$\w\-\/\.\:@,\\\\]+)\\1(?>[^' . $end . ']*)' . $end;
                    } else {
                        $regex = $begin . $tagName . '\b\s+(?>(?:(?!' . $name . '=).)*)\b' . $name . '=([\'\"])(?P<name>[\$\w\-\/\.\:@,\\\\]+)\\1(?>(?:(?!' . $end . ').)*)' . $end;
                    }
                    break;
            }
        }

        return '/' . $regex . '/is';
    }

    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['app'], $data['storage']);

        return $data;
    }
}
