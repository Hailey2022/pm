<?php







return array (
	'account_sid' => array (
		'title' => 'ACCOUNT SID', 
		'type' => 'text',
		'value' => '',
		'tip' => '主帐号,对应开发者官网主账号下的ACCOUNT SID' //表单的帮助提示
	),
    'auth_token' => array (
        'title' => 'AUTH TOKEN', 
        'type' => 'text',
        'value' => '',
        'tip' => '主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN' //表单的帮助提示
    ),
    'app_id' => array (
        'title' => 'APP ID', 
        'type' => 'text',
        'value' => '',
        'tip' => '应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID' //表单的帮助提示
    ),
    'template_id' => array (
        'title' => '模板ID', 
        'type' => 'text',
        'value' => '',
        'tip' => '模板Id,测试应用和未上线应用使用测试模板请填写1，正式应用上线后填写已申请审核通过的模板ID' //表单的帮助提示
    ),
    'expire_minute' => array (
        'title' => '有效期', 
        'type' => 'text',
        'value' => '30',
        'tip' => '短信验证码过期时间，单位分钟' //表单的帮助提示
    ),
);
					