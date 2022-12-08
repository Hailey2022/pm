<?php










namespace think\cache\driver;

use think\cache\Driver;

class Memcache extends Driver
{
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 11211,
        'expire'     => 0,
        'timeout'    => 0, 
        'persistent' => true,
        'prefix'     => '',
        'serialize'  => true,
    ];

    
    public function __construct($options = [])
    {
        if (!extension_loaded('memcache')) {
            throw new \BadFunctionCallException('not support: memcache');
        }

        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        $this->handler = new \Memcache;

        
        $hosts = explode(',', $this->options['host']);
        $ports = explode(',', $this->options['port']);

        if (empty($ports[0])) {
            $ports[0] = 11211;
        }

        
        foreach ((array) $hosts as $i => $host) {
            $port = isset($ports[$i]) ? $ports[$i] : $ports[0];
            $this->options['timeout'] > 0 ?
            $this->handler->addServer($host, $port, $this->options['persistent'], 1, $this->options['timeout']) :
            $this->handler->addServer($host, $port, $this->options['persistent'], 1);
        }
    }

    
    public function has($name)
    {
        $key = $this->getCacheKey($name);

        return false !== $this->handler->get($key);
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

        if ($this->handler->set($key, $value, 0, $expire)) {
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

            foreach ($keys as $key) {
                $this->handler->delete($key);
            }

            $tagName = $this->getTagKey($tag);
            $this->rm($tagName);
            return true;
        }

        $this->writeTimes++;

        return $this->handler->flush();
    }

}
