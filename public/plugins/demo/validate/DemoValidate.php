<?php
namespace plugins\demo\validate;
use think\Validate;
class DemoValidate extends Validate
{
    protected $rule = [
        'title' => 'require',
    ];
    protected $message = [
        'title.require' => '标题不能为空',
    ];
    protected $scene = [
    ];
}
