<?php

namespace app\user\model;

use think\Model;

class CommentModel extends Model
{
    
    protected $name = 'comment';

    
    public function user()
    {
        return $this->belongsTo('UserModel', 'user_id');
    }


    
    public function getContentAttr($value)
    {
        return cmf_replace_content_file_url(htmlspecialchars_decode($value));
    }

    
    public function setContentAttr($value)
    {

        $config = \HTMLPurifier_Config::createDefault();
        if (!file_exists(RUNTIME_PATH . 'HTMLPurifier_DefinitionCache_Serializer')) {
            mkdir(RUNTIME_PATH . 'HTMLPurifier_DefinitionCache_Serializer');
        }

        $config->set('Cache.SerializerPath', RUNTIME_PATH . 'HTMLPurifier_DefinitionCache_Serializer');
        $purifier  = new \HTMLPurifier($config);
        $cleanHtml = $purifier->purify(cmf_replace_content_file_url(htmlspecialchars_decode($value), true));
        return htmlspecialchars($cleanHtml);
    }

}

