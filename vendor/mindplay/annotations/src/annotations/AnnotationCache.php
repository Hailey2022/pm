<?php
namespace mindplay\annotations;
class AnnotationCache
{
    const PHP_TAG = "<?php\n\n";
    private $_fileMode;
    private $_root;
    public function __construct($root, $fileMode = 0777)
    {
        $this->_root = $root;
        $this->_fileMode = $fileMode;
    }
    public function exists($key)
    {
        return \file_exists($this->_getPath($key));
    }
    public function store($key, $code)
    {
        $path = $this->_getPath($key);
        $content = self::PHP_TAG . $code . "\n";
        if (@\file_put_contents($path, $content, LOCK_EX) === false) {
            throw new AnnotationException("Unable to write cache file: {$path}");
        }
        if (@\chmod($path, $this->_fileMode) === false) {
            throw new AnnotationException("Unable to set permissions of cache file: {$path}");
        }
    }
    public function fetch($key)
    {
        return include($this->_getPath($key));
    }
    public function getTimestamp($key)
    {
        return \filemtime($this->_getPath($key));
    }
    private function _getPath($key)
    {
        return $this->_root . DIRECTORY_SEPARATOR . $key . '.annotations.php';
    }
    public function getRoot()
    {
        return $this->_root;
    }
}
