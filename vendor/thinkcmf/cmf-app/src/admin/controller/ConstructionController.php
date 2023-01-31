<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ConstructionController extends AdminBaseController
{
    public function index()
    {
        return;
    }
    public function showUsers()
    {
        $usernames = Db::name("wechat_user")->column('username');
        $this->assign('usernames', $usernames);
        return $this->fetch();
    }
    public function showRecords()
    {
        $records = Db::name("wechat_user u, pm_wechat_punch_in_location l ,pm_wechat_punch_in_record r")
            ->where('u.uid = r.uid')
            ->where('l.id = r.locationId')
            ->select();
        $this->assign('records', $records);
        return $this->fetch();
    }

    public function showSites()
    {
        $locations = Db::name("wechat_punch_in_location")
            ->select();
        $this->assign('locations', $locations);
        return $this->fetch();
    }
}