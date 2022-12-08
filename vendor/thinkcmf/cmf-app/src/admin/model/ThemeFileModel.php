<?php









namespace app\admin\model;

use think\Model;

class ThemeFileModel extends Model
{
    
    protected $name = 'theme_file';

    protected $type = [
        'more'        => 'array',
        'config_more' => 'array',
        'draft_more'  => 'array'
    ];
}
