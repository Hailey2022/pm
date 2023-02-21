<?php
namespace think\cache;
use think\Container;
abstract class Driver
{
    protected $handler = null;
    protected $readTimes = 0;
    protected $writeTimes = 0;
    protected $options = [];
    protected $tag;
    protected static $serialize = ['serialize', 'unserialize', 'think_serialize:', 16];
    abstract public function has($name);
    abstract public function get($name, $default = false);
    abstract public function set($name, $value, $expire = null);
    abstract public function inc($name, $step = 1);
    abstract public function dec($name, $step = 1);
    abstract public function rm($name);
    abstract public function clear($tag = null);
    protected function getExpireTime($expire)
    {
        if ($expire instanceof \DateTime) {
            $expire = $expire->getTimestamp() - time();
        }
        return $expire;
    }
    protected function getCacheKey($name)
    {
        return $this->options['prefix'] . $name;
    }
    public function pull($name)
    {
        $result = $this->get($name, false);
        if ($result) {
            $this->rm($name);
            return $result;
        } else {
            return;
        }
    }
    public function remember($name, $value, $expire = null)
    {
        if (!$this->has($name)) {
            $time = time();
            while ($time + 5 > time() && $this->has($name . '_lock')) {
                usleep(200000);
            }
            try {
                $this->set($name . '_lock', true);
                if ($value instanceof \Closure) {
                    $value = Container::getInstance()->invokeFunction($value);
                }
                $this->set($name, $value, $expire);
                $this->rm($name . '_lock');
            } catch (\Exception $e) {
                $this->rm($name . '_lock');
                throw $e;
            } catch (\throwable $e) {
                $this->rm($name . '_lock');
                throw $e;
            }
        } else {
            $value = $this->get($name);
        }
        return $value;
    }
    public function tag($name, $keys = null, $overlay = false)
    {
        if (is_null($name)) {
        } elseif (is_null($keys)) {
            $this->tag = $name;
        } else {
            $key = $this->getTagkey($name);
            if (is_string($keys)) {
                $keys = explode(',', $keys);
            }
            $keys = array_map([$this, 'getCacheKey'], $keys);
            if ($overlay) {
                $value = $keys;
            } else {
                $value = array_unique(array_merge($this->getTagItem($name), $keys));
            }
            $this->set($key, implode(',', $value), 0);
        }
        return $this;
    }
    protected function setTagItem($name)
    {
        if ($this->tag) {
            $key       = $this->getTagkey($this->tag);
            $this->tag = null;
            if ($this->has($key)) {
                $value   = explode(',', $this->get($key));
                $value[] = $name;
                if (count($value) > 1000) {
                    array_shift($value);
                }
                $value = implode(',', array_unique($value));
            } else {
                $value = $name;
            }
            $this->set($key, $value, 0);
        }
    }
    protected function getTagItem($tag)
    {
        $key   = $this->getTagkey($tag);
        $value = $this->get($key);
        if ($value) {
            return array_filter(explode(',', $value));
        } else {
            return [];
        }
    }
    protected function getTagKey($tag)
    {
        return 'tag_' . md5($tag);
    }
    protected function serialize($data)
    {
        if (is_scalar($data) || !$this->options['serialize']) {
            return $data;
        }
        $serialize = self::$serialize[0];
        return self::$serialize[2] . $serialize($data);
    }
    protected function unserialize($data)
    {
        if ($this->options['serialize'] && 0 === strpos($data, self::$serialize[2])) {
            $unserialize = self::$serialize[1];
            return $unserialize(substr($data, self::$serialize[3]));
        } else {
            return $data;
        }
    }
    public static function registerSerialize($serialize, $unserialize, $prefix = 'think_serialize:')
    {
        self::$serialize = [$serialize, $unserialize, $prefix, strlen($prefix)];
    }
    public function handler()
    {
        return $this->handler;
    }
    public function getReadTimes()
    {
        return $this->readTimes;
    }
    public function getWriteTimes()
    {
        return $this->writeTimes;
    }
    public function __call($method, $args)
    {
        return call_user_func_array([$this->handler, $method], $args);
    }
}
