<?php










namespace think\debug;

use think\Container;
use think\Db;
use think\Response;


class Html
{
    protected $config = [
        'file' => '',
        'tabs' => ['base' => '基本', 'file' => '文件', 'info' => '流程', 'notice|error' => '错误', 'sql' => 'SQL', 'debug|log' => '调试'],
    ];

    
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    
    public function output(Response $response, array $log = [])
    {
        $request     = Container::get('request');
        $contentType = $response->getHeader('Content-Type');
        $accept      = $request->header('accept');
        if (strpos($accept, 'application/json') === 0 || $request->isAjax()) {
            return false;
        } elseif (!empty($contentType) && strpos($contentType, 'html') === false) {
            return false;
        }
        
        $runtime = number_format(microtime(true) - Container::get('app')->getBeginTime(), 10, '.', '');
        $reqs    = $runtime > 0 ? number_format(1 / $runtime, 2) : '∞';
        $mem     = number_format((memory_get_usage() - Container::get('app')->getBeginMem()) / 1024, 2);

        
        if ($request->host()) {
            $uri = $request->protocol() . ' ' . $request->method() . ' : ' . $request->url(true);
        } else {
            $uri = 'cmd:' . implode(' ', $_SERVER['argv']);
        }
        $base = [
            '请求信息' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . ' ' . $uri,
            '运行时间' => number_format($runtime, 6) . 's [ 吞吐率：' . $reqs . 'req/s ] 内存消耗：' . $mem . 'kb 文件加载：' . count(get_included_files()),
            '查询信息' => Db::$queryTimes . ' queries ' . Db::$executeTimes . ' writes ',
            '缓存信息' => Container::get('cache')->getReadTimes() . ' reads,' . Container::get('cache')->getWriteTimes() . ' writes',
        ];

        if (session_id()) {
            $base['会话信息'] = 'SESSION_ID=' . session_id();
        }

        $info = Container::get('debug')->getFile(true);

        
        $trace = [];
        foreach ($this->config['tabs'] as $name => $title) {
            $name = strtolower($name);
            switch ($name) {
                case 'base': 
                    $trace[$title] = $base;
                    break;
                case 'file': 
                    $trace[$title] = $info;
                    break;
                default: 
                    if (strpos($name, '|')) {
                        
                        $names  = explode('|', $name);
                        $result = [];
                        foreach ($names as $name) {
                            $result = array_merge($result, isset($log[$name]) ? $log[$name] : []);
                        }
                        $trace[$title] = $result;
                    } else {
                        $trace[$title] = isset($log[$name]) ? $log[$name] : '';
                    }
            }
        }
        
        ob_start();
        include $this->config['file'];
        return ob_get_clean();
    }

}
