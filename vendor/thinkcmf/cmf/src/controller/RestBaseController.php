<?php
namespace cmf\controller;
use cmf\model\UserTokenModel;
use think\App;
use think\Container;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
class RestBaseController
{
    //token
    protected $token = '';
    //设备类型
    protected $deviceType = '';
    protected $apiVersion;
    //用户 id
    protected $userId = 0;
    //用户
    protected $user;
    protected $app;
    //用户类型
    protected $userType;
    protected $allowedDeviceTypes = ['mobile', 'android', 'iphone', 'ipad', 'web', 'pc', 'mac', 'wxapp'];
    protected $request;
    protected $failException = false;
    protected $batchValidate = false;
    protected $beforeActionList = [];
    public function __construct(App $app = null)
    {
        $this->app     = $app ?: Container::get('app');
        $this->request = $this->app['request'];
        $this->request->root(cmf_get_root() . '/');
        $this->apiVersion = $this->request->header('XX-Api-Version');
        $this->_initUser();
        $this->initialize();
        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method => $options) {
                is_numeric($method) ?
                    $this->beforeAction($options) :
                    $this->beforeAction($method, $options);
            }
        }
    }
    protected function initialize()
    {
    }
    private function _initUser()
    {
        $token = $this->request->header('Authorization');
        if (empty($token)) {
            $token = $this->request->header('XX-Token');
        }
        $deviceType = $this->request->header('XX-Device-Type');
        if (empty($deviceType)) {
            return;
        }
        if (!in_array($deviceType, $this->allowedDeviceTypes)) {
            return;
        }
        $this->deviceType = $deviceType;
        if (empty($token)) {
            return;
        }
        $this->token = $token;
        $user = UserTokenModel::alias('a')
            ->field('b.*')
            ->where(['token' => $token, 'device_type' => $deviceType])
            ->join('user b', 'a.user_id = b.id')
            ->find();
        if (!empty($user)) {
            $this->user     = $user;
            $this->userId   = $user['id'];
            $this->userType = $user['user_type'];
        }
    }
    protected function beforeAction($method, $options = [])
    {
        if (isset($options['only'])) {
            if (is_string($options['only'])) {
                $options['only'] = explode(',', $options['only']);
            }
            if (!in_array($this->request->action(), $options['only'])) {
                return;
            }
        } elseif (isset($options['except'])) {
            if (is_string($options['except'])) {
                $options['except'] = explode(',', $options['except']);
            }
            if (in_array($this->request->action(), $options['except'])) {
                return;
            }
        }
        call_user_func([$this, $method]);
    }
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;
        return $this;
    }
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate)) {
            $v = $this->app->validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                list($validate, $scene) = explode('.', $validate);
            }
            $v = $this->app->validate($validate);
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }
        if (is_array($message)) {
            $v->message($message);
        }
        if ($callback && is_callable($callback)) {
            call_user_func_array($callback, [$v, &$data]);
        }
        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            } else {
                return $v->getError();
            }
        } else {
            return true;
        }
    }
    protected function validateFailError($data, $validate, $message = [], $callback = null)
    {
        $result = $this->validate($data, $validate, $message);
        if ($result !== true) {
            $this->error($result);
        }
        return $result;
    }
    protected function success($msg = '', $data = '', array $header = [])
    {
        $code   = 1;
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ];
        $type                                   = $this->getResponseType();
        $header['Access-Control-Allow-Origin']  = '*';
        $header['Access-Control-Allow-Headers'] = 'X-Requested-With,Content-Type,XX-Device-Type,XX-Token,Authorization,XX-Api-Version,XX-Wxapp-AppId';
        $header['Access-Control-Allow-Methods'] = 'GET,POST,PATCH,PUT,DELETE,OPTIONS';
        $response                               = Response::create($result, $type)->header($header);
        throw new HttpResponseException($response);
    }
    protected function error($msg = '', $data = '', array $header = [])
    {
        $code = 0;
        if (is_array($msg)) {
            $code = $msg['code'];
            $msg  = $msg['msg'];
        }
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ];
        $type                                   = $this->getResponseType();
        $header['Access-Control-Allow-Origin']  = '*';
        $header['Access-Control-Allow-Headers'] = 'X-Requested-With,Content-Type,XX-Device-Type,XX-Token,Authorization,XX-Api-Version,XX-Wxapp-AppId';
        $header['Access-Control-Allow-Methods'] = 'GET,POST,PATCH,PUT,DELETE,OPTIONS';
        $response                               = Response::create($result, $type)->header($header);
        throw new HttpResponseException($response);
    }
    protected function getResponseType()
    {
        return 'json';
    }
    public function getUserId()
    {
        if (empty($this->userId)) {
            $this->error(['code' => 10001, 'msg' => '用户未登录']);
        }
        return $this->userId;
    }
}