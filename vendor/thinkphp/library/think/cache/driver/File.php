<?php
namespace think\cache\driver;
use think\cache\Driver;
use think\Container;
class File extends Driver
{
    protected $options = [
        'expire'        => 0,
        'cache_subdir'  => true,
        'prefix'        => '',
        'path'          => '',
        'hash_type'     => 'md5',
        'data_compress' => false,
        'serialize'     => true,
    ];
    protected $expire;
    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if (empty($this->options['path'])) {
            $this->options['path'] = Container::get('app')->getRuntimePath() . 'cache' . DIRECTORY_SEPARATOR;
        } elseif (substr($this->options['path'], -1) != DIRECTORY_SEPARATOR) {
            $this->options['path'] .= DIRECTORY_SEPARATOR;
        }
        $this->init();
    }
    private function init()
    {
        try {
            if (!is_dir($this->options['path']) && mkdir($this->options['path'], 0755, true)) {
                return true;
            }
        } catch (\Exception $e) {
        }
        return false;
    }
    protected function getCacheKey($name, $auto = false)
    {
        $name = hash($this->options['hash_type'], $name);
        if ($this->options['cache_subdir']) {
            $name = substr($name, 0, 2) . DIRECTORY_SEPARATOR . substr($name, 2);
        }
        if ($this->options['prefix']) {
            $name = $this->options['prefix'] . DIRECTORY_SEPARATOR . $name;
        }
        $filename = $this->options['path'] . $name . '.php';
        $dir      = dirname($filename);
        if ($auto && !is_dir($dir)) {
            try {
                mkdir($dir, 0755, true);
            } catch (\Exception $e) {
            }
        }
        return $filename;
    }
    public function has($name)
    {
        return false !== $this->get($name) ? true : false;
    }
    public function get($name, $default = false)
    {
        $this->readTimes++;
        $filename = $this->getCacheKey($name);
        if (!is_file($filename)) {
            return $default;
        }
        $content      = file_get_contents($filename);
        $this->expire = null;
        if (false !== $content) {
            $expire = (int) substr($content, 8, 12);
            if (0 != $expire && time() > filemtime($filename) + $expire) {
                //??????????????????????????????
                $this->unlink($filename);
                return $default;
            }
            $this->expire = $expire;
            $content      = substr($content, 32);
            if ($this->options['data_compress'] && function_exists('gzcompress')) {
                //??????????????????
                $content = gzuncompress($content);
            }
            return $this->unserialize($content);
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
        $expire   = $this->getExpireTime($expire);
        $filename = $this->getCacheKey($name, true);
        if ($this->tag && !is_file($filename)) {
            $first = true;
        }
        $data = $this->serialize($value);
        if ($this->options['data_compress'] && function_exists('gzcompress')) {
            //????????????
            $data = gzcompress($data, 3);
        }
        $data   = "<?php\n//" . sprintf('%012d', $expire) . "\n exit();?>\n" . $data;
        $result = file_put_contents($filename, $data);
        if ($result) {
            isset($first) && $this->setTagItem($filename);
            clearstatcache();
            return true;
        } else {
            return false;
        }
    }
    public function inc($name, $step = 1)
    {
        if ($this->has($name)) {
            $value  = $this->get($name) + $step;
            $expire = $this->expire;
        } else {
            $value  = $step;
            $expire = 0;
        }
        return $this->set($name, $value, $expire) ? $value : false;
    }
    public function dec($name, $step = 1)
    {
        if ($this->has($name)) {
            $value  = $this->get($name) - $step;
            $expire = $this->expire;
        } else {
            $value  = -$step;
            $expire = 0;
        }
        return $this->set($name, $value, $expire) ? $value : false;
    }
    public function rm($name)
    {
        $this->writeTimes++;
        try {
            return $this->unlink($this->getCacheKey($name));
        } catch (\Exception $e) {
        }
    }
    public function clear($tag = null)
    {
        if ($tag) {
            $keys = $this->getTagItem($tag);
            foreach ($keys as $key) {
                $this->unlink($key);
            }
            $this->rm($this->getTagKey($tag));
            return true;
        }
        $this->writeTimes++;
        $files = (array) glob($this->options['path'] . ($this->options['prefix'] ? $this->options['prefix'] . DIRECTORY_SEPARATOR : '') . '*');
        foreach ($files as $path) {
            if (is_dir($path)) {
                $matches = glob($path . DIRECTORY_SEPARATOR . '*.php');
                if (is_array($matches)) {
                    array_map(function ($v) {
                        $this->unlink($v);
                    }, $matches);
                }
                rmdir($path);
            } else {
                $this->unlink($path);
            }
        }
        return true;
    }
    private function unlink($path)
    {
        return is_file($path) && unlink($path);
    }
}
