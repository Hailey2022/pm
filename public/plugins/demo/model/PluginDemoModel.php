<?php
namespace plugins\demo\model;
use think\Model;
class PluginDemoModel extends Model
{
    //自定义方法
    public function test()
    {
        echo 'hello';
    }
}
