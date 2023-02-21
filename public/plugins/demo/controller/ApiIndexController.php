<?php
namespace plugins\demo\controller;
use cmf\controller\PluginRestBaseController;
class ApiIndexController extends PluginRestBaseController
{
    public function index()
    {
        $this->success('success', ['hello' => 'hello ThinkCMF!']);
    }
}
