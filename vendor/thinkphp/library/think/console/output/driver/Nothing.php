<?php










namespace think\console\output\driver;

use think\console\Output;

class Nothing
{

    public function __construct(Output $output)
    {
        
    }

    public function write($messages, $newline = false, $options = Output::OUTPUT_NORMAL)
    {
        
    }

    public function renderException(\Exception $e)
    {
        
    }
}
