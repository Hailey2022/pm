<?php
namespace think\exception;
use think\Exception;
class ErrorException extends Exception
{
    protected $severity;
    public function __construct($severity, $message, $file, $line)
    {
        $this->severity = $severity;
        $this->message  = $message;
        $this->file     = $file;
        $this->line     = $line;
        $this->code     = 0;
    }
    final public function getSeverity()
    {
        return $this->severity;
    }
}
