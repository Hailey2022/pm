<?php










namespace think\exception;

use think\Response;

class HttpResponseException extends \RuntimeException
{
    
    protected $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }

}
