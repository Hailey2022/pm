<?php









namespace cmf\model;

use think\Db;
use think\Model;

class UserModel extends Model
{
    
    protected $name = 'user';

    
    protected $autoWriteTimestamp = true;

    
    protected $updateTime = false;

    
    protected $type = [
        'more' => 'array',
    ];


}
