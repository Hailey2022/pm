<?php
namespace app\admin\validate;
use think\Validate;
class RoleValidate extends Validate
{
    protected $rule = [
        'name' => 'require',
    ];
    protected $message = [
        'name.require' => '角色名称不能为空',
    ];
    protected $scene = [
        'add' => ['name'],
    ];
}