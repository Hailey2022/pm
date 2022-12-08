<?php








namespace api\user\model;

use think\Model;

class UserLikeModel extends Model
{
    
    protected $name = 'user_like';
    
    
    public function getUrlAttr($value)
    {
        $url = json_decode($value, true);
        if (!empty($url)) {
            $url = url($url['action'], $url['param'], true, true);
        } else {
            $url = '';
        }
        return $url;
    }

    
    public function getThumbnailAttr($value)
    {
        if (!empty($value)) {
            $value = cmf_get_image_url($value);
        }

        return $value;
    }

}
