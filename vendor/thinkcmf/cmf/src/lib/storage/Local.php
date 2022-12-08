<?php









namespace cmf\lib\storage;

class Local
{
    private $config;

    
    public function __construct($config)
    {
        $this->config = $config;
    }

    
    public function upload($file, $filePath = '', $fileType = 'image', $param = null)
    {
        return [
            'preview_url' => $this->getPreviewUrl($file),
            'url'         => $this->getUrl($file),
        ];
    }

    
    public function getImageUrl($file, $style = '')
    {
        return $this->_getWebRoot() . '/upload/' . $file;
    }

    
    public function getPreviewUrl($file, $style = '')
    {
        return $this->_getWebRoot() . '/upload/' . $file;
    }

    
    public function getUrl($file, $style = '')
    {
        return $this->_getWebRoot() . '/upload/' . $file;
    }

    
    public function getFileDownloadUrl($file, $expires = 3600)
    {
        $url = $this->getUrl($file);
        return $url;
    }

    
    public function getDomain()
    {
        return request()->host();
    }

    
    public function getFilePath($url)
    {
        $storageDomain = $this->getDomain();
        $url           = preg_replace("/^http(s)?:\/\/$storageDomain/", '', $url);
        $url           = preg_replace("/^\/upload\//", '', $url);
        return $url;
    }

    private function _getWebRoot()
    {
        return cmf_get_domain() . cmf_get_root();

    }
}