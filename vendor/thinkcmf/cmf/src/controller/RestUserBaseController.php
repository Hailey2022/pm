<?php
namespace cmf\controller;
class RestUserBaseController extends RestBaseController
{
    public function initialize()
    {
        if (empty($this->user)) {
            $this->error(['code' => 10001, 'msg' => '登录已失效!']);
        }
    }
}