<?php
namespace api\home\controller;
use cmf\controller\RestBaseController;
class IndexController extends RestBaseController
{
    public function index()
    {
        $this->success("恭喜您,API访问成功!", [
            'version' => '1.1.0',
            'doc'     => 'http://www.thinkcmf.com/cmf5api.html'
        ]);
    }
}
