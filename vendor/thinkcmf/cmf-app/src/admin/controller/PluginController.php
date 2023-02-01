<?php

namespace app\admin\controller;

use app\admin\logic\PluginLogic;
use cmf\controller\AdminBaseController;
use app\admin\model\PluginModel;
use app\admin\model\HookPluginModel;
use think\facade\Cache;
use think\Validate;


class PluginController extends AdminBaseController
{

    protected $pluginModel;


    public function index()
    {
        $pluginModel = new PluginModel();
        $plugins = $pluginModel->getList();
        $this->assign("plugins", $plugins);
        return $this->fetch();
    }


    public function toggle()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');

            $pluginModel = PluginModel::find($id);

            if (empty($pluginModel)) {
                $this->error('插件不存在！');
            }

            $status = 1;
            $successMessage = "启用成功！";

            if ($this->request->param('disable')) {
                $status = 0;
                $successMessage = "禁用成功！";
            }

            $pluginModel->startTrans();

            try {
                $pluginModel->save(['status' => $status]);

                $hookPluginModel = new HookPluginModel();

                $hookPluginModel->where(['plugin' => $pluginModel->name])->update(['status' => $status]);

                $pluginModel->commit();

            } catch (\Exception $e) {

                $pluginModel->rollback();

                $this->error('操作失败！');

            }

            Cache::clear('init_hook_plugins');

            $this->success($successMessage);
        }
    }


    public function setting()
    {
        $id = $this->request->param('id', 0, 'intval');

        $pluginModel = new PluginModel();
        $plugin = $pluginModel->find($id);

        if (empty($plugin)) {
            $this->error('插件未安装!');
        }

        $plugin = $plugin->toArray();

        $pluginClass = cmf_get_plugin_class($plugin['name']);
        if (!class_exists($pluginClass)) {
            $this->error('插件不存在!');
        }

        $pluginObj = new $pluginClass;
        //$plugin['plugin_path']   = $pluginObj->plugin_path;
        //$plugin['custom_config'] = $pluginObj->custom_config;
        $pluginConfigInDb = $plugin['config'];
        $plugin['config'] = include $pluginObj->getConfigFilePath();

        if ($pluginConfigInDb) {
            $pluginConfigInDb = json_decode($pluginConfigInDb, true);
            foreach ($plugin['config'] as $key => $value) {
                if ($value['type'] != 'group') {
                    if (isset($pluginConfigInDb[$key])) {
                        $plugin['config'][$key]['value'] = $pluginConfigInDb[$key];
                    }
                } else {
                    foreach ($value['options'] as $group => $options) {
                        foreach ($options['options'] as $gkey => $value) {
                            if (isset($pluginConfigInDb[$gkey])) {
                                $plugin['config'][$key]['options'][$group]['options'][$gkey]['value'] = $pluginConfigInDb[$gkey];
                            }
                        }
                    }
                }
            }
        }

        $this->assign('data', $plugin);




        $this->assign('id', $id);
        return $this->fetch();

    }


    public function settingPost()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');

            $pluginModel = new PluginModel();
            $plugin = $pluginModel->find($id)->toArray();

            if (!$plugin) {
                $this->error('插件未安装!');
            }

            $pluginClass = cmf_get_plugin_class($plugin['name']);
            if (!class_exists($pluginClass)) {
                $this->error('插件不存在!');
            }

            $pluginObj = new $pluginClass;
            //$plugin['plugin_path']   = $pluginObj->plugin_path;
            //$plugin['custom_config'] = $pluginObj->custom_config;
            $pluginConfigInDb = $plugin['config'];
            $plugin['config'] = include $pluginObj->getConfigFilePath();

            $rules = [];
            $messages = [];

            foreach ($plugin['config'] as $key => $value) {
                if ($value['type'] != 'group') {
                    if (isset($value['rule'])) {
                        $rules[$key] = $this->_parseRules($value['rule']);
                    }

                    if (isset($value['message'])) {
                        foreach ($value['message'] as $rule => $msg) {
                            $messages[$key . '.' . $rule] = $msg;
                        }
                    }

                } else {
                    foreach ($value['options'] as $group => $options) {
                        foreach ($options['options'] as $gkey => $value) {
                            if (isset($value['rule'])) {
                                $rules[$gkey] = $this->_parseRules($value['rule']);
                            }

                            if (isset($value['message'])) {
                                foreach ($value['message'] as $rule => $msg) {
                                    $messages[$gkey . '.' . $rule] = $msg;
                                }
                            }
                        }
                    }
                }
            }

            $config = $this->request->param('config/a');

            $validate = new Validate($rules, $messages);
            $result = $validate->check($config);
            if ($result !== true) {
                $this->error($validate->getError());
            }

            $pluginModel = PluginModel::where('id', $id)->find();
            $pluginModel->save(['config' => json_encode($config)]);
            $this->success('保存成功', '');
        }
    }


    private function _parseRules($rules)
    {
        $newRules = [];

        $simpleRules = [
            'require',
            'number',
            'integer',
            'float',
            'boolean',
            'email',
            'array',
            'accepted',
            'date',
            'alpha',
            'alphaNum',
            'alphaDash',
            'activeUrl',
            'url',
            'ip'
        ];
        foreach ($rules as $key => $rule) {
            if (in_array($key, $simpleRules) && $rule) {
                array_push($newRules, $key);
            }
        }

        return $newRules;
    }


    public function install()
    {
        if ($this->request->isPost()) {
            $pluginName = $this->request->param('name', '', 'trim');
            $result = PluginLogic::install($pluginName);

            if ($result !== true) {
                $this->error($result);
            }

            $this->success('安装成功!');
        }
    }


    public function update()
    {
        if ($this->request->isPost()) {
            $pluginName = $this->request->param('name', '', 'trim');
            $result = PluginLogic::update($pluginName);

            if ($result !== true) {
                $this->error($result);
            }
            $this->success('更新成功!');
        }
    }


    public function uninstall()
    {
        if ($this->request->isPost()) {
            $pluginModel = new PluginModel();
            $id = $this->request->param('id', 0, 'intval');

            $result = $pluginModel->uninstall($id);

            if ($result !== true) {
                $this->error('卸载失败!');
            }

            Cache::clear('init_hook_plugins');
            Cache::clear('admin_menus');

            $this->success('卸载成功!');
        }
    }


}