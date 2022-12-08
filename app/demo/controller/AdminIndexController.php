<?php


namespace app\demo\controller;

use cmf\controller\AdminBaseController;

class AdminIndexController extends AdminBaseController
{
    public function index()
    {
        return $this->fetch();
    }

    public function ws()
    {
        return $this->fetch(':ws');
    }
}
