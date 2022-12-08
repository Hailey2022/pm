<?php










return [

    
    'app_name'               => '',
    
    'app_host'               => '',
    
    'app_debug'              => APP_DEBUG,
    
    'app_trace'              => APP_DEBUG,
    
    'app_status'             => '',
    
    'app_multi_module'       => true,
    
    'auto_bind_module'       => false,
    
    'root_namespace'         => ['plugins' => WEB_ROOT . 'plugins/', 'themes' => WEB_ROOT . 'themes/', 'app' => CMF_ROOT . 'app/'],
    
    'default_return_type'    => 'html',
    
    'default_ajax_return'    => 'json',
    
    'default_jsonp_handler'  => 'jsonpReturn',
    
    'var_jsonp_handler'      => 'callback',
    
    'default_timezone'       => 'Asia/Shanghai',
    
    'lang_switch_on'         => false,
    
    'default_filter'         => 'htmlspecialchars',
    
    'default_lang'           => 'zh-cn',
    
    'class_suffix'           => true,
    
    'controller_suffix'      => "Controller",

    
    
    

    
    'default_module'         => 'portal',
    
    'deny_module_list'       => ['common'],
    
    'default_controller'     => 'Index',
    
    'default_action'         => 'index',
    
    'default_validate'       => '',
    
    'empty_module'           => '',
    
    'empty_controller'       => 'Error',
    
    'use_action_prefix'      => false,
    
    'action_suffix'          => '',
    
    'controller_auto_search' => false,

    
    
    

    
    'var_pathinfo'           => 's',
    
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    
    'pathinfo_depr'          => '/',
    
    'https_agent_name'       => '',
    
    'url_html_suffix'        => 'html',
    
    'url_common_param'       => false,
    
    'url_param_type'         => 0,
    
    'url_lazy_route'         => false,
    
    'url_route_must'         => false,
    
    'route_rule_merge'       => false,
    
    'route_complete_match'   => false,
    
    'route_annotation'       => false,
    
    'url_domain_root'        => '',
    
    'url_convert'            => true,
    
    'url_controller_layer'   => 'controller',
    
    'var_method'             => '_method',
    
    'var_ajax'               => '_ajax',
    
    'var_pjax'               => '_pjax',
    
    'request_cache'          => false,
    
    'request_cache_expire'   => null,
    
    'request_cache_except'   => [],

    



    
    
    

    


    
    'error_message'          => '出现致命错误！请联系开发者修复～',
    
    'show_error_msg'         => false,
    
    'exception_handle'       => '',
];


