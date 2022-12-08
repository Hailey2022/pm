<?php










namespace think;

class Lang
{
    
    private $lang = [];

    
    private $range = 'zh-cn';

    
    protected $langDetectVar = 'lang';

    
    protected $langCookieVar = 'think_var';

    
    protected $allowLangList = [];

    
    protected $acceptLanguage = [
        'zh-hans-cn' => 'zh-cn',
    ];

    
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    
    public function range($range = '')
    {
        if ('' == $range) {
            return $this->range;
        } else {
            $this->range = $range;
        }
    }

    
    public function set($name, $value = null, $range = '')
    {
        $range = $range ?: $this->range;
        
        if (!isset($this->lang[$range])) {
            $this->lang[$range] = [];
        }

        if (is_array($name)) {
            return $this->lang[$range] = array_change_key_case($name) + $this->lang[$range];
        }

        return $this->lang[$range][strtolower($name)] = $value;
    }

    
    public function load($file, $range = '')
    {
        $range = $range ?: $this->range;
        if (!isset($this->lang[$range])) {
            $this->lang[$range] = [];
        }

        
        if (is_string($file)) {
            $file = [$file];
        }

        $lang = [];

        foreach ($file as $_file) {
            if (is_file($_file)) {
                
                $this->app->log('[ LANG ] ' . $_file);
                $_lang = include $_file;
                if (is_array($_lang)) {
                    $lang = array_change_key_case($_lang) + $lang;
                }
            }
        }

        if (!empty($lang)) {
            $this->lang[$range] = $lang + $this->lang[$range];
        }

        return $this->lang[$range];
    }

    
    public function has($name, $range = '')
    {
        $range = $range ?: $this->range;

        return isset($this->lang[$range][strtolower($name)]);
    }

    
    public function get($name = null, $vars = [], $range = '')
    {
        $range = $range ?: $this->range;

        
        if (is_null($name)) {
            return $this->lang[$range];
        }

        $key   = strtolower($name);
        $value = isset($this->lang[$range][$key]) ? $this->lang[$range][$key] : $name;

        
        if (!empty($vars) && is_array($vars)) {
            
            if (key($vars) === 0) {
                
                array_unshift($vars, $value);
                $value = call_user_func_array('sprintf', $vars);
            } else {
                
                $replace = array_keys($vars);
                foreach ($replace as &$v) {
                    $v = "{:{$v}}";
                }
                $value = str_replace($replace, $vars, $value);
            }
        }

        return $value;
    }

    
    public function detect()
    {
        
        $langSet = '';

        if (isset($_GET[$this->langDetectVar])) {
            
            $langSet = strtolower($_GET[$this->langDetectVar]);
        } elseif (isset($_COOKIE[$this->langCookieVar])) {
            
            $langSet = strtolower($_COOKIE[$this->langCookieVar]);
        } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            
            preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
            $langSet = strtolower($matches[1]);
            if (isset($this->acceptLanguage[$langSet])) {
                $langSet = $this->acceptLanguage[$langSet];
            }
        }

        if (empty($this->allowLangList) || in_array($langSet, $this->allowLangList)) {
            
            $this->range = $langSet ?: $this->range;
        }

        return $this->range;
    }

    
    public function saveToCookie($lang = null)
    {
        $range = $lang ?: $this->range;

        $_COOKIE[$this->langCookieVar] = $range;
    }

    
    public function setLangDetectVar($var)
    {
        $this->langDetectVar = $var;
    }

    
    public function setLangCookieVar($var)
    {
        $this->langCookieVar = $var;
    }

    
    public function setAllowLangList(array $list)
    {
        $this->allowLangList = $list;
    }

    
    public function setAcceptLanguage(array $list)
    {
        $this->acceptLanguage = array_merge($this->acceptLanguage, $list);
    }
}
