<?php










namespace think\exception;

class ValidateException extends \RuntimeException
{
    protected $error;

    public function __construct($error, $code = 0)
    {
        $this->error   = $error;
        $this->message = is_array($error) ? implode(PHP_EOL, $error) : $error;
        $this->code    = $code;
    }

    
    public function getError()
    {
        return $this->error;
    }
}
