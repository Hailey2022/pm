<?php










namespace think\response;

use think\Response;

class Jump extends Response
{
    protected $contentType = 'text/html';

    
    protected function output($data)
    {
        $data = $this->app['view']->fetch($this->options['jump_template'], $data);
        return $data;
    }
}
