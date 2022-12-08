<?php










namespace think\response;

use think\Exception;
use think\Response;

class Download extends Response
{
    protected $expire = 360;
    protected $name;
    protected $mimeType;
    protected $isContent = false;
    protected $openinBrowser = false;
    
    protected function output($data)
    {
        if (!$this->isContent && !is_file($data)) {
            throw new Exception('file not exists:' . $data);
        }

        ob_end_clean();

        if (!empty($this->name)) {
            $name = $this->name;
        } else {
            $name = !$this->isContent ? pathinfo($data, PATHINFO_BASENAME) : '';
        }

        if ($this->isContent) {
            $mimeType = $this->mimeType;
            $size     = strlen($data);
        } else {
            $mimeType = $this->getMimeType($data);
            $size     = filesize($data);
        }

        $this->header['Pragma']                    = 'public';
        $this->header['Content-Type']              = $mimeType ?: 'application/octet-stream';
        $this->header['Cache-control']             = 'max-age=' . $this->expire;
        $this->header['Content-Disposition']       = $this->openinBrowser ? 'inline' : 'attachment; filename="' . $name . '"';
        $this->header['Content-Length']            = $size;
        $this->header['Content-Transfer-Encoding'] = 'binary';
        $this->header['Expires']                   = gmdate("D, d M Y H:i:s", time() + $this->expire) . ' GMT';

        $this->lastModified(gmdate('D, d M Y H:i:s', time()) . ' GMT');

        $data = $this->isContent ? $data : file_get_contents($data);
        return $data;
    }

    
    public function isContent($content = true)
    {
        $this->isContent = $content;
        return $this;
    }

    
    public function expire($expire)
    {
        $this->expire = $expire;
        return $this;
    }

    
    public function mimeType($mimeType)
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    
    protected function getMimeType($filename)
    {
        if (!empty($this->mimeType)) {
            return $this->mimeType;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return finfo_file($finfo, $filename);
    }

    
    public function name($filename, $extension = true)
    {
        $this->name = $filename;

        if ($extension && false === strpos($filename, '.')) {
            $this->name .= '.' . pathinfo($this->data, PATHINFO_EXTENSION);
        }

        return $this;
    }

    
    public function openinBrowser($openinBrowser) {
        $this->openinBrowser = $openinBrowser;
        return $this;
    }
}
