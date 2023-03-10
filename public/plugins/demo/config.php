<?php
return [
    'custom_config' => [
        'title' => '自定义配置处理', 
        'type'  => 'text', 
        'value' => '0', 
        'tip'   => '自定义配置处理', //表单的帮助提示
    ],
    'text'          => [
        'title' => '文本', 
        'type'  => 'text', 
        'value' => 'hello,ThinkCMF!', 
        'tip'   => '这是文本组件的演示', //表单的帮助提示
    ],
    'password'      => [
        'title' => '密码',
        'type'  => 'password',
        'value' => '',
        'tip'   => '这是密码组件',
    ],
    'number'        => [
        'title' => '数字',
        'type'  => 'number',
        'value' => '1.0',
        'tip'   => '这是数字组件的演示',
    ],
    'select'        => [
        'title'   => '下拉列表',
        'type'    => 'select',
        'options' => [//select 和radio,checkbox的子选项
            '1' => 'ThinkCMFX', 
            '2' => 'ThinkCMF',
            '3' => '跟猫玩糗事',
            '4' => '门户应用',
        ],
        'value'   => '1',
        'tip'     => '这是下拉列表组件',
    ],
    'checkbox'      => [
        'title'   => '多选框',
        'type'    => 'checkbox',
        'options' => [
            '1' => 'genmaowan.com',
            '2' => 'www.thinkcmf.com',
        ],
        'value'   => 1,
        'tip'     => '这是多选框组件',
    ],
    'radio'         => [
        'title'   => '单选框',
        'type'    => 'radio',
        'options' => [
            '1' => 'ThinkCMFX',
            '2' => 'ThinkCMF',
        ],
        'value'   => '1',
        'tip'     => '这是单选框组件',
    ],
    'radio2'        => [
        'title'   => '单选框2',
        'type'    => 'radio',
        'options' => [
            '1' => 'ThinkCMFX',
            '2' => 'ThinkCMF',
        ],
        'value'   => '1',
        'tip'     => '这是单选框组件2',
    ],
    'textarea'      => [
        'title' => '多行文本',
        'type'  => 'textarea',
        'value' => '这里是你要填写的内容',
        'tip'   => '这是多行文本组件',
    ],
    'date'          => [
        'title' => '日期',
        'type'  => 'date',
        'value' => '2017-05-20',
        'tip'   => '这是日期组件的演示',
    ],
    'datetime'      => [
        'title' => '时间',
        'type'  => 'datetime',
        'value' => '2017-05-20',
        'tip'   => '这是时间组件的演示',
    ],
    'color'         => [
        'title' => '颜色',
        'type'  => 'color',
        'value' => '#103633',
        'tip'   => '这是颜色组件的演示',
    ],
    'image'         => [
        'title' => '图片',
        'type'  => 'image',
        'value' => '',
        'tip'   => '这是图片组件的演示',
    ],
    'file'          => [
        'title' => '文件',
        'type'  => 'file',
        'value' => '',
        'tip'   => '这是文件组件的演示',
    ],
    'location'      => [
        'title' => '地理坐标',
        'type'  => 'location',
        'value' => '',
        'tip'   => '这是地理坐标组件的演示',
    ],
];
