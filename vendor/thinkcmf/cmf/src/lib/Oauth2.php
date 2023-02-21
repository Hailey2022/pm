<?php
namespace cmf\lib;
abstract class Oauth2
{
    protected $version = '2.0';
    protected $appKey = '';
    protected $appSecret = '';
    protected $responseType = 'code';
    protected $grantType = 'authorization_code';
    protected $callback = '';
    protected $authorize = '';
    protected $getRequestCodeURL = '';
    protected $getAccessTokenURL = '';
    protected $apiBase = '';
    protected $token = null;
    private function config()
    {
    }
    public function setAppKey(string $appKey)
    {
        $this->appKey = $appKey;
    }
    public function setCallback(string $callback)
    {
        $this->callback = $callback;
    }
    public function setAppSecret(string $appSecret)
    {
        $this->appSecret = $appSecret;
    }
    public function getRequestCodeURL()
    {
        $this->config();
        //Oauth 标准参数
        $params = [
            'client_id'     => $this->appKey,
            'redirect_uri'  => $this->callback,
            'response_type' => $this->responseType,
        ];
        //获取额外参数
        if ($this->authorize) {
            parse_str($this->authorize, $_param);
            if (is_array($_param)) {
                $params = array_merge($params, $_param);
            } else {
                throw new \Exception('AUTHORIZE配置不正确！');
            }
        }
        return $this->getRequestCodeURL . '?' . http_build_query($params);
    }
    public function getAccessToken($code, $extend = null)
    {
        $this->config();
        $params = [
            'client_id'     => $this->appKey,
            'client_secret' => $this->appSecret,
            'grant_type'    => $this->grantType,
            'code'          => $code,
            'redirect_uri'  => $this->callback,
        ];
        $data        = $this->http($this->getAccessTokenURL, $params, 'POST');
        $this->token = $this->parseToken($data, $extend);
        return $this->token;
    }
    protected function param($params, $param)
    {
        if (is_string($param))
            parse_str($param, $param);
        return array_merge($params, $param);
    }
    protected function url($api, $fix = '')
    {
        return $this->apiBase . $api . $fix;
    }
    protected function http($url, $params, $method = 'GET', $header = [], $multi = false)
    {
        $opts = [
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => $header
        ];
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                $params                   = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL]        = $url;
                $opts[CURLOPT_POST]       = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                throw new \Exception('不支持的请求方式！');
        }
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) throw new \Exception('请求发生错误：' . $error);
        return $data;
    }
    abstract protected function call($api, $param = '', $method = 'GET', $multi = false);
    abstract protected function parseToken($result, $extend);
    abstract public function openid();
}