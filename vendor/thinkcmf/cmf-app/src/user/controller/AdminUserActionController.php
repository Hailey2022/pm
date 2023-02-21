<?php
namespace app\user\controller;
use app\user\logic\UserActionLogic;
use app\user\model\UserActionModel;
use cmf\controller\AdminBaseController;
class AdminUserActionController extends AdminBaseController
{
    public function index()
    {
        $where   = [];
        $request = input('request.');
        if (!empty($request['uid'])) {
            $where['id'] = intval($request['uid']);
        }
        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];
            $keywordComplex['user_login']    = ['like', "%$keyword%"];
            $keywordComplex['user_nickname'] = ['like', "%$keyword%"];
            $keywordComplex['user_email']    = ['like', "%$keyword%"];
        }
        $actions = UserActionModel::paginate(20);
        $page = $actions->render();
        $this->assign('actions', $actions);
        $this->assign('page', $page);
        return $this->fetch();
    }
    public function edit()
    {
        $id     = $this->request->param('id', 0, 'intval');
        $action = UserActionModel::where('id', $id)->find()->toArray();
        $this->assign($action);
        return $this->fetch();
    }
    public function editPost()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');
            $data = $this->request->param();
            UserActionModel::where('id', $id)
                ->strict(false)
                ->field('score,coin,reward_number,cycle_type,cycle_time')
                ->update($data);
            $this->success('保存成功！');
        }
    }
    public function sync()
    {
        $apps = cmf_scan_dir(APP_PATH . '*', GLOB_ONLYDIR);
        array_push($apps, 'admin', 'user');
        foreach ($apps as $app) {
            UserActionLogic::importUserActions($app);
        }
        return $this->fetch();
    }
}
