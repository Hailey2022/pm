<?php
namespace app\admin\service;
use app\admin\model\LinkModel;
use app\admin\model\SlideItemModel;
use app\admin\model\SlideModel;
class ApiService
{
    public static function links()
    {
        return LinkModel::where('status', 1)->order('list_order ASC')->select();
    }
    public static function slides($slideId)
    {
        $slideCount = SlideModel::where('id', $slideId)->where(['status' => 1, 'delete_time' => 0])->count();
        if ($slideCount == 0) {
            return [];
        }
        $slides = SlideItemModel::where('status', 1)->where('slide_id', $slideId)->order('list_order ASC')->select();
        return $slides;
    }
}