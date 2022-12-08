<?php
namespace think;
use Yaconf;
class Config implements \ArrayAccess
{
    
    protected $config = [];

    
    protected $prefix = 'app';

    
    protected $path;

    
    protected $ext;

    
    protected $yaconf;

    
    public function __construct($path = '', $ext = '.php')
    {
        $this->path = $path;
        $this->ext = $ext;
        $this->yaconf = class_exists('Yaconf');
    }

    public static function __make(App $app)
    {
        $path = $app->getConfigPath();
        $ext = $app->getConfigExt();
        return new static ($path, $ext);
    }

    
    public function setYaconf($yaconf)
    {
        if ($this->yaconf) {
            $this->yaconf = $yaconf;
        }
    }

    
    public function setDefaultPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    
    public function parse($config, $type = '', $name = '')
    {
        if (empty($type)) {
            $type = pathinfo($config, PATHINFO_EXTENSION);
        }

        $object = Loader::factory($type, '\\think\\config\\driver\\', $config);

        return $this->set($object->parse(), $name);
    }

    
    public function load($file, $name = '')
    {
        if (is_file($file)) {
            $filename = $file;
        } elseif (is_file($this->path . $file . $this->ext)) {
            $filename = $this->path . $file . $this->ext;
        }

        if (isset($filename)) {
            return $this->loadFile($filename, $name);
        } elseif ($this->yaconf && Yaconf::has($file)) {
            return $this->set(Yaconf::get($file), $name);
        }

        return $this->config;
    }

    
    protected function getYaconfName($name)
    {
        if ($this->yaconf && is_string($this->yaconf)) {
            return $this->yaconf . '.' . $name;
        }

        return $name;
    }

    
    public function yaconf($name, $default = null)
    {
        if ($this->yaconf) {
            $yaconfName = $this->getYaconfName($name);

            if (Yaconf::has($yaconfName)) {
                return Yaconf::get($yaconfName);
            }
        }

        return $default;
    }

    protected function loadFile($file, $name)
    {
        $name = strtolower($name);
        $type = pathinfo($file, PATHINFO_EXTENSION);

        if ('php' == $type) {
            return $this->set(include $file, $name);
        } elseif ('yaml' == $type && function_exists('yaml_parse_file')) {
            return $this->set(yaml_parse_file($file), $name);
        }

        return $this->parse($file, $type, $name);
    }

    
    public function has($name)
    {
        if (false === strpos($name, '.')) {
            $name = $this->prefix . '.' . $name;
        }

        return !is_null($this->get($name));
    }

    
    public function pull($name)
    {
        $name = strtolower($name);

        if ($this->yaconf) {
            $yaconfName = $this->getYaconfName($name);

            if (Yaconf::has($yaconfName)) {
                $config = Yaconf::get($yaconfName);
                return isset($this->config[$name]) ? array_merge($this->config[$name], $config) : $config;
            }
        }

        return isset($this->config[$name]) ? $this->config[$name] : [];
    }

    
    public function get($name = null, $default = null)
    {
        if ($name && false === strpos($name, '.')) {
            $name = $this->prefix . '.' . $name;
        }


        if (empty($name)) {
            return $this->config;
        }

        if ('.' == substr($name, -1)) {
            return $this->pull(substr($name, 0, -1));
        }

        if ($this->yaconf) {
            $yaconfName = $this->getYaconfName($name);

            if (Yaconf::has($yaconfName)) {
                return Yaconf::get($yaconfName);
            }
        }

        $name = explode('.', $name);
        $name[0] = strtolower($name[0]);
        $config = $this->config;


        foreach ($name as $val) {
            if (isset($config[$val])) {
                $config = $config[$val];
            } else {
                return $default;
            }
        }

        return $config;
    }

    
    public function set($name, $value = null)
    {
        if (is_string($name)) {
            if (false === strpos($name, '.')) {
                $name = $this->prefix . '.' . $name;
            }

            $name = explode('.', $name, 3);

            if (count($name) == 2) {
                $this->config[strtolower($name[0])][$name[1]] = $value;
            } else {
                $this->config[strtolower($name[0])][$name[1]][$name[2]] = $value;
            }

            return $value;
        } elseif (is_array($name)) {

            if (!empty($value)) {
                if (isset($this->config[$value])) {
                    $result = array_merge($this->config[$value], $name);
                } else {
                    $result = $name;
                }

                $this->config[$value] = $result;
            } else {
                $result = $this->config = array_merge($this->config, $name);
            }
        } else {

            $result = $this->config;
        }

        return $result;
    }

    
    public function remove($name)
    {
        if (false === strpos($name, '.')) {
            $name = $this->prefix . '.' . $name;
        }

        $name = explode('.', $name, 3);

        if (count($name) == 2) {
            unset($this->config[strtolower($name[0])][$name[1]]);
        } else {
            unset($this->config[strtolower($name[0])][$name[1]][$name[2]]);
        }
    }

    
    public function reset($prefix = '')
    {
        if ('' === $prefix) {
            $this->config = [];
        } else {
            $this->config[$prefix] = [];
        }
    }

    
    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    
    public function __get($name)
    {
        return $this->get($name);
    }

    
    public function __isset($name)
    {
        return $this->has($name);
    }


    public function offsetSet($name, $value)
    {
        $this->set($name, $value);
    }

    public function offsetExists($name)
    {
        return $this->has($name);
    }

    public function offsetUnset($name)
    {
        $this->remove($name);
    }

    public function offsetGet($name)
    {
        return $this->get($name);
    }
}