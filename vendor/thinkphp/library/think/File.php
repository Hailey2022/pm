<?php
namespace think;
use SplFileObject;
class File extends SplFileObject
{
    private $error = '';
    protected $filename;
    protected $saveName;
    protected $rule = 'date';
    protected $validate = [];
    protected $isTest;
    protected $info = [];
    protected $hash = [];
    public function __construct($filename, $mode = 'r')
    {
        parent::__construct($filename, $mode);
        $this->filename = $this->getRealPath() ?: $this->getPathname();
    }
    public function isTest($test = false)
    {
        $this->isTest = $test;
        return $this;
    }
    public function setUploadInfo($info)
    {
        $this->info = $info;
        return $this;
    }
    public function getInfo($name = '')
    {
        return isset($this->info[$name]) ? $this->info[$name] : $this->info;
    }
    public function getSaveName()
    {
        return $this->saveName;
    }
    public function setSaveName($saveName)
    {
        $this->saveName = $saveName;
        return $this;
    }
    public function hash($type = 'sha1')
    {
        if (!isset($this->hash[$type])) {
            $this->hash[$type] = hash_file($type, $this->filename);
        }
        return $this->hash[$type];
    }
    protected function checkPath($path)
    {
        if (is_dir($path)) {
            return true;
        }
        if (mkdir($path, 0755, true)) {
            return true;
        }
        $this->error = ['directory {:path} creation failed', ['path' => $path]];
        return false;
    }
    public function getMime()
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        return finfo_file($finfo, $this->filename);
    }
    public function rule($rule)
    {
        $this->rule = $rule;
        return $this;
    }
    public function validate($rule = [])
    {
        $this->validate = $rule;
        return $this;
    }
    public function isValid()
    {
        if ($this->isTest) {
            return is_file($this->filename);
        }
        return is_uploaded_file($this->filename);
    }
    public function check($rule = [])
    {
        $rule = $rule ?: $this->validate;
        if ((isset($rule['size']) && !$this->checkSize($rule['size']))
            || (isset($rule['type']) && !$this->checkMime($rule['type']))
            || (isset($rule['ext']) && !$this->checkExt($rule['ext']))
            || !$this->checkImg()) {
            return false;
        }
        return true;
    }
    public function checkExt($ext)
    {
        if (is_string($ext)) {
            $ext = explode(',', $ext);
        }
        $extension = strtolower(pathinfo($this->getInfo('name'), PATHINFO_EXTENSION));
        if (!in_array($extension, $ext)) {
            $this->error = 'extensions to upload is not allowed';
            return false;
        }
        return true;
    }
    public function checkImg()
    {
        $extension = strtolower(pathinfo($this->getInfo('name'), PATHINFO_EXTENSION));
        if (in_array($extension, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf']) && !in_array($this->getImageType($this->filename), [1, 2, 3, 4, 6, 13])) {
            $this->error = 'illegal image files';
            return false;
        }
        return true;
    }
    protected function getImageType($image)
    {
        if (function_exists('exif_imagetype')) {
            return exif_imagetype($image);
        }
        try {
            $info = getimagesize($image);
            return $info ? $info[2] : false;
        } catch (\Exception $e) {
            return false;
        }
    }
    public function checkSize($size)
    {
        if ($this->getSize() > (int) $size) {
            $this->error = 'filesize not match';
            return false;
        }
        return true;
    }
    public function checkMime($mime)
    {
        if (is_string($mime)) {
            $mime = explode(',', $mime);
        }
        if (!in_array(strtolower($this->getMime()), $mime)) {
            $this->error = 'mimetype to upload is not allowed';
            return false;
        }
        return true;
    }
    public function move($path, $savename = true, $replace = true, $autoAppendExt = true)
    {
        if (!empty($this->info['error'])) {
            $this->error($this->info['error']);
            return false;
        }
        if (!$this->isValid()) {
            $this->error = 'upload illegal files';
            return false;
        }
        if (!$this->check()) {
            return false;
        }
        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $saveName = $this->buildSaveName($savename, $autoAppendExt);
        $filename = $path . $saveName;
        if (false === $this->checkPath(dirname($filename))) {
            return false;
        }
        if (!$replace && is_file($filename)) {
            $this->error = ['has the same filename: {:filename}', ['filename' => $filename]];
            return false;
        }
        if ($this->isTest) {
            rename($this->filename, $filename);
        } elseif (!move_uploaded_file($this->filename, $filename)) {
            $this->error = 'upload write error';
            return false;
        }
        $file = new self($filename);
        $file->setSaveName($saveName);
        $file->setUploadInfo($this->info);
        return $file;
    }
    protected function buildSaveName($savename, $autoAppendExt = true)
    {
        if (true === $savename) {
            $savename = $this->autoBuildName();
        } elseif ('' === $savename || false === $savename) {
            $savename = $this->getInfo('name');
        }
        if ($autoAppendExt && false === strpos($savename, '.')) {
            $savename .= '.' . pathinfo($this->getInfo('name'), PATHINFO_EXTENSION);
        }
        return $savename;
    }
    protected function autoBuildName()
    {
        if ($this->rule instanceof \Closure) {
            $savename = call_user_func_array($this->rule, [$this]);
        } else {
            switch ($this->rule) {
                case 'date':
                    $savename = date('Ymd') . DIRECTORY_SEPARATOR . md5(microtime(true));
                    break;
                default:
                    if (in_array($this->rule, hash_algos())) {
                        $hash     = $this->hash($this->rule);
                        $savename = substr($hash, 0, 2) . DIRECTORY_SEPARATOR . substr($hash, 2);
                    } elseif (is_callable($this->rule)) {
                        $savename = call_user_func($this->rule);
                    } else {
                        $savename = date('Ymd') . DIRECTORY_SEPARATOR . md5(microtime(true));
                    }
            }
        }
        return $savename;
    }
    private function error($errorNo)
    {
        switch ($errorNo) {
            case 1:
            case 2:
                $this->error = 'upload File size exceeds the maximum value';
                break;
            case 3:
                $this->error = 'only the portion of file is uploaded';
                break;
            case 4:
                $this->error = 'no file to uploaded';
                break;
            case 6:
                $this->error = 'upload temp dir not found';
                break;
            case 7:
                $this->error = 'file write error';
                break;
            default:
                $this->error = 'unknown upload error';
        }
    }
    public function getError()
    {
        $lang = Container::get('lang');
        if (is_array($this->error)) {
            list($msg, $vars) = $this->error;
        } else {
            $msg  = $this->error;
            $vars = [];
        }
        return $lang->has($msg) ? $lang->get($msg, $vars) : $msg;
    }
    public function __call($method, $args)
    {
        return $this->hash($method);
    }
}
