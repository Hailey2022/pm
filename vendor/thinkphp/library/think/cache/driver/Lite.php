<?php










namespace think\cache\driver;

use think\cache\Driver;


class Lite extends Driver
{
    protected $options = [
        'prefix' => '',
        'path'   => '',
        'expire' => 0, 
    ];

    
    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        if (substr($this->options['path'], -1) != DIRECTORY_SEPARATOR) {
            $this->options['path'] .= DIRECTORY_SEPARATOR;
        }

    }

    
    protected function getCacheKey($name)
    {
        return $this->options['path'] . $this->options['prefix'] . md5($name) . '.php';
    }

    
    public function has($name)
    {
        return $this->get($name) ? true : false;
    }

    
    public function get($name, $default = false)
    {
        $this->readTimes++;

        $filename = $this->getCacheKey($name);

        if (is_file($filename)) {
            
            $mtime = filemtime($filename);

            if ($mtime < time()) {
                
                unlink($filename);
                return $default;
            }

            return include $filename;
        } else {
            return $default;
        }
    }

    
    public function set($name, $value, $expire = null)
    {
        $this->writeTimes++;

        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        if ($expire instanceof \DateTime) {
            $expire = $expire->getTimestamp();
        } else {
            $expire = 0 === $expire ? 10 * 365 * 24 * 3600 : $expire;
            $expire = time() + $expire;
        }

        $filename = $this->getCacheKey($name);

        if ($this->tag && !is_file($filename)) {
            $first = true;
        }

        $ret = file_put_contents($filename, ("<?php return " . var_export($value, true) . ";"));

        
        if ($ret) {
            isset($first) && $this->setTagItem($filename);
            touch($filename, $expire);
        }

        return $ret;
    }

    
    public function inc($name, $step = 1)
    {
        if ($this->has($name)) {
            $value = $this->get($name) + $step;
        } else {
            $value = $step;
        }

        return $this->set($name, $value, 0) ? $value : false;
    }

    
    public function dec($name, $step = 1)
    {
        if ($this->has($name)) {
            $value = $this->get($name) - $step;
        } else {
            $value = -$step;
        }

        return $this->set($name, $value, 0) ? $value : false;
    }

    
    public function rm($name)
    {
        $this->writeTimes++;

        return unlink($this->getCacheKey($name));
    }

    
    public function clear($tag = null)
    {
        if ($tag) {
            
            $keys = $this->getTagItem($tag);
            foreach ($keys as $key) {
                unlink($key);
            }

            $this->rm($this->getTagKey($tag));
            return true;
        }

        $this->writeTimes++;

        array_map("unlink", glob($this->options['path'] . ($this->options['prefix'] ? $this->options['prefix'] . DIRECTORY_SEPARATOR : '') . '*.php'));
    }
}
