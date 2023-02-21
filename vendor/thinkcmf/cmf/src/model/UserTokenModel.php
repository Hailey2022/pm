<?php
namespace cmf\model;
use think\Db;
use think\Model;
class UserTokenModel extends Model
{
    protected $name = 'user_token';
    protected $autoWriteTimestamp = true;
}
