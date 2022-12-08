<?php

return [
    
    
    
    'app'        => [
        
        'app_name'               => '',
        
        'app_host'               => '',
        
        'app_debug'              => false,
        
        'app_trace'              => false,
        
        'app_status'             => '',
        
        'is_https'               => false,
        
        'auto_bind_module'       => false,
        
        'root_namespace'         => [],
        
        'default_return_type'    => 'html',
        
        'default_ajax_return'    => 'json',
        
        'default_jsonp_handler'  => 'jsonpReturn',
        
        'var_jsonp_handler'      => 'callback',
        
        'default_timezone'       => 'Asia/Shanghai',
        
        'lang_switch_on'         => false,
        
        'default_validate'       => '',
        
        'default_lang'           => 'zh-cn',

        
        
        

        
        'controller_auto_search' => false,
        
        'use_action_prefix'      => false,
        
        'action_suffix'          => '',
        
        'empty_controller'       => 'Error',
        
        'empty_module'           => '',
        
        'default_module'         => 'index',
        
        'app_multi_module'       => true,
        
        'deny_module_list'       => ['common'],
        
        'default_controller'     => 'Index',
        
        'default_action'         => 'index',
        
        'url_convert'            => true,
        
        'url_controller_layer'   => 'controller',
        
        'class_suffix'           => false,
        
        'controller_suffix'      => false,

        
        
        

        
        'default_filter'         => '',
        
        'var_pathinfo'           => 's',
        
        'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
        
        'https_agent_name'       => '',
        
        'http_agent_ip'          => 'HTTP_X_REAL_IP',
        
        'url_html_suffix'        => 'html',
        
        'url_domain_root'        => '',
        
        'var_method'             => '_method',
        
        'var_ajax'               => '_ajax',
        
        'var_pjax'               => '_pjax',
        
        'request_cache'          => false,
        
        'request_cache_expire'   => null,
        
        'request_cache_except'   => [],

        
        
        

        
        'pathinfo_depr'          => '/',
        
        'url_common_param'       => false,
        
        'url_param_type'         => 0,
        
        'url_lazy_route'         => false,
        
        'url_route_must'         => false,
        
        'route_rule_merge'       => false,
        
        'route_complete_match'   => false,
        
        'route_annotation'       => false,
        
        'default_route_pattern'  => '\w+',
        
        'route_check_cache'      => false,
        
        'route_check_cache_key'  => '',
        
        'route_cache_option'     => [],

        
        
        

        
        'dispatch_success_tmpl'  => __DIR__ . '/tpl/dispatch_jump.tpl',
        'dispatch_error_tmpl'    => __DIR__ . '/tpl/dispatch_jump.tpl',
        
        'exception_tmpl'         => __DIR__ . '/tpl/think_exception.tpl',
        
        'error_message'          => '出现一个致命错误！请联系开发者修复～',
        
        'show_error_msg'         => false,
        
        'exception_handle'       => '',
    ],

    
    
    

    'template'   => [
        
        'auto_rule'    => 1,
        
        'type'         => 'Think',
        
        'view_base'    => '',
        
        'view_path'    => '',
        
        'view_suffix'  => 'html',
        
        'view_depr'    => DIRECTORY_SEPARATOR,
        
        'tpl_begin'    => '{',
        
        'tpl_end'      => '}',
        
        'taglib_begin' => '{',
        
        'taglib_end'   => '}',
    ],

    
    
    

    'log'        => [
        
        'type'         => 'File',
        
        //'path'  => LOG_PATH,
        
        'level'        => [],
        
        'record_trace' => false,
        
        'json'         => false,
    ],

    
    
    

    'trace'      => [
        
        'type' => 'Html',
        'file' => __DIR__ . '/tpl/page_trace.tpl',
    ],

    
    
    

    'cache'      => [
        
        'type'   => 'File',
        
        //'path'   => CACHE_PATH,
        
        'prefix' => '',
        
        'expire' => 0,
    ],

    
    
    

    'session'    => [
        'id'             => '',
        
        'var_session_id' => '',
        
        'prefix'         => 'think',
        
        'type'           => '',
        
        'auto_start'     => true,
        'httponly'       => true,
        'secure'         => false,
    ],

    
    
    

    'cookie'     => [
        
        'prefix'    => '',
        
        'expire'    => 0,
        
        'path'      => '/',
        
        'domain'    => '',
        
        'secure'    => false,
        
        'httponly'  => '',
        
        'setcookie' => true,
    ],

    
    
    

    'database'   => [
        
        'type'            => 'mysql',
        
        'dsn'             => '',
        
        'hostname'        => '127.0.0.1',
        
        'database'        => '',
        
        'username'        => 'root',
        
        'password'        => '',
        
        'hostport'        => '',
        
        'params'          => [],
        
        'charset'         => 'utf8',
        
        'prefix'          => '',
        
        'debug'           => false,
        
        'deploy'          => 0,
        
        'rw_separate'     => false,
        
        'master_num'      => 1,
        
        'slave_no'        => '',
        
        'fields_strict'   => true,
        
        'resultset_type'  => 'array',
        
        'auto_timestamp'  => false,
        
        'datetime_format' => 'Y-m-d H:i:s',
        
        'sql_explain'     => false,
        
        'query'           => '\\think\\db\\Query',
    ],

    //分页配置
    'paginate'   => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 15,
    ],

    //控制台配置
    'console'    => [
        'name'      => 'Think Console',
        'version'   => '0.1',
        'user'      => null,
        'auto_path' => '',
    ],

    
    'middleware' => [
        'default_namespace' => 'app\\http\\middleware\\',
    ],
];
