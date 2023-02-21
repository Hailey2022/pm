<?php
namespace traits\controller;
use think\Container;
use think\exception\HttpResponseException;
use think\Response;
use think\response\Redirect;
trait Jump
{
    protected $app;
    protected function success($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        } elseif ('' !== $url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : Container::get('url')->build($url);
        }
        $result = [
            'code' => 1,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];
        $type = $this->getResponseType();
        if ('html' == strtolower($type)) {
            $type = 'jump';
        }
        $response = Response::create($result, $type)->header($header)->options(['jump_template' => $this->app['config']->get('dispatch_success_tmpl')]);
        throw new HttpResponseException($response);
    }
    protected function error($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        $type = $this->getResponseType();
        if (is_null($url)) {
            $url = $this->app['request']->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ('' !== $url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : $this->app['url']->build($url);
        }
        $result = [
            'code' => 0,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];
        if ('html' == strtolower($type)) {
            $type = 'jump';
        }
        $response = Response::create($result, $type)->header($header)->options(['jump_template' => $this->app['config']->get('dispatch_error_tmpl')]);
        throw new HttpResponseException($response);
    }
    protected function result($data, $code = 0, $msg = '', $type = '', array $header = [])
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => time(),
            'data' => $data,
        ];
        $type     = $type ?: $this->getResponseType();
        $response = Response::create($result, $type)->header($header);
        throw new HttpResponseException($response);
    }
    protected function redirect($url, $params = [], $code = 302, $with = [])
    {
        $response = new Redirect($url);
        if (is_integer($params)) {
            $code   = $params;
            $params = [];
        }
        $response->code($code)->params($params)->with($with);
        throw new HttpResponseException($response);
    }
    protected function getResponseType()
    {
        if (!$this->app) {
            $this->app = Container::get('app');
        }
        $isAjax = $this->app['request']->isAjax();
        $config = $this->app['config'];
        return $isAjax
        ? $config->get('default_ajax_return')
        : $config->get('default_return_type');
    }
}
