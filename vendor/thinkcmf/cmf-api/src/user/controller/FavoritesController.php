<?php
namespace api\user\controller;
use api\user\model\UserFavoriteModel;
use api\user\service\UserFavoriteService;
use cmf\controller\RestBaseController;
use think\Validate;
class FavoritesController extends RestBaseController
{
    public function getFavorites()
    {
        $userId = $this->getUserId();
        $param            = $this->request->param();
        $param['user_id'] = $userId;
        $userFavoriteModel = new UserFavoriteService();
        $favoriteData      = $userFavoriteModel->favorites($param);
        if (empty($this->apiVersion) || $this->apiVersion == '1.0.0') {
            $response = $favoriteData;
        } else {
            $response = ['list' => $favoriteData,];
        }
        if ($favoriteData->isEmpty()) {
            $this->error('您没有收藏的数据！');
        }
        $this->success('请求成功', $response);
    }
    public function setFavorites()
    {
        $data   = $this->request->param();
        $result = $this->validate($data, 'UserFavorite');
        if (true !== $result) {
            $this->error($result);
        }
        $userFavoriteModel = new UserFavoriteModel();
        $count             = $userFavoriteModel
            ->where(['user_id' => $this->getUserId(), 'object_id' => $data['object_id']])
            ->where('table_name', $data['table_name'])
            ->count();
        if ($count > 0) {
            $this->error('已收藏', ['code' => 1]);
        }
        $data['user_id'] = $this->getUserId();
        $favoriteId      = $userFavoriteModel->addFavorite($data);
        if ($favoriteId) {
            $this->success('收藏成功', ['id' => $userFavoriteModel->id]);
        } else {
            $this->error('收藏失败');
        }
    }
    public function unsetFavorites()
    {
        $id     = $this->request->param('id', 0, 'intval');
        $userId = $this->getUserId();
        $userFavoriteModel = new UserFavoriteModel();
        $count             = $userFavoriteModel->where(['id' => $id, 'user_id' => $userId])->count();
        if ($count == 0) {
            $this->error('收藏不存在,无法取消');
        }
        $userFavoriteModel->where(['id' => $id])->delete();
        $this->success('取消成功');
    }
    public function hasFavorite()
    {
        $input = $this->request->param();
        $validate = new Validate([
            'object_id'  => 'require',
            'table_name' => 'require'
        ], [
            'object_id.require'  => '请输出内容ID',
            'table_name.require' => '请输出内容ID所在表名不带前缀'
        ]);
        if (!$validate->check($input)) {
            $this->error($validate->getError());
        }
        $userId = $this->userId;
        if (empty($this->userId)) {
            $this->error('用户登录');
        }
        $userFavoriteModel = new UserFavoriteModel();
        $findFavorite      = $userFavoriteModel->where([
            'table_name' => $input['table_name'],
            'user_id'    => $userId,
            'object_id'  => intval($input['object_id'])
        ])->find();
        if ($findFavorite) {
            $this->success('success', $findFavorite);
        } else {
            $this->error('用户未收藏');
        }
    }
}
