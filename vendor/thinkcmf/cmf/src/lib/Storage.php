<?php
namespace cmf\lib;
class Storage
{
    private $driver;
    protected static $instance;
    public function __construct($driver = null, $driverConfig = null)
    {
        if (empty($driver)) {
            $storageSetting = cmf_get_option('storage');
            if (empty($storageSetting)) {
                $driver       = 'Local';
                $driverConfig = [];
            } else {
                $driver = isset($storageSetting['type']) ? $storageSetting['type'] : 'Local';
                if ($driver == 'Local') {
                    $driverConfig = [];
                } else {
                    $driverConfig = $storageSetting['storages'][$driver];
                }
            }
        }
        if (empty($driverConfig['driver'])) {
            $storageDriverClass = "\\cmf\\lib\\storage\\$driver";
        } else {
            $storageDriverClass = $driverConfig['driver'];
        }
        $storage = new $storageDriverClass($driverConfig);
        $this->driver = $storage;
    }
    public function upload($file, $filePath, $fileType = 'image', $param = null)
    {
        return $this->driver->upload($file, $filePath, $fileType, $param);
    }
    public static function instance($type = null, $config = null)
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($type, $config);
        }
        return self::$instance;
    }
    public function getPreviewUrl($file, $style = '')
    {
        return $this->driver->getPreviewUrl($file, $style);
    }
    public function getImageUrl($file, $style = '')
    {
        return $this->driver->getImageUrl($file, $style);
    }
    public function getUrl($file, $style = '')
    {
        return $this->driver->getUrl($file, $style);
    }
    public function getFileDownloadUrl($file, $expires = 3600)
    {
        return $this->driver->getFileDownloadUrl($file, $expires);
    }
    public function getDomain()
    {
        return $this->driver->getDomain();
    }
    public function getFilePath($url)
    {
        return $this->driver->getFilePath($url);
    }
}