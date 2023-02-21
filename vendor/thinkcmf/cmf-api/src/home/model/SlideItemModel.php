<?php
namespace api\home\model;
use think\Model;
class SlideItemModel extends Model
{
    protected $name = 'slide_item';
    protected $type = [
        'more' => 'array',
    ];
    protected function base($query)
    {
        $query->where('status', 1);
    }
    public function getImageAttr($value)
    {
        return cmf_get_image_url($value);
    }
}
