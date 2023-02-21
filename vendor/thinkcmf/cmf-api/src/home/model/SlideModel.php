<?php
namespace api\home\model;
use think\Model;
class SlideModel extends Model
{
    protected $name = 'slide';
    protected function items()
    {
        return $this->hasMany('SlideItemModel')->order('list_order ASC');
    }
}
