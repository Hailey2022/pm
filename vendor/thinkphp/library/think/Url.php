<?php










namespace think;

class Url
{
    
    protected $config = [];

    
    protected $root;

    
    protected $bindCheck;

    
    protected $app;

    public function __construct(App $app, array $config = [])
    {
        $this->app    = $app;
        $this->config = $config;

        if (is_file($app->getRuntimePath() . 'route.php')) {
            
            $app['route']->setName(include $app->getRuntimePath() . 'route.php');
        }
    }

    
    public function init(array $config = [])
    {
        $this->config = array_merge($this->config, array_change_key_case($config));
    }

    public static function __make(App $app, Config $config)
    {
        return new static($app, $config->pull('app'));
    }

    
    public function build($url = '', $vars = '', $suffix = true, $domain = false)
    {
        
        if (0 === strpos($url, '[') && $pos = strpos($url, ']')) {
            
            $name = substr($url, 1, $pos - 1);
            $url  = 'name' . substr($url, $pos + 1);
        }

        if (false === strpos($url, '://') && 0 !== strpos($url, '/')) {
            $info = parse_url($url);
            $url  = !empty($info['path']) ? $info['path'] : '';

            if (isset($info['fragment'])) {
                
                $anchor = $info['fragment'];

                if (false !== strpos($anchor, '?')) {
                    
                    list($anchor, $info['query']) = explode('?', $anchor, 2);
                }

                if (false !== strpos($anchor, '@')) {
                    
                    list($anchor, $domain) = explode('@', $anchor, 2);
                }
            } elseif (strpos($url, '@') && false === strpos($url, '\\')) {
                
                list($url, $domain) = explode('@', $url, 2);
            }
        }

        
        if (is_string($vars)) {
            
            parse_str($vars, $vars);
        }

        if ($url) {
            $checkName   = isset($name) ? $name : $url . (isset($info['query']) ? '?' . $info['query'] : '');
            $checkDomain = $domain && is_string($domain) ? $domain : null;

            $rule = $this->app['route']->getName($checkName, $checkDomain);

            if (is_null($rule) && isset($info['query'])) {
                $rule = $this->app['route']->getName($url);
                
                parse_str($info['query'], $params);
                $vars = array_merge($params, $vars);
                unset($info['query']);
            }
        }

        if (!empty($rule) && $match = $this->getRuleUrl($rule, $vars, $domain)) {
            
            $url = $match[0];

            if ($domain) {
                $domain = $match[1];
            }

            if (!is_null($match[2])) {
                $suffix = $match[2];
            }
        } elseif (!empty($rule) && isset($name)) {
            throw new \InvalidArgumentException('route name not exists:' . $name);
        } else {
            
            $alias      = $this->app['route']->getAlias();
            $matchAlias = false;

            if ($alias) {
                
                foreach ($alias as $key => $item) {
                    $val = $item->getRoute();

                    if (0 === strpos($url, $val)) {
                        $url        = $key . substr($url, strlen($val));
                        $matchAlias = true;
                        break;
                    }
                }
            }

            if (!$matchAlias) {
                
                $url = $this->parseUrl($url);
            }

            
            if (!$this->bindCheck) {
                $bind = $this->app['route']->getBind($domain && is_string($domain) ? $domain : null);

                if ($bind && 0 === strpos($url, $bind)) {
                    $url = substr($url, strlen($bind) + 1);
                } else {
                    $binds = $this->app['route']->getBind(true);

                    foreach ($binds as $key => $val) {
                        if (is_string($val) && 0 === strpos($url, $val) && substr_count($val, '/') > 1) {
                            $url    = substr($url, strlen($val) + 1);
                            $domain = $key;
                            break;
                        }
                    }
                }
            }

            if (isset($info['query'])) {
                
                parse_str($info['query'], $params);
                $vars = array_merge($params, $vars);
            }
        }

        
        $depr = $this->config['pathinfo_depr'];
        $url  = str_replace('/', $depr, $url);

        
        if ('/' == substr($url, -1) || '' == $url) {
            $suffix = '';
        } else {
            $suffix = $this->parseSuffix($suffix);
        }

        
        $anchor = !empty($anchor) ? '#' . $anchor : '';

        
        if (!empty($vars)) {
            
            if ($this->config['url_common_param']) {
                $vars = http_build_query($vars);
                $url .= $suffix . '?' . $vars . $anchor;
            } else {
                $paramType = $this->config['url_param_type'];

                foreach ($vars as $var => $val) {
                    if ('' !== trim($val)) {
                        if ($paramType) {
                            $url .= $depr . urlencode($val);
                        } else {
                            $url .= $depr . $var . $depr . urlencode($val);
                        }
                    }
                }

                $url .= $suffix . $anchor;
            }
        } else {
            $url .= $suffix . $anchor;
        }

        
        $domain = $this->parseDomain($url, $domain);

        
        $url = $domain . rtrim($this->root ?: $this->app['request']->root(), '/') . '/' . ltrim($url, '/');

        $this->bindCheck = false;

        return $url;
    }

    
    protected function parseUrl($url)
    {
        $request = $this->app['request'];

        if (0 === strpos($url, '/')) {
            
            $url = substr($url, 1);
        } elseif (false !== strpos($url, '\\')) {
            
            $url = ltrim(str_replace('\\', '/', $url), '/');
        } elseif (0 === strpos($url, '@')) {
            
            $url = substr($url, 1);
        } else {
            
            $module     = $request->module();
            $module     = $module ? $module . '/' : '';
            $controller = $request->controller();

            if ('' == $url) {
                $action = $request->action();
            } else {
                $path       = explode('/', $url);
                $action     = array_pop($path);
                $controller = empty($path) ? $controller : array_pop($path);
                $module     = empty($path) ? $module : array_pop($path) . '/';
            }

            if ($this->config['url_convert']) {
                $action     = strtolower($action);
                $controller = Loader::parseName($controller);
            }

            $url = $module . $controller . '/' . $action;
        }

        return $url;
    }

    
    protected function parseDomain(&$url, $domain)
    {
        if (!$domain) {
            return '';
        }

        $rootDomain = $this->app['request']->rootDomain();
        if (true === $domain) {
            
            $domain = $this->config['app_host'] ?: $this->app['request']->host();

            $domains = $this->app['route']->getDomains();

            if ($domains) {
                $route_domain = array_keys($domains);
                foreach ($route_domain as $domain_prefix) {
                    if (0 === strpos($domain_prefix, '*.') && strpos($domain, ltrim($domain_prefix, '*.')) !== false) {
                        foreach ($domains as $key => $rule) {
                            $rule = is_array($rule) ? $rule[0] : $rule;
                            if (is_string($rule) && false === strpos($key, '*') && 0 === strpos($url, $rule)) {
                                $url    = ltrim($url, $rule);
                                $domain = $key;

                                
                                if (!empty($rootDomain)) {
                                    $domain .= $rootDomain;
                                }
                                break;
                            } elseif (false !== strpos($key, '*')) {
                                if (!empty($rootDomain)) {
                                    $domain .= $rootDomain;
                                }

                                break;
                            }
                        }
                    }
                }
            }
        } elseif (0 !== strpos($domain, $rootDomain) && false === strpos($domain, '.')) {
            $domain .= '.' . $rootDomain;
        }

        if (false !== strpos($domain, '://')) {
            $scheme = '';
        } else {
            $scheme = $this->app['request']->isSsl() || $this->config['is_https'] ? 'https://' : 'http://';

        }

        return $scheme . $domain;
    }

    
    protected function parseSuffix($suffix)
    {
        if ($suffix) {
            $suffix = true === $suffix ? $this->config['url_html_suffix'] : $suffix;

            if ($pos = strpos($suffix, '|')) {
                $suffix = substr($suffix, 0, $pos);
            }
        }

        return (empty($suffix) || 0 === strpos($suffix, '.')) ? $suffix : '.' . $suffix;
    }

    
    public function getRuleUrl($rule, &$vars = [], $allowDomain = '')
    {
        $port = $this->app['request']->port();
        foreach ($rule as $item) {
            list($url, $pattern, $domain, $suffix, $method) = $item;

            if (is_string($allowDomain) && $domain != $allowDomain) {
                continue;
            }

            if ($port && !in_array($port, [80, 443])) {
                $domain .= ':' . $port;
            }

            if (empty($pattern)) {
                return [rtrim($url, '?/-'), $domain, $suffix];
            }

            $type = $this->config['url_common_param'];
            $keys = [];

            foreach ($pattern as $key => $val) {
                if (isset($vars[$key])) {
                    $url    = str_replace(['[:' . $key . ']', '<' . $key . '?>', ':' . $key, '<' . $key . '>'], $type ? $vars[$key] : urlencode($vars[$key]), $url);
                    $keys[] = $key;
                    $url    = str_replace(['/?', '-?'], ['/', '-'], $url);
                    $result = [rtrim($url, '?/-'), $domain, $suffix];
                } elseif (2 == $val) {
                    $url    = str_replace(['/[:' . $key . ']', '[:' . $key . ']', '<' . $key . '?>'], '', $url);
                    $url    = str_replace(['/?', '-?'], ['/', '-'], $url);
                    $result = [rtrim($url, '?/-'), $domain, $suffix];
                } else {
                    $result = null;
                    $keys   = [];
                    break;
                }
            }

            $vars = array_diff_key($vars, array_flip($keys));

            if (isset($result)) {
                return $result;
            }
        }

        return false;
    }

    
    public function root($root)
    {
        $this->root = $root;
        $this->app['request']->setRoot($root);
    }

    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['app']);

        return $data;
    }
}
