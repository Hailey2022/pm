<?php
namespace think\cache\driver;
use think\cache\Driver;
class Redis extends Driver
{
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => '',
        'serialize'  => true,
    ];
    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if (extension_loaded('redis')) {
            $this->handler = new \Redis;
            if ($this->options['persistent']) {
                $this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
            } else {
                $this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
            }
            if ('' != $this->options['password']) {
                $this->handler->auth($this->options['password']);
            }
            if (0 != $this->options['select']) {
                $this->handler->select($this->options['select']);
            }
        } elseif (class_exists('\Predis\Client')) {
            $params = [];
            foreach ($this->options as $key => $val) {
                if (in_array($key, ['aggregate', 'cluster', 'connections', 'exceptions', 'prefix', 'profile', 'replication', 'parameters'])) {
                    $params[$key] = $val;
                    unset($this->options[$key]);
                }
            }
            if ('' == $this->options['password']) {
                unset($this->options['password']);
            }
            $this->handler = new \Predis\Client($this->options, $params);
            $this->options['prefix'] = '';
        } else {
            throw new \BadFunctionCallException('not support: redis');
        }
    }
    public function has($name)
    {
        return $this->handler->exists($this->getCacheKey($name)) ? true : false;
    }
    public function get($name, $default = false)
    {
        $this->readTimes++;
        $value = $this->handler->get($this->getCacheKey($name));
        if (is_null($value) || false === $value) {
            return $default;
        }
        return $this->unserialize($value);
    }
    public function set($name, $value, $expire = null)
    {
        $this->writeTimes++;
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        if ($this->tag && !$this->has($name)) {
            $first = true;
        }
        $key    = $this->getCacheKey($name);
        $expire = $this->getExpireTime($expire);
        $value = $this->serialize($value);
        if ($expire) {
            $result = $this->handler->setex($key, $expire, $value);
        } else {
            $result = $this->handler->set($key, $value);
        }
        isset($first) && $this->setTagItem($key);
        return $result;
    }
    public function inc($name, $step = 1)
    {
        $this->writeTimes++;
        $key = $this->getCacheKey($name);
        return $this->handler->incrby($key, $step);
    }
    public function dec($name, $step = 1)
    {
        $this->writeTimes++;
        $key = $this->getCacheKey($name);
        return $this->handler->decrby($key, $step);
    }
    public function rm($name)
    {
        $this->writeTimes++;
        return $this->handler->del($this->getCacheKey($name));
    }
    public function clear($tag = null)
    {
        if ($tag) {
            $keys = $this->getTagItem($tag);
            $this->handler->del($keys);
            $tagName = $this->getTagKey($tag);
            $this->handler->del($tagName);
            return true;
        }
        $this->writeTimes++;
        return $this->handler->flushDB();
    }
    public function tag($name, $keys = null, $overlay = false)
    {
        if (is_null($keys)) {
            $this->tag = $name;
        } else {
            $tagName = $this->getTagKey($name);
            if ($overlay) {
                $this->handler->del($tagName);
            }
            foreach ($keys as $key) {
                $this->handler->sAdd($tagName, $key);
            }
        }
        return $this;
    }
    protected function setTagItem($name)
    {
        if ($this->tag) {
            $tagName = $this->getTagKey($this->tag);
            $this->handler->sAdd($tagName, $name);
        }
    }
    protected function getTagItem($tag)
    {
        $tagName = $this->getTagKey($tag);
        return $this->handler->sMembers($tagName);
    }
}
