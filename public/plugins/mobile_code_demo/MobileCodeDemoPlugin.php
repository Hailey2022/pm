<?php
namespace plugins\mobile_code_demo;
use cmf\lib\Plugin;
class MobileCodeDemoPlugin extends Plugin
{
    public $info = [
        'name'        => 'MobileCodeDemo',
        'title'       => '手机验证码演示插件',
        'description' => '手机验证码演示插件',
        'status'      => 1,
        'author'      => 'ThinkCMF',
        'version'     => '1.0'
    ];
    public $hasAdmin = 0;//插件是否有后台管理界面
    public function install() //安装方法必须实现
    {
        return true;//安装成功返回true，失败false
    }
    public function uninstall() //卸载方法必须实现
    {
        return true;//卸载成功返回true，失败false
    }
    //实现的send_mobile_verification_code钩子方法
    public function sendMobileVerificationCode($param)
    {
        $mobile        = $param['mobile'];//手机号
        $code          = $param['code'];//验证码
        $config        = $this->getConfig();
        $expire_minute = intval($config['expire_minute']);
        $expire_minute = empty($expire_minute) ? 30 : $expire_minute;
        $expire_time   = time() + $expire_minute * 60;
        $result        = false;
        $result = [
            'error'     => 0,
            'message' => '演示插件,您的验证码是'.$code
        ];
        return $result;
    }
}
