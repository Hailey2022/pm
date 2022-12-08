<?php
namespace app\demo\api;

class PageApi
{
    
    public function nav()
    {
        $return = [
            'rule' => [
                'action' => 'demo/Index/index',
                'param' => [
                ]
            ], //url规则
            'items' => [
                ['id' => 1, 'name' => 'test']
            ] //每个子项item里必须包括id,name,如果想表示层级关系请加上 parent_id
        ];

        return $return;
    }

}