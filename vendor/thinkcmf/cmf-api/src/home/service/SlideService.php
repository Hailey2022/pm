<?php









namespace api\home\service;

use api\home\model\SlideModel;

class SlideService
{
    
    public function SlideList($map)
    {
        $slideModel = new SlideModel();
        $data       = $slideModel
            ->relation('items')
            ->where('status', 1)
            ->where('delete_time', 0)
            ->where($map)
            ->find();
        return $data;
    }
}