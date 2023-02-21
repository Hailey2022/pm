<?php
namespace think\console\output\driver;
use think\console\Output;
class Buffer
{
    private $buffer = '';
    public function __construct(Output $output)
    {
    }
    public function fetch()
    {
        $content      = $this->buffer;
        $this->buffer = '';
        return $content;
    }
    public function write($messages, $newline = false, $options = Output::OUTPUT_NORMAL)
    {
        $messages = (array) $messages;
        foreach ($messages as $message) {
            $this->buffer .= $message;
        }
        if ($newline) {
            $this->buffer .= "\n";
        }
    }
    public function renderException(\Exception $e)
    {
    }
}
