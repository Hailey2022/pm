<?php










namespace think;

use think\image\Exception as ImageException;
use think\image\gif\Gif;

class Image
{

    
    const THUMB_SCALING   = 1; 
    const THUMB_FILLED    = 2; 
    const THUMB_CENTER    = 3; 
    const THUMB_NORTHWEST = 4; 
    const THUMB_SOUTHEAST = 5; 
    const THUMB_FIXED     = 6; 
    
    const WATER_NORTHWEST = 1; 
    const WATER_NORTH     = 2; 
    const WATER_NORTHEAST = 3; 
    const WATER_WEST      = 4; 
    const WATER_CENTER    = 5; 
    const WATER_EAST      = 6; 
    const WATER_SOUTHWEST = 7; 
    const WATER_SOUTH     = 8; 
    const WATER_SOUTHEAST = 9; 
    
    const FLIP_X = 1; //X轴翻转
    const FLIP_Y = 2; //Y轴翻转

    
    protected $im;

    
    protected $gif;

    
    protected $info;

    protected function __construct(\SplFileInfo $file)
    {
        //获取图像信息
        $info = @getimagesize($file->getPathname());

        //检测图像合法性
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            throw new ImageException('Illegal image file');
        }

        //设置图像信息
        $this->info = [
            'width'  => $info[0],
            'height' => $info[1],
            'type'   => image_type_to_extension($info[2], false),
            'mime'   => $info['mime'],
        ];

        //打开图像
        if ('gif' == $this->info['type']) {
            $this->gif = new Gif($file->getPathname());
            $this->im  = @imagecreatefromstring($this->gif->image());
        } else {
            $fun      = "imagecreatefrom{$this->info['type']}";
            $this->im = @$fun($file->getPathname());
        }

