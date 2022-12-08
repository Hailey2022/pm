<?php









namespace app\user\controller;

use app\user\model\CommentModel;
use cmf\controller\UserBaseController;
use app\user\model\UserModel;


class CommentController extends UserBaseController
{
    
    public function index()
    {
        $user = cmf_get_current_user();

        $commentModel = new CommentModel();
        $comments     = $commentModel->where(['user_id' => cmf_get_current_user_id(), 'delete_time' => 0])
            ->order('create_time DESC')->paginate();
        $this->assign($user);
        $this->assign("page", $comments->render());
        $this->assign("comments", $comments);
        return $this->fetch();
    }

    
    public function delete()
    {
        if ($this->request->isPost()) {
            $id     = $this->request->param("id", 0, "intval");
            $delete = new UserModel();
            $data   = $delete->deleteComment($id);
            if ($data) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }
    }
}
