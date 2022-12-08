<?php










namespace think\response;

use think\Response;

class Redirect extends Response
{

    protected $options = [];

    
    protected $params = [];

    public function __construct($data = '', $code = 302, array $header = [], array $options = [])
    {
        parent::__construct($data, $code, $header, $options);

        $this->cacheControl('no-cache,must-revalidate');
    }

    
    protected function output($data)
    {
        $this->header['Location'] = $this->getTargetUrl();

        return;
    }

    
    public function with($name, $value = null)
    {
        $session = $this->app['session'];

        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $session->flash($key, $val);
            }
        } else {
            $session->flash($name, $value);
        }

        return $this;
    }

    
    public function getTargetUrl()
    {
        if (strpos($this->data, '://') || (0 === strpos($this->data, '/') && empty($this->params))) {
            return $this->data;
        } else {
            return $this->app['url']->build($this->data, $this->params);
        }
    }

    public function params($params = [])
    {
        $this->params = $params;

        return $this;
    }

    
    public function remember($url = null)
    {
        $this->app['session']->set('redirect_url', $url ?: $this->app['request']->url());

        return $this;
    }

    
    public function restore($url = null)
    {
        $session = $this->app['session'];

        if ($session->has('redirect_url')) {
            $this->data = $session->get('redirect_url');
            $session->delete('redirect_url');
        } elseif ($url) {
            $this->data = $url;
        }

        return $this;
    }
}
