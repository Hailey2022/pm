<?php
namespace app\user\controller;
use app\user\model\UserModel;
use cmf\controller\HomeBaseController;
class IndexController extends HomeBaseController
{
    public function index()
    {
        $id = $this->request->param("id", 0, "intval");
        $userModel = new UserModel();
        $user = $userModel->where('id', $id)->find();
        if (empty($user)) {
            $this->error("查无此人！");
        }
        $this->assign($user->toArray());
        $this->assign('user', $user);
        return $this->fetch(":index");
    }
    function isLogin()
    {
        if (cmf_is_user_login()) {
            $this->success("用户已登录", null, ['user' => cmf_get_current_user()]);
        } else {
            $this->error("此用户未登录!");
        }
    }
    public function logout()
    {
        session("user", null); //只有前台用户退出
        return redirect($this->request->root() . "/");
    }
}