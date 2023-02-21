<?php
namespace api\user\service;
use api\user\model\CommentModel;
use think\db\Query;
class CommentService
{
    public function userComments($filter)
    {
        $page    = empty($filter['page']) ? '1' : $filter['page'];
        $comment = new CommentModel();
        $result  = $comment
            ->where('delete_time', 0)
            ->where('status', 1)
            ->where(function (Query $query) use ($filter) {
                if (!empty($filter['user_id'])) {
                    $query->where('user_id', $filter['user_id']);
                }
                if (!empty($filter['object_id'])) {
                    $query->where('object_id', $filter['object_id']);
                }
                if (!empty($filter['table_name'])) {
                    $query->where('table_name', $filter['table_name']);
                }
            })
            ->page($page)
            ->order('create_time desc')
            ->select();
        return $result;
    }
}