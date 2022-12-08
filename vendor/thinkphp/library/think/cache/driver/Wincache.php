<?php










namespace think\cache\driver;

use think\cache\Driver;


class Wincache extends Driver
{
    protected $options = [
        'prefix'    => '',
        'expire'    => 0,
        'serialize' => true,
    ];

    
    public function __construct($options = [])
    {
        if (!function_exists('wincache_ucache_info')) {
            throw new \BadFunctionCallException('not support: WinCache');
        }

        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
    }

    
    public function has($name)
    {
        $this->readTimes++;

        $key = $this->getCacheKey($name);

        return wincache_ucache_exists($key);
    }

    
    public function get($name, $default = false)
    {
        $this->readTimes++;

        $key = $this->getCacheKey($name);

        return wincache_ucache_exists($key) ? $this->unserialize(wincache_ucache_get($key)) : $default;
    }

    
    public function set($name, $value, $expire = null)
    {
        $this->writeTimes++;

        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        $key    = $this->getCacheKey($name);
        $expire = $this->getExpireTime($expire);
        $value  = $this->serialize($value);

        if ($this->tag && !$this->has($name)) {
            $first = true;
        }

        if (wincache_ucache_set($key, $value, $expire)) {
            isset($first) && $this->setTagItem($key);
            return true;
        }

        return false;
    }

    
    public function inc($name, $step = 1)
    {
        $this->writeTimes++;

        $key = $this->getCacheKey($name);

        return wincache_ucache_inc($key, $step);
    }

    
    public function dec($name, $step = 1)
    {
        $this->writeTimes++;

        $key = $this->getCacheKey($name);

        return wincache_ucache_dec($key, $step);
    }

    
    public function rm($name)
    {
        $this->writeTimes++;

        return wincache_ucache_delete($this->getCacheKey($name));
    }

    
    public function clear($tag = null)
    {
        if ($tag) {
            $keys = $this->getTagItem($tag);

            wincache_ucache_delete($keys);

            $tagName = $this->getTagkey($tag);
            $this->rm($tagName);
            return true;
        }

        $this->writeTimes++;
        return wincache_ucache_clear();
    }

}
