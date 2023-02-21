<?php
namespace think\exception;
use Exception;
use think\console\Output;
use think\Container;
use think\Response;
class Handle
{
    protected $render;
    protected $ignoreReport = [
        '\\think\\exception\\HttpException',
    ];
    public function setRender($render)
    {
        $this->render = $render;
    }
    public function report(Exception $exception)
    {
        if (!$this->isIgnoreReport($exception)) {
            if (Container::get('app')->isDebug()) {
                $data = [
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'message' => $this->getMessage($exception),
                    'code'    => $this->getCode($exception),
                ];
                $log = "[{$data['code']}]{$data['message']}[{$data['file']}:{$data['line']}]";
            } else {
                $data = [
                    'code'    => $this->getCode($exception),
                    'message' => $this->getMessage($exception),
                ];
                $log = "[{$data['code']}]{$data['message']}";
            }
            if (Container::get('app')->config('log.record_trace')) {
                $log .= "\r\n" . $exception->getTraceAsString();
            }
            Container::get('log')->record($log, 'error');
        }
    }
    protected function isIgnoreReport(Exception $exception)
    {
        foreach ($this->ignoreReport as $class) {
            if ($exception instanceof $class) {
                return true;
            }
        }
        return false;
    }
    public function render(Exception $e)
    {
        if ($this->render && $this->render instanceof \Closure) {
            $result = call_user_func_array($this->render, [$e]);
            if ($result) {
                return $result;
            }
        }
        if ($e instanceof HttpException) {
            return $this->renderHttpException($e);
        } else {
            return $this->convertExceptionToResponse($e);
        }
    }
    public function renderForConsole(Output $output, Exception $e)
    {
        if (Container::get('app')->isDebug()) {
            $output->setVerbosity(Output::VERBOSITY_DEBUG);
        }
        $output->renderException($e);
    }
    protected function renderHttpException(HttpException $e)
    {
        $status   = $e->getStatusCode();
        $template = Container::get('app')->config('http_exception_template');
        if (!Container::get('app')->isDebug() && !empty($template[$status])) {
            return Response::create($template[$status], 'view', $status)->assign(['e' => $e]);
        } else {
            return $this->convertExceptionToResponse($e);
        }
    }
    protected function convertExceptionToResponse(Exception $exception)
    {
        if (Container::get('app')->isDebug()) {
            $data = [
                'name'    => get_class($exception),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'message' => $this->getMessage($exception),
                'trace'   => $exception->getTrace(),
                'code'    => $this->getCode($exception),
                'source'  => $this->getSourceCode($exception),
                'datas'   => $this->getExtendData($exception),
                'tables'  => [
                    'GET Data'              => $_GET,
                    'POST Data'             => $_POST,
                    'Files'                 => $_FILES,
                    'Cookies'               => $_COOKIE,
                    'Session'               => isset($_SESSION) ? $_SESSION : [],
                    'Server/Request Data'   => $_SERVER,
                    'Environment Variables' => $_ENV,
                    'ThinkPHP Constants'    => $this->getConst(),
                ],
            ];
        } else {
            $data = [
                'code'    => $this->getCode($exception),
                'message' => $this->getMessage($exception),
            ];
            if (!Container::get('app')->config('show_error_msg')) {
                $data['message'] = Container::get('app')->config('error_message');
            }
        }
        //保留一层
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        $data['echo'] = ob_get_clean();
        ob_start();
        extract($data);
        include Container::get('app')->config('exception_tmpl');
        $content  = ob_get_clean();
        $response = Response::create($content, 'html');
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
            $response->header($exception->getHeaders());
        }
        if (!isset($statusCode)) {
            $statusCode = 500;
        }
        $response->code($statusCode);
        return $response;
    }
    protected function getCode(Exception $exception)
    {
        $code = $exception->getCode();
        if (!$code && $exception instanceof ErrorException) {
            $code = $exception->getSeverity();
        }
        return $code;
    }
    protected function getMessage(Exception $exception)
    {
        $message = $exception->getMessage();
        if (PHP_SAPI == 'cli') {
            return $message;
        }
        $lang = Container::get('lang');
        if (strpos($message, ':')) {
            $name    = strstr($message, ':', true);
            $message = $lang->has($name) ? $lang->get($name) . strstr($message, ':') : $message;
        } elseif (strpos($message, ',')) {
            $name    = strstr($message, ',', true);
            $message = $lang->has($name) ? $lang->get($name) . ':' . substr(strstr($message, ','), 1) : $message;
        } elseif ($lang->has($message)) {
            $message = $lang->get($message);
        }
        return $message;
    }
    protected function getSourceCode(Exception $exception)
    {
        $line  = $exception->getLine();
        $first = ($line - 9 > 0) ? $line - 9 : 1;
        try {
            $contents = file($exception->getFile());
            $source   = [
                'first'  => $first,
                'source' => array_slice($contents, $first - 1, 19),
            ];
        } catch (Exception $e) {
            $source = [];
        }
        return $source;
    }
    protected function getExtendData(Exception $exception)
    {
        $data = [];
        if ($exception instanceof \think\Exception) {
            $data = $exception->getData();
        }
        return $data;
    }
    private static function getConst()
    {
        $const = get_defined_constants(true);
        return isset($const['user']) ? $const['user'] : [];
    }
}
