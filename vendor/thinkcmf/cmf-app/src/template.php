<?php
return [
    'auto_rule'               => 1,
    'type'                    => 'Think',
    'view_base'               => '',
    'view_path'               => '',
    'view_suffix'             => 'html',
    'view_depr'               => DIRECTORY_SEPARATOR,
    'tpl_begin'               => '{',
    'tpl_end'                 => '}',
    'taglib_begin'            => '<',
    'taglib_end'              => '>',
    'taglib_build_in'         => 'cmf\lib\taglib\Cmf,cx',
    'default_filter'          => '',
    'cmf_theme_path'          => 'themes/',
    'cmf_default_theme'       => 'default',
    'cmf_admin_theme_path'    => 'themes/',
    'cmf_admin_default_theme' => 'admin_simpleboot3',
    'tpl_replace_string'      => [
        '__STATIC__' => '/static',
        '__ROOT__'   => '',
    ]
];