        if (empty($this->im)) {
            throw new ImageException('Failed to create image resources!');
        }

    }

    
    public static function open($file)
    {
        if (is_string($file)) {
            $file = new \SplFileInfo($file);
        }
        if (!$file->isFile()) {
            throw new ImageException('image file not exist');
        }
        return new self($file);
    }

    
    public function save($pathname, $type = null, $quality = 80, $interlace = true)
    {
        //自动获取图像类型
        if (is_null($type)) {
            $type = $this->info['type'];
        } else {
            $type = strtolower($type);
        }
        //保存图像
        if ('jpeg' == $type || 'jpg' == $type) {
            //JPEG图像设置隔行扫描
            imageinterlace($this->im, $interlace);
            imagejpeg($this->im, $pathname, $quality);
        } elseif ('gif' == $type && !empty($this->gif)) {
            $this->gif->save($pathname);
        } elseif ('png' == $type) {
            //设定保存完整的 alpha 通道信息
            imagesavealpha($this->im, true);
            //ImagePNG生成图像的质量范围从0到9的
            imagepng($this->im, $pathname, min((int) ($quality / 10), 9));
        } else {
            $fun = 'image' . $type;
            $fun($this->im, $pathname);
        }

        return $this;
    }

    
    public function width()
    {
        return $this->info['width'];
    }

    
    public function height()
    {
        return $this->info['height'];
    }

    
    public function type()
    {
        return $this->info['type'];
    }

    
    public function mime()
    {
        return $this->info['mime'];
    }

    
    public function size()
    {
        return [$this->info['width'], $this->info['height']];
    }

    
    public function rotate($degrees = 90)
    {
        do {
            $img = imagerotate($this->im, -$degrees, imagecolorallocatealpha($this->im, 0, 0, 0, 127));
            imagedestroy($this->im);
            $this->im = $img;
        } while (!empty($this->gif) && $this->gifNext());

        $this->info['width']  = imagesx($this->im);
        $this->info['height'] = imagesy($this->im);

        return $this;
    }

    
    public function flip($direction = self::FLIP_X)
    {
        //原图宽度和高度
        $w = $this->info['width'];
        $h = $this->info['height'];

        do {

            $img = imagecreatetruecolor($w, $h);

            switch ($direction) {
                case self::FLIP_X:
                    for ($y = 0; $y < $h; $y++) {
                        imagecopy($img, $this->im, 0, $h - $y - 1, 0, $y, $w, 1);
                    }
                    break;
                case self::FLIP_Y:
                    for ($x = 0; $x < $w; $x++) {
                        imagecopy($img, $this->im, $w - $x - 1, 0, $x, 0, 1, $h);
                    }
                    break;
                default:
                    throw new ImageException('不支持的翻转类型');
            }

            imagedestroy($this->im);
            $this->im = $img;

        } while (!empty($this->gif) && $this->gifNext());

        return $this;
    }

    
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)
    {
        //设置保存尺寸
        empty($width) && $width   = $w;
        empty($height) && $height = $h;
        do {
            //创建新图像
            $img = imagecreatetruecolor($width, $height);
            
            $color = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $color);
            //裁剪
            imagecopyresampled($img, $this->im, 0, 0, $x, $y, $width, $height, $w, $h);
            imagedestroy($this->im); //销毁原图
            //设置新图像
            $this->im = $img;
        } while (!empty($this->gif) && $this->gifNext());
        $this->info['width']  = (int) $width;
        $this->info['height'] = (int) $height;
        return $this;
    }

    
    public function thumb($width, $height, $type = self::THUMB_SCALING)
    {
        //原图宽度和高度
        $w = $this->info['width'];
        $h = $this->info['height'];
        
        switch ($type) {
            
            case self::THUMB_SCALING:
                //原图尺寸小于缩略图尺寸则不进行缩略
                if ($w < $width && $h < $height) {
                    return $this;
                }
                //计算缩放比例
                $scale = min($width / $w, $height / $h);
                //设置缩略图的坐标及宽度和高度
                $x      = $y      = 0;
                $width  = $w * $scale;
                $height = $h * $scale;
                break;
            
            case self::THUMB_CENTER:
                //计算缩放比例
                $scale = max($width / $w, $height / $h);
                //设置缩略图的坐标及宽度和高度
                $w = $width / $scale;
                $h = $height / $scale;
                $x = ($this->info['width'] - $w) / 2;
                $y = ($this->info['height'] - $h) / 2;
                break;
            
            case self::THUMB_NORTHWEST:
                //计算缩放比例
                $scale = max($width / $w, $height / $h);
                //设置缩略图的坐标及宽度和高度
                $x = $y = 0;
                $w = $width / $scale;
                $h = $height / $scale;
                break;
            
            case self::THUMB_SOUTHEAST:
                //计算缩放比例
                $scale = max($width / $w, $height / $h);
                //设置缩略图的坐标及宽度和高度
                $w = $width / $scale;
                $h = $height / $scale;
                $x = $this->info['width'] - $w;
                $y = $this->info['height'] - $h;
                break;
            
            case self::THUMB_FILLED:
                //计算缩放比例
                if ($w < $width && $h < $height) {
                    $scale = 1;
                } else {
                    $scale = min($width / $w, $height / $h);
                }
                //设置缩略图的坐标及宽度和高度
                $neww = $w * $scale;
                $newh = $h * $scale;
                $x    = $this->info['width'] - $w;
                $y    = $this->info['height'] - $h;
                $posx = ($width - $w * $scale) / 2;
                $posy = ($height - $h * $scale) / 2;
                do {
                    //创建新图像
                    $img = imagecreatetruecolor($width, $height);
                    
                    $color = imagecolorallocate($img, 255, 255, 255);
                    imagefill($img, 0, 0, $color);
                    //裁剪
                    imagecopyresampled($img, $this->im, $posx, $posy, $x, $y, $neww, $newh, $w, $h);
                    imagedestroy($this->im); //销毁原图
                    $this->im = $img;
                } while (!empty($this->gif) && $this->gifNext());
                $this->info['width']  = (int) $width;
                $this->info['height'] = (int) $height;
                return $this;
            
            case self::THUMB_FIXED:
                $x = $y = 0;
                break;
            default:
                throw new ImageException('不支持的缩略图裁剪类型');
        }
        
        return $this->crop($w, $h, $x, $y, $width, $height);
    }

    
    public function water($source, $locate = self::WATER_SOUTHEAST, $alpha = 100)
    {
        if (!is_file($source)) {
            throw new ImageException('水印图像不存在');
        }
        //获取水印图像信息
        $info = getimagesize($source);
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            throw new ImageException('非法水印文件');
        }
        //创建水印图像资源
        $fun   = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $water = $fun($source);
        //设定水印图像的混色模式
        imagealphablending($water, true);
        
        switch ($locate) {
            
            case self::WATER_SOUTHEAST:
                $x = $this->info['width'] - $info[0];
                $y = $this->info['height'] - $info[1];
                break;
            
            case self::WATER_SOUTHWEST:
                $x = 0;
                $y = $this->info['height'] - $info[1];
                break;
            
            case self::WATER_NORTHWEST:
                $x = $y = 0;
                break;
            
            case self::WATER_NORTHEAST:
                $x = $this->info['width'] - $info[0];
                $y = 0;
                break;
            
            case self::WATER_CENTER:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;
            
            case self::WATER_SOUTH:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = $this->info['height'] - $info[1];
                break;
            
            case self::WATER_EAST:
                $x = $this->info['width'] - $info[0];
                $y = ($this->info['height'] - $info[1]) / 2;
                break;
            
            case self::WATER_NORTH:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = 0;
                break;
            
            case self::WATER_WEST:
                $x = 0;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;
            default:
                
                if (is_array($locate)) {
                    list($x, $y) = $locate;
                } else {
                    throw new ImageException('不支持的水印位置类型');
                }
        }
        do {
            //添加水印
            $src = imagecreatetruecolor($info[0], $info[1]);
            
            $color = imagecolorallocate($src, 255, 255, 255);
            imagefill($src, 0, 0, $color);
            imagecopy($src, $this->im, 0, 0, $x, $y, $info[0], $info[1]);
            imagecopy($src, $water, 0, 0, 0, 0, $info[0], $info[1]);
            imagecopymerge($this->im, $src, $x, $y, 0, 0, $info[0], $info[1], $alpha);
            //销毁零时图片资源
            imagedestroy($src);
        } while (!empty($this->gif) && $this->gifNext());
        //销毁水印资源
        imagedestroy($water);
        return $this;
    }

    
    public function text($text, $font, $size, $color = '#00000000',
        $locate = self::WATER_SOUTHEAST, $offset = 0, $angle = 0) {

        if (!is_file($font)) {
            throw new ImageException("不存在的字体文件：{$font}");
        }
        //获取文字信息
        $info = imagettfbbox($size, $angle, $font, $text);
        $minx = min($info[0], $info[2], $info[4], $info[6]);
        $maxx = max($info[0], $info[2], $info[4], $info[6]);
        $miny = min($info[1], $info[3], $info[5], $info[7]);
        $maxy = max($info[1], $info[3], $info[5], $info[7]);
        
        $x = $minx;
        $y = abs($miny);
        $w = $maxx - $minx;
        $h = $maxy - $miny;
        
        switch ($locate) {
            
            case self::WATER_SOUTHEAST:
                $x += $this->info['width'] - $w;
                $y += $this->info['height'] - $h;
                break;
            
            case self::WATER_SOUTHWEST:
                $y += $this->info['height'] - $h;
                break;
            
            case self::WATER_NORTHWEST:
                
                break;
            
            case self::WATER_NORTHEAST:
                $x += $this->info['width'] - $w;
                break;
            
            case self::WATER_CENTER:
                $x += ($this->info['width'] - $w) / 2;
                $y += ($this->info['height'] - $h) / 2;
                break;
            
            case self::WATER_SOUTH:
                $x += ($this->info['width'] - $w) / 2;
                $y += $this->info['height'] - $h;
                break;
            
            case self::WATER_EAST:
                $x += $this->info['width'] - $w;
                $y += ($this->info['height'] - $h) / 2;
                break;
            
            case self::WATER_NORTH:
                $x += ($this->info['width'] - $w) / 2;
                break;
            
            case self::WATER_WEST:
                $y += ($this->info['height'] - $h) / 2;
                break;
            default:
                
                if (is_array($locate)) {
                    list($posx, $posy) = $locate;
                    $x += $posx;
                    $y += $posy;
                } else {
                    throw new ImageException('不支持的文字位置类型');
                }
        }
        
        if (is_array($offset)) {
            $offset        = array_map('intval', $offset);
            list($ox, $oy) = $offset;
        } else {
            $offset = intval($offset);
            $ox     = $oy     = $offset;
        }
        
        if (is_string($color) && 0 === strpos($color, '#')) {
            $color = str_split(substr($color, 1), 2);
            $color = array_map('hexdec', $color);
            if (empty($color[3]) || $color[3] > 127) {
                $color[3] = 0;
            }
        } elseif (!is_array($color)) {
            throw new ImageException('错误的颜色值');
        }
        do {
            
            $col = imagecolorallocatealpha($this->im, $color[0], $color[1], $color[2], $color[3]);
            imagettftext($this->im, $size, $angle, $x + $ox, $y + $oy, $col, $font, $text);
        } while (!empty($this->gif) && $this->gifNext());
        return $this;
    }

    
    protected function gifNext()
    {
        ob_start();
        ob_implicit_flush(0);
        imagegif($this->im);
        $img = ob_get_clean();
        $this->gif->image($img);
        $next = $this->gif->nextImage();
        if ($next) {
            imagedestroy($this->im);
            $this->im = imagecreatefromstring($next);
            return $next;
        } else {
            imagedestroy($this->im);
            $this->im = imagecreatefromstring($this->gif->image());
            return false;
        }
    }

    
    public function __destruct()
    {
        empty($this->im) || imagedestroy($this->im);
    }

}
