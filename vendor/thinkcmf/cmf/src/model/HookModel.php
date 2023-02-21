<?php
namespace cmf\model;
use think\Model;
class HookModel extends Model
{
    protected $name = 'hook';
    public function plugins()
    {
        return $this->belongsToMany('PluginModel', 'hook_plugin', 'plugin', 'hook');
    }
}
