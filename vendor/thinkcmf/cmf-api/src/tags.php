<?php
return [
    'app_init'     => [
        'cmf\\behavior\\InitHookBehavior',
        'cmf\\behavior\\LangBehavior',
    ],
    'app_begin'    => [
    ],
    'module_init' => [
        'cmf\\behavior\\InitAppHookBehavior',
    ],
    'action_begin' => [],
    'view_filter'  => [],
    'log_write'      => [],
    //日志写入完成
    'log_write_done' => [],
    'app_end'      => [],
    'admin_init'  => [
        'cmf\\behavior\\AdminLangBehavior',
    ],
    'home_init'    => [
        'cmf\\behavior\\HomeLangBehavior',
    ]
];
