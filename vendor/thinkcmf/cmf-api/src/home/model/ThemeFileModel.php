<?php












namespace api\home\model;

use think\Model;

class ThemeFileModel extends Model
{
    
    protected $name = 'theme_file';

    //类型转换
    protected $type = [
        'more' => 'array',
    ];

}

