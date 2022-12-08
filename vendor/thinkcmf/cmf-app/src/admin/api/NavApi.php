<?php

namespace app\admin\api;

use app\admin\model\NavModel;
use think\db\Query;

class NavApi
{
    
    public function index($param = [])
    {
        $navModel = new NavModel();

        $result = $navModel
            ->where(function (Query $query) use ($param) {
                if (!empty($param['keyword'])) {
                    $query->where('name', 'like', "%{$param['keyword']}%");
                }
            })->select();

        return $result;
    }

}