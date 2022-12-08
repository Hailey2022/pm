<?php











namespace api\user\service;


use api\user\model\UserFavoriteModel;

class UserFavoriteService
{
    
    public function favorites($filter)
    {
        $favoriteModel = new UserFavoriteModel();
        $page          = empty($filter['page']) ? '1' : $filter['page'];
        $result        = $favoriteModel
            ->where('user_id', $filter['user_id'])
            ->page($page)
            ->order('create_time desc')
            ->select();
        return $result;
    }
}