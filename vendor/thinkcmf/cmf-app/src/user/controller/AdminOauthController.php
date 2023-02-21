<?php
namespace app\user\controller;
use app\user\model\ThirdPartyUserModel;
use cmf\controller\AdminBaseController;
class AdminOauthController extends AdminBaseController
{
    public function index()
    {
        $content = hook_one('user_admin_oauth_index_view');
        if (!empty($content)) {
            return $content;
        }
        $lists = ThirdPartyUserModel::field('a.*,u.user_nickname,u.sex,u.avatar')
            ->alias('a')
            ->join('user u', 'a.user_id = u.id')
            ->where("status", 1)
            ->order("create_time DESC")
            ->paginate(10);
        $page = $lists->render();
        $this->assign('lists', $lists);
        $this->assign('page', $page);
        return $this->fetch();
    }
    public function delete()
    {
        if ($this->request->isPost()) {
            $id = input('param.id', 0, 'intval');
            if (empty($id)) {
                $this->error('非法数据！');
            }
            ThirdPartyUserModel::where("id", $id)->delete();
            $this->success("删除成功！", url('AdminOauth/index'));
        }
    }
}