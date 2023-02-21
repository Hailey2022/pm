<?php
namespace cmf\controller;
class RestAdminBaseController extends RestBaseController
{
    public function initialize()
    {
        if (empty($this->user)) {
            $this->error(['code' => 10001, 'msg' => '登录已失效!']);
        } elseif ($this->userType != 1) {
            $this->error(['code' => 10001, 'msg' => '登录已失效!']);
        }
    }
}