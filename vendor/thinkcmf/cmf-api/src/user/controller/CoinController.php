<?php
namespace app\user\controller;
use api\user\model\UserScoreLogModel;
use cmf\controller\RestUserBaseController;
class CoinController extends RestUserBaseController
{
    public function logs()
    {
        $userId            = $this->getUserId();
        $userScoreLogModel = new UserScoreLogModel();
        $logs = $userScoreLogModel->where('user_id', $userId)
            ->where('coin', '<>', 0)
            ->order('create_time DESC')->paginate();
        $this->success('请求成功', ['list' => $logs->items()]);
    }
}