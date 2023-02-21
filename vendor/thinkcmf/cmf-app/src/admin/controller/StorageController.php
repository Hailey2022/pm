<?php
namespace app\admin\controller;
use cmf\controller\AdminBaseController;
class StorageController extends AdminBaseController
{
    public function index()
    {
        $storage = cmf_get_option('storage');
        if (empty($storage)) {
            $storage['type']     = 'Local';
            $storage['storages'] = ['Local' => ['name' => '本地']];
        } else {
            if (empty($storage['type'])) {
                $storage['type'] = 'Local';
            }
            if (empty($storage['storages']['Local'])) {
                $storage['storages']['Local'] = ['name' => '本地'];
            }
        }
        $this->assign($storage);
        return $this->fetch();
    }
    public function settingPost()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $storage = cmf_get_option('storage');
            $storage['type'] = $post['type'];
            cmf_set_option('storage', $storage);
            $this->success("设置成功！", '');
        }
    }
}