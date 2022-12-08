<?php










namespace think;

class View
{
    
    public $engine;

    
    protected $data = [];

    
    protected $filter;

    
    protected static $var = [];

    
    public function init($engine = [])
    {
        
        $this->engine($engine);

        return $this;
    }

    public static function __make(Config $config)
    {
        return (new static())->init($config->pull('template'));
    }

    
    public function share($name, $value = '')
    {
        if (is_array($name)) {
            self::$var = array_merge(self::$var, $name);
        } else {
            self::$var[$name] = $value;
        }

        return $this;
    }

    
    public function clear()
    {
        self::$var  = [];
        $this->data = [];
    }

    
    public function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }

        return $this;
    }

    
    public function engine($options = [])
    {
        if (is_string($options)) {
            $type    = $options;
            $options = [];
        } else {
            $type = !empty($options['type']) ? $options['type'] : 'Think';
        }

        if (isset($options['type'])) {
            unset($options['type']);
        }

        $this->engine = Loader::factory($type, '\\think\\view\\driver\\', $options);

        return $this;
    }

    
    public function config($name, $value = null)
    {
        $this->engine->config($name, $value);

        return $this;
    }

    
    public function exists($name)
    {
        return $this->engine->exists($name);
    }

    
    public function filter($filter)
    {
        if ($filter) {
            $this->filter = $filter;
        }

        return $this;
    }

    
    public function fetch($template = '', $vars = [], $config = [], $renderContent = false)
    {
        
        $vars = array_merge(self::$var, $this->data, $vars);

        
        ob_start();
        ob_implicit_flush(0);

        
        try {
            $method = $renderContent ? 'display' : 'fetch';
            $this->engine->$method($template, $vars, $config);
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }

        
        $content = ob_get_clean();

        if ($this->filter) {
            $content = call_user_func_array($this->filter, [$content]);
        }

        return $content;
    }

    
    public function display($content, $vars = [], $config = [])
    {
        return $this->fetch($content, $vars, $config, true);
    }

    
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    
    public function __get($name)
    {
        return $this->data[$name];
    }

    
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }
}
