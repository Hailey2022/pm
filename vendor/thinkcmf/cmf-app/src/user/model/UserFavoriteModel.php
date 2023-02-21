<?php
namespace app\user\model;
use think\Model;
class UserFavoriteModel extends Model
{
    protected $name = 'user_favorite';
    public function favorites()
    {
        $userId        = cmf_get_current_user_id();
        $favorites     = UserFavoriteModel::where('user_id', $userId)->order('id desc')->paginate(10);
        $data['page']  = $favorites->render();
        $data['lists'] = $favorites->items();
        return $data;
    }
    public function deleteFavorite($id)
    {
        $userId           = cmf_get_current_user_id();
        $where['id']      = $id;
        $where['user_id'] = $userId;
        $data             = UserFavoriteModel::where($where)->delete();
        return $data;
    }
}