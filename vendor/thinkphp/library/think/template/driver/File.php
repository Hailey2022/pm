<?php
namespace think\template\driver;
use think\Exception;
class File
{
    protected $cacheFile;
    public function write($cacheFile, $content)
    {
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (false === file_put_contents($cacheFile, $content)) {
            throw new Exception('cache write error:' . $cacheFile, 11602);
        }
    }
    public function read($cacheFile, $vars = [])
    {
        $this->cacheFile = $cacheFile;
        if (!empty($vars) && is_array($vars)) {
            extract($vars, EXTR_OVERWRITE);
        }
        //载入模版缓存文件
        include $this->cacheFile;
    }
    public function check($cacheFile, $cacheTime)
    {
        if (!file_exists($cacheFile)) {
            return false;
        }
        if (0 != $cacheTime && time() > filemtime($cacheFile) + $cacheTime) {
            return false;
        }
        return true;
    }
}
