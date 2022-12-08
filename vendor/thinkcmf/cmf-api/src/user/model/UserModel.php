<?php











namespace api\user\model;

use think\Model;

class UserModel extends Model
{
    
    protected $name = 'user';

    protected $type = [
        'more' => 'array',
    ];

    
    public function getAvatarAttr($value)
    {
        return cmf_get_user_avatar_url($value);
    }
}
