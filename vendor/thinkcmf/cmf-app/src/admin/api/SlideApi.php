<?php

namespace app\admin\api;

use app\admin\model\SlideModel;
use think\db\Query;

class SlideApi
{
    
    public function index($param = [])
    {
        $slideModel = new SlideModel();

        //返回的数据必须是数据集或数组,item里必须包括id,name,如果想表示层级关系请加上 parent_id
        return $slideModel
            ->where(function (Query $query) use ($param) {
                if (!empty($param['keyword'])) {
                    $query->where('name', 'like', "%{$param['keyword']}%");
                }
            })->select();
    }

}