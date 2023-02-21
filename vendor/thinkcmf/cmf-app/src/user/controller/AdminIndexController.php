<?php
namespace app\user\controller;
use app\user\model\UserModel;
use cmf\controller\AdminBaseController;
use think\db\Query;
class AdminIndexController extends AdminBaseController
{
    public function index()
    {
        $content = hook_one('user_admin_index_view');
        if (!empty($content)) {
            return $content;
        }
        $list = UserModel::where(function (Query $query) {
            $data = $this->request->param();
            if (!empty($data['uid'])) {
                $query->where('id', intval($data['uid']));
            }
            if (!empty($data['keyword'])) {
                $keyword = $data['keyword'];
                $query->where('user_login|user_nickname|user_email|mobile', 'like', "%$keyword%");
            }
        })->order("create_time DESC")
            ->paginate(10);
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }
    public function ban()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $result = UserModel::where(["id" => $id, "user_type" => 2])->update(['user_status' => 0]);
            if ($result) {
                $this->success("会员拉黑成功！", "adminIndex/index");
            } else {
                $this->error('会员拉黑失败,会员不存在,或者是管理员！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }
    public function cancelBan()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            UserModel::where(["id" => $id, "user_type" => 2])->update(['user_status' => 1]);
            $this->success("会员启用成功！", '');
        } else {
            $this->error('数据传入失败！');
        }
    }
}
