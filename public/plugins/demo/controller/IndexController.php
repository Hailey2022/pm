<?php
namespace plugins\demo\controller;
use app\user\model\UserModel;
use cmf\controller\PluginBaseController;
class IndexController extends PluginBaseController
{
    public function index()
    {
        $users = UserModel::limit(0, 5)->select();
        $this->assign('users', $users);
        return $this->fetch('/index');
    }
}
