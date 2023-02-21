<?php
namespace think\console\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\App;
class Version extends Command
{
    protected function configure()
    {
        $this->setName('version')
            ->setDescription('show thinkphp framework version');
    }
    protected function execute(Input $input, Output $output)
    {
        $output->writeln('v' . App::version());
    }
}
