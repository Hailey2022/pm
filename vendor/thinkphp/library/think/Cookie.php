<?php










namespace think;

class Cookie
{
    
    protected $config = [
        
        'prefix'    => '',
        
        'expire'    => 0,
        
        'path'      => '/',
        
        'domain'    => '',
        
        'secure'    => false,
        
        'httponly'  => false,
        
        'setcookie' => true,
    ];

    
    public function __construct(array $config = [])
    {
        $this->init($config);
    }

    
    public function init(array $config = [])
    {
        $this->config = array_merge($this->config, array_change_key_case($config));

        if (!empty($this->config['httponly']) && PHP_SESSION_ACTIVE != session_status()) {
            ini_set('session.cookie_httponly', 1);
        }
    }

    public static function __make(Config $config)
    {
        return new static($config->pull('cookie'));
    }

    
    public function prefix($prefix = '')
    {
        if (empty($prefix)) {
            return $this->config['prefix'];
        }

        $this->config['prefix'] = $prefix;
    }

    
    public function set($name, $value = '', $option = null)
    {
        
        if (!is_null($option)) {
            if (is_numeric($option)) {
                $option = ['expire' => $option];
            } elseif (is_string($option)) {
                parse_str($option, $option);
            }

            $config = array_merge($this->config, array_change_key_case($option));
        } else {
            $config = $this->config;
        }

        $name = $config['prefix'] . $name;

        
        if (is_array($value)) {
            array_walk_recursive($value, [$this, 'jsonFormatProtect'], 'encode');
            $value = 'think:' . json_encode($value);
        }

        $expire = !empty($config['expire']) ? $_SERVER['REQUEST_TIME'] + intval($config['expire']) : 0;

        if ($config['setcookie']) {
            $this->setCookie($name, $value, $expire, $config);
        }

        $_COOKIE[$name] = $value;
    }

    
    protected function setCookie($name, $value, $expire, $option = [])
    {
        setcookie($name, $value, $expire, $option['path'], $option['domain'], $option['secure'], $option['httponly']);
    }

    
    public function forever($name, $value = '', $option = null)
    {
        if (is_null($option) || is_numeric($option)) {
            $option = [];
        }

        $option['expire'] = 315360000;

        $this->set($name, $value, $option);
    }

    
    public function has($name, $prefix = null)
    {
        $prefix = !is_null($prefix) ? $prefix : $this->config['prefix'];
        $name   = $prefix . $name;

        return isset($_COOKIE[$name]);
    }

    
    public function get($name = '', $prefix = null)
    {
        $prefix = !is_null($prefix) ? $prefix : $this->config['prefix'];
        $key    = $prefix . $name;

        if ('' == $name) {
            if ($prefix) {
                $value = [];
                foreach ($_COOKIE as $k => $val) {
                    if (0 === strpos($k, $prefix)) {
                        $value[$k] = $val;
                    }
                }
            } else {
                $value = $_COOKIE;
            }
        } elseif (isset($_COOKIE[$key])) {
            $value = $_COOKIE[$key];

            if (0 === strpos($value, 'think:')) {
                $value = substr($value, 6);
                $value = json_decode($value, true);
                array_walk_recursive($value, [$this, 'jsonFormatProtect'], 'decode');
            }
        } else {
            $value = null;
        }

        return $value;
    }

    
    public function delete($name, $prefix = null)
    {
        $config = $this->config;
        $prefix = !is_null($prefix) ? $prefix : $config['prefix'];
        $name   = $prefix . $name;

        if ($config['setcookie']) {
            $this->setcookie($name, '', $_SERVER['REQUEST_TIME'] - 3600, $config);
        }

        
        unset($_COOKIE[$name]);
    }

    
    public function clear($prefix = null)
    {
        
        if (empty($_COOKIE)) {
            return;
        }

        
        $config = $this->config;
        $prefix = !is_null($prefix) ? $prefix : $config['prefix'];

        if ($prefix) {
            
            foreach ($_COOKIE as $key => $val) {
                if (0 === strpos($key, $prefix)) {
                    if ($config['setcookie']) {
                        $this->setcookie($key, '', $_SERVER['REQUEST_TIME'] - 3600, $config);
                    }
                    unset($_COOKIE[$key]);
                }
            }
        }

        return;
    }

    private function jsonFormatProtect(&$val, $key, $type = 'encode')
    {
        if (!empty($val) && true !== $val) {
            $val = 'decode' == $type ? urldecode($val) : urlencode($val);
        }
    }

}
