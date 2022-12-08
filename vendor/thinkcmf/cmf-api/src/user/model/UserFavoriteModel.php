<?php








namespace api\user\model;

use think\Model;


class UserFavoriteModel extends Model
{
    
    protected $name = 'user_favorite';
    
    
    protected $autoWriteTimestamp = true;

    
    protected function unionTable($table_name)
    {
        return $this->hasOne($table_name . 'Model', 'object_id');
    }

    
    public function getThumbnailAttr($value)
    {
        if (!empty($value)) {
            $value = cmf_get_image_url($value);
        }

        return $value;
    }

    
    public function getUrlAttr($value)
    {
        $url = json_decode($value, true);
        if (!empty($url)) {
            $url = url($url['action'], $url['param'], true, true);
        } else {
            $url = '';
        }
        return $url;
    }

    
    public function getFavorite($data)
    {
        if (!is_string($data[0])) {
            foreach ($data as $key => $value) {
                $where[$value['table_name']][] = $value['object_id'];
            }
            foreach ($where as $key => $value) {
                $favoriteData[] = $this->unionTable($key)->select($value);
            }
        } else {
            $favoriteData = $this->unionTable($data['table_name'])->find($data['object_id']);
        }

        return $favoriteData;
    }

    
    public function addFavorite($data)
    {
        //获取收藏内容信息
        $Favorite =$this->save($data);
        return $Favorite;
    }

    
    public function unsetFavorite($id)
    {
        return self::destroy($id); //执行删除
    }
}
