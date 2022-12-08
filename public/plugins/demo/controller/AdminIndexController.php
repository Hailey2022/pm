<?php

namespace plugins\demo\controller;

use app\user\model\UserModel;
use cmf\controller\PluginAdminBaseController;


class AdminIndexController extends PluginAdminBaseController
{
    protected function initialize()
    {
        parent::initialize();
        $adminId = cmf_get_current_admin_id();
        if (!empty($adminId)) {
            $this->assign('admin_id', $adminId);
        }
    }

    public function index()
    {
        $users = UserModel::limit(0, 5)->select();
        $this->assign('plugin', $this->getPlugin());
        $this->assign('users', $users);
        return $this->fetch('/admin_index');
    }

    public function setting()
    {
        $users = UserModel::limit(0, 5)->select();
        $this->assign('users', $users);
        $this->assign('users', $users);
        return $this->fetch('/admin_index');
    }
}