<?php
namespace app\admin\model;
use think\Model;
class RecycleBinModel extends Model
{
    protected $name = 'recycle_bin';
    protected $autoWriteTimestamp = true;
    protected $update = false;
    public function user()
    {
        return $this->belongsTo('UserModel', 'user_id');
    }
}