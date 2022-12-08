<?php









namespace api\user\controller;

use api\user\model\UserScoreLogModel;
use cmf\controller\RestUserBaseController;

class ScoreController extends RestUserBaseController
{

    public function logs()
    {
        $userId            = $this->getUserId();
        $userScoreLogModel = new UserScoreLogModel();

        $logs = $userScoreLogModel->where('user_id', $userId)
            ->where('score', '<>', 0)
            ->order('create_time DESC')
            ->paginate();

        $this->success('请求成功', ['list' => $logs->items()]);
    }

}