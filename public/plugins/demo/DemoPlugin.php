<?php








namespace plugins\demo;


use cmf\lib\Plugin;


class DemoPlugin extends Plugin
{
    public $info = [
        'name'        => 'Demo', 
        'title'       => '插件演示',
        'description' => '插件演示',
        'status'      => 1,
        'author'      => 'ThinkCMF',
        'version'     => '1.0',
        'demo_url'    => 'http://demo.thinkcmf.com',
        'author_url'  => 'http://www.thinkcmf.com',
    ];

    public $hasAdmin = 1; //插件是否有后台管理界面

    
    public function install()
    {
        return true; //安装成功返回true，失败false
    }

    
    public function uninstall()
    {
        return true; //卸载成功返回true，失败false
    }

    //实现的footer_start钩子方法
    public function footerStart($param)
    {
        $config = $this->getConfig();
        $this->assign($config);
        echo $this->fetch('widget');
    }

    public function testFetch()
    {
        $config = $this->getConfig();
        $this->assign($config);
        return $this->fetch('widget');
    }
}
