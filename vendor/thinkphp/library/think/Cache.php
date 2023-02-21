<?php
namespace think;
use think\cache\Driver;
class Cache
{
    protected $instance = [];
    protected $config = [];
    protected $handler;
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->init($config);
    }
    public function connect(array $options = [], $name = false)
    {
        if (false === $name) {
            $name = md5(serialize($options));
        }
        if (true === $name || !isset($this->instance[$name])) {
            $type = !empty($options['type']) ? $options['type'] : 'File';
            if (true === $name) {
                $name = md5(serialize($options));
            }
            $this->instance[$name] = Loader::factory($type, '\\think\\cache\\driver\\', $options);
        }
        return $this->instance[$name];
    }
    public function init(array $options = [], $force = false)
    {
        if (is_null($this->handler) || $force) {
            if ('complex' == $options['type']) {
                $default = $options['default'];
                $options = isset($options[$default['type']]) ? $options[$default['type']] : $default;
            }
            $this->handler = $this->connect($options);
        }
        return $this->handler;
    }
    public static function __make(Config $config)
    {
        return new static($config->pull('cache'));
    }
    public function getConfig()
    {
        return $this->config;
    }
    public function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }
    public function store($name = '')
    {
        if ('' !== $name && 'complex' == $this->config['type']) {
            return $this->connect($this->config[$name], strtolower($name));
        }
        return $this->init();
    }
    public function __call($method, $args)
    {
        return call_user_func_array([$this->init(), $method], $args);
    }
}
