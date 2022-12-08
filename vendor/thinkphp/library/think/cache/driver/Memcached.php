<?php










namespace think\cache\driver;

use think\cache\Driver;

class Memcached extends Driver
{
    protected $options = [
        'host'      => '127.0.0.1',
        'port'      => 11211,
        'expire'    => 0,
        'timeout'   => 0, 
        'prefix'    => '',
        'username'  => '', //账号
        'password'  => '', //密码
        'option'    => [],
        'serialize' => true,
    ];

    
    public function __construct($options = [])
    {
        if (!extension_loaded('memcached')) {
            throw new \BadFunctionCallException('not support: memcached');
        }

        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        $this->handler = new \Memcached;

        if (!empty($this->options['option'])) {
            $this->handler->setOptions($this->options['option']);
        }

        
        if ($this->options['timeout'] > 0) {
            $this->handler->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $this->options['timeout']);
        }

        
        $hosts = explode(',', $this->options['host']);
        $ports = explode(',', $this->options['port']);
        if (empty($ports[0])) {
            $ports[0] = 11211;
        }

        
        $servers = [];
        foreach ((array) $hosts as $i => $host) {
            $servers[] = [$host, (isset($ports[$i]) ? $ports[$i] : $ports[0]), 1];
        }

        $this->handler->addServers($servers);
        $this->handler->setOption(\Memcached::OPT_COMPRESSION, false);
        if ('' != $this->options['username']) {
            $this->handler->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $this->handler->setSaslAuthData($this->options['username'], $this->options['password']);
        }
    }

    
    public function has($name)
    {
        $key = $this->getCacheKey($name);

        return $this->handler->get($key) ? true : false;
    }

    
    public function get($name, $default = false)
    {
        $this->readTimes++;

        $result = $this->handler->get($this->getCacheKey($name));

        return false !== $result ? $this->unserialize($result) : $default;
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
        $value  = $this->serialize($value);

        if ($this->handler->set($key, $value, $expire)) {
            isset($first) && $this->setTagItem($key);
            return true;
        }

        return false;
    }

    
    public function inc($name, $step = 1)
    {
        $this->writeTimes++;

        $key = $this->getCacheKey($name);

        if ($this->handler->get($key)) {
            return $this->handler->increment($key, $step);
        }

        return $this->handler->set($key, $step);
    }

    
    public function dec($name, $step = 1)
    {
        $this->writeTimes++;

        $key   = $this->getCacheKey($name);
        $value = $this->handler->get($key) - $step;
        $res   = $this->handler->set($key, $value);

        return !$res ? false : $value;
    }

    
    public function rm($name, $ttl = false)
    {
        $this->writeTimes++;

        $key = $this->getCacheKey($name);

        return false === $ttl ?
        $this->handler->delete($key) :
        $this->handler->delete($key, $ttl);
    }

    
    public function clear($tag = null)
    {
        if ($tag) {
            
            $keys = $this->getTagItem($tag);

            $this->handler->deleteMulti($keys);
            $this->rm($this->getTagKey($tag));

            return true;
        }

        $this->writeTimes++;

        return $this->handler->flush();
    }

    
    public function tag($name, $keys = null, $overlay = false)
    {
        if (is_null($keys)) {
            $this->tag = $name;
        } else {
            $tagName = $this->getTagKey($name);
            if ($overlay) {
                $this->handler->delete($tagName);
            }

            if (!$this->has($tagName)) {
                $this->handler->set($tagName, '');
            }

            foreach ($keys as $key) {
                $this->handler->append($tagName, ',' . $key);
            }
        }

        return $this;
    }

    
    protected function setTagItem($name)
    {
        if ($this->tag) {
            $tagName = $this->getTagKey($this->tag);

            if ($this->has($tagName)) {
                $this->handler->append($tagName, ',' . $name);
            } else {
                $this->handler->set($tagName, $name);
            }

            $this->tag = null;
        }
    }

    
    public function getTagItem($tag)
    {
        $tagName = $this->getTagKey($tag);
        return explode(',', trim($this->handler->get($tagName), ','));
    }
}
