<?php
namespace app\demo\controller;
use cmf\controller\HomeBaseController;
class IndexController extends HomeBaseController
{
    public function index()
    {
        return $this->fetch(':index');
    }
    public function block()
    {
        return $this->fetch();
    }
    public function ws()
    {
        return $this->fetch(':ws');
    }
}
