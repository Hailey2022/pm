<?php
namespace think\response;
use think\Response;
class Jsonp extends Response
{
    protected $options = [
        'var_jsonp_handler'     => 'callback',
        'default_jsonp_handler' => 'jsonpReturn',
        'json_encode_param'     => JSON_UNESCAPED_UNICODE,
    ];
    protected $contentType = 'application/javascript';
    protected function output($data)
    {
        try {
            $var_jsonp_handler = $this->app['request']->param($this->options['var_jsonp_handler'], "");
            $handler           = !empty($var_jsonp_handler) ? $var_jsonp_handler : $this->options['default_jsonp_handler'];
            $data = json_encode($data, $this->options['json_encode_param']);
            if (false === $data) {
                throw new \InvalidArgumentException(json_last_error_msg());
            }
            $data = $handler . '(' . $data . ');';
            return $data;
        } catch (\Exception $e) {
            if ($e->getPrevious()) {
                throw $e->getPrevious();
            }
            throw $e;
        }
    }
}
