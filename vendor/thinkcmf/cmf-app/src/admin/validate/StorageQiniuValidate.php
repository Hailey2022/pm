<?php









namespace app\admin\validate;

use think\Validate;

class StorageQiniuValidate extends Validate
{
    protected $rule = [
        'accessKey' => 'require',
        'secretKey' => 'require',
        'domain'    => 'require',
    ];

    protected $message = [
        'accessKey.require' => 'AccessKey不能为空',
        'secretKey.require' => 'secretKey不能为空',
        'domain.require' => '空间域名不能为空',
    ];

}