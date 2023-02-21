<?php
namespace api\user\model;
use think\facade\Db;
use think\Model;
class CommentModel extends Model
{
    protected $name = 'comment';
    //模型关联方法
    protected $relationFilter = ['user', 'to_user'];
    public function getContentAttr($value)
    {
        return cmf_replace_content_file_url(htmlspecialchars_decode($value));
    }
    public function getMoreAttr($value)
    {
        if (empty($value)) {
            return null;
        }
        $more = json_decode($value, true);
        if (!empty($more['thumbnail'])) {
            $more['thumbnail'] = cmf_get_image_url($more['thumbnail']);
        }
        if (!empty($more['photos'])) {
            foreach ($more['photos'] as $key => $value) {
                $more['photos'][$key]['url'] = cmf_get_image_url($value['url']);
            }
        }
        if (!empty($more['files'])) {
            foreach ($more['files'] as $key => $value) {
                $more['files'][$key]['url'] = cmf_get_image_url($value['url']);
            }
        }
        return $more;
    }
    public function user()
    {
        return $this->belongsTo('UserModel', 'user_id')->field('id,user_nickname');
    }
    public function toUser()
    {
        return $this->belongsTo('UserModel', 'to_user_id')->field('id,user_nickname');
    }
    public static function setComment($data)
    {
        if (!$data) {
            return false;
        }
        if ($obj = self::create($data)) {
            $objectId = intval($data['object_id']);
            try {
                $pk = Db::name($data['table_name'])->getPk();
                Db::name($data['table_name'])->where([$pk => $objectId])->inc('comment_count')->update();
                Db::name($data['table_name'])->where([$pk => $objectId])->update(['last_comment' => time()]);
            } catch (\Exception $e) {
            }
            return $obj->id;
        } else {
            return false;
        }
    }
}
