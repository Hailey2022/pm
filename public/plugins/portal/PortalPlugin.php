<?php







namespace plugins\portal;
use cmf\lib\Plugin;


class PortalPlugin extends Plugin
{

    public $info = [
        'name'        => 'Portal',
        'title'       => '门户应用增强插件',
        'description' => '门户应用增强插件',
        'status'      => 1,
        'author'      => 'ThinkCMF',
        'version'     => '1.0.0',
        'demo_url'    => 'http://demo.thinkcmf.com',
        'author_url'  => 'http://www.thinkcmf.com'
    ];

    public $hasAdmin = 0;//插件是否有后台管理界面

    
    public function install()
    {
        return true;//安装成功返回true，失败false
    }

    
    public function uninstall()
    {
        return true;//卸载成功返回true，失败false
    }


}