<?php










namespace think\cache\driver;

use think\cache\Driver;


class Xcache extends Driver
{
    protected $options = [
        'prefix'    => '',
        'expire'    => 0,
        'serialize' => true,
    ];

    
    public function __construct($options = [])
    {
        if (!function_exists('xcache_info')) {
            throw new \BadFunctionCallException('not support: Xcache');
        }

        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
    }

    
    public function has($name)
    {
        $key = $this->getCacheKey($name);

        return xcache_isset($key);
    }

    
    public function get($name, $default = false)
    {
        $this->readTimes++;

        $key = $this->getCacheKey($name);

        return xcache_isset($key) ? $this->unserialize(xcache_get($key)) : $default;
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

        if (xcache_set($key, $value, $expire)) {
            isset($first) && $this->setTagItem($key);
            return true;
        }

        return false;
    }

    
    public function inc($name, $step = 1)
    {
        $this->writeTimes++;

        $key = $this->getCacheKey($name);

        return xcache_inc($key, $step);
    }

    
    public function dec($name, $step = 1)
    {
        $this->writeTimes++;

        $key = $this->getCacheKey($name);

        return xcache_dec($key, $step);
    }

    
    public function rm($name)
    {
        $this->writeTimes++;

        return xcache_unset($this->getCacheKey($name));
    }

    
    public function clear($tag = null)
    {
        if ($tag) {
            
            $keys = $this->getTagItem($tag);

            foreach ($keys as $key) {
                xcache_unset($key);
            }

            $this->rm($this->getTagKey($tag));
            return true;
        }

        $this->writeTimes++;

        if (function_exists('xcache_unset_by_prefix')) {
            return xcache_unset_by_prefix($this->options['prefix']);
        } else {
            return false;
        }
    }
}
