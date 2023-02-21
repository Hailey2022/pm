<?php
return [
    'List/index'    => [
        'name'   => '演示应用-文章列表',
        'vars'   => [
            'id' => [
                'pattern' => '\d+',
                'require' => true
            ]
        ],
        'simple' => true
    ],
    'Page/index'    => [
        'name'   => '演示应用-页面页',
        'vars'   => [
            'id' => [
                'pattern' => '\d+',
                'require' => true
            ]
        ],
        'simple' => true
    ],
    'Article/index' => [
        'name'   => '演示应用-文章页',
        'vars'   => [
            'id'  => [
                'pattern' => '\d+',
                'require' => true
            ],
            'cid' => [
                'pattern' => '\d+',
                'require' => false
            ]
        ],
        'simple' => true
    ],
    'Search/index'  => [
        'name'   => '演示应用-搜索页',
        'vars'   => [
        ],
        'simple' => false
    ],
];
