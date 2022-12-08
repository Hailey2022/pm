<?php

return [
    'fetch_upload_view'           => [
        "type"        => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name"        => '获取上传界面', 
        "description" => "获取上传界面", //钩子描述
        "once"        => 1 
    ],
    'user_admin_index_view'       => [
        "type"        => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name"        => '用户管理本站用户列表界面', 
        "description" => "用户管理本站用户列表界面", //钩子描述
        "once"        => 1 
    ],
    'user_admin_asset_index_view' => [
        "type"        => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name"        => '资源管理列表界面', 
        "description" => "资源管理列表界面", //钩子描述
        "once"        => 1 
    ],
    'user_admin_oauth_index_view' => [
        "type"        => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name"        => '用户管理第三方用户列表界面', 
        "description" => "用户管理第三方用户列表界面", //钩子描述
        "once"        => 1 
    ],
];