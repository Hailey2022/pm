<?php
namespace app\admin\api;
use app\admin\model\NavMenuModel;
use think\db\Query;
class NavMenuApi
{
    public function index($param = [])
    {
        $navMenuModel = new NavMenuModel();
        $result = $navMenuModel
            ->where(function (Query $query) use ($param) {
                if (!empty($param['keyword'])) {
                    $query->where('name', 'like', "%{$param['keyword']}%");
                }
                if (!empty($param['id'])) {
                    $query->where('nav_id', intval($param['id']));
                }
            })->select();
        return $result;
    }
}