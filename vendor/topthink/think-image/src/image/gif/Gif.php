<?php










namespace think\image\gif;

class Gif
{
    
    private $frames = [];
    
    private $delays = [];

    
    public function __construct($src = null, $mod = 'url')
    {
        if (!is_null($src)) {
            if ('url' == $mod && is_file($src)) {
                $src = file_get_contents($src);
            }
            
            try {
                $de           = new Decoder($src);
                $this->frames = $de->getFrames();
                $this->delays = $de->getDelays();
            } catch (\Exception $e) {
                throw new \Exception("解码GIF图片出错");
            }
        }
    }

    
    public function image($stream = null)
    {
        if (is_null($stream)) {
            $current = current($this->frames);
            return false === $current ? reset($this->frames) : $current;
        }
        $this->frames[key($this->frames)] = $stream;
    }

    
    public function nextImage()
    {
        return next($this->frames);
    }

    
    public function save($pathname)
    {
        $gif = new Encoder($this->frames, $this->delays, 0, 2, 0, 0, 0, 'bin');
        file_put_contents($pathname, $gif->getAnimation());
    }
}