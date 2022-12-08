<?php










namespace think;

class Hook
{
    
    private $tags = [];

    
    protected $bind = [];

    
    private static $portal = 'run';

    
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    
    public function portal($name)
    {
        self::$portal = $name;
        return $this;
    }

    
    public function alias($name, $behavior = null)
    {
        if (is_array($name)) {
            $this->bind = array_merge($this->bind, $name);
        } else {
            $this->bind[$name] = $behavior;
        }

        return $this;
    }

    
    public function add($tag, $behavior, $first = false)
    {
        isset($this->tags[$tag]) || $this->tags[$tag] = [];

        if (is_array($behavior) && !is_callable($behavior)) {
            if (!array_key_exists('_overlay', $behavior)) {
                $this->tags[$tag] = array_merge($this->tags[$tag], $behavior);
            } else {
                unset($behavior['_overlay']);
                $this->tags[$tag] = $behavior;
            }
        } elseif ($first) {
            array_unshift($this->tags[$tag], $behavior);
        } else {
            $this->tags[$tag][] = $behavior;
        }
    }

    
    public function import(array $tags, $recursive = true)
    {
        if ($recursive) {
            foreach ($tags as $tag => $behavior) {
                $this->add($tag, $behavior);
            }
        } else {
            $this->tags = $tags + $this->tags;
        }
    }

    
    public function get($tag = '')
    {
        if (empty($tag)) {
            //获取全部的插件信息
            return $this->tags;
        }

        return array_key_exists($tag, $this->tags) ? $this->tags[$tag] : [];
    }

    
    public function listen($tag, $params = null, $once = false)
    {
        $results = [];
        $tags    = $this->get($tag);

        foreach ($tags as $key => $name) {
            $results[$key] = $this->execTag($name, $tag, $params);

            if (false === $results[$key] || (!is_null($results[$key]) && $once)) {
                break;
            }
        }

        return $once ? end($results) : $results;
    }

    
    public function exec($class, $params = null)
    {
        if ($class instanceof \Closure || is_array($class)) {
            $method = $class;
        } else {
            if (isset($this->bind[$class])) {
                $class = $this->bind[$class];
            }
            $method = [$class, self::$portal];
        }

        return $this->app->invoke($method, [$params]);
    }

    
    protected function execTag($class, $tag = '', $params = null)
    {
        $method = Loader::parseName($tag, 1, false);

        if ($class instanceof \Closure) {
            $call  = $class;
            $class = 'Closure';
        } elseif (is_array($class) || strpos($class, '::')) {
            $call = $class;
        } else {
            $obj = Container::get($class);

            if (!is_callable([$obj, $method])) {
                $method = self::$portal;
            }

            $call  = [$class, $method];
            $class = $class . '->' . $method;
        }

        $result = $this->app->invoke($call, [$params]);

        return $result;
    }

    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['app']);

        return $data;
    }
}
