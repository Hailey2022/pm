<?php
namespace cmf\controller;
use think\Container;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;
use think\facade\View;
use think\facade\Config;
class BaseController extends Controller
{
    public function __construct()
    {
        $this->app     = Container::get('app');
        $this->request = $this->app['request'];
        if (!cmf_is_installed() && $this->request->module() != 'install') {
            return $this->redirect(cmf_get_root() . '/?s=install');
        }
        $this->_initializeView();
        $this->view = View::init(Config::get('template.'));
        $this->initialize();
        foreach ((array)$this->beforeActionList as $method => $options) {
            is_numeric($method) ?
                $this->beforeAction($options) :
                $this->beforeAction($method, $options);
        }
    }
    protected function _initializeView()
    {
    }
    protected function listOrders($model)
    {
        $modelName = '';
        if (is_object($model)) {
            $modelName = $model->getName();
        } else {
            $modelName = $model;
        }
        $pk  = Db::name($modelName)->getPk(); //获取主键名称
        $ids = $this->request->post("list_orders/a");
        if (!empty($ids)) {
            foreach ($ids as $key => $r) {
                $data['list_order'] = $r;
                Db::name($modelName)->where($pk, $key)->update($data);
            }
        }
        return true;
    }
    protected function validateFailError($data, $validate, $message = [], $callback = null)
    {
        $result = $this->validate($data, $validate, $message);
        if ($result !== true) {
            $this->error($result);
        }
        return $result;
    }
}