<?php










namespace think\console\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\App;
use think\facade\Build as AppBuild;

class Build extends Command
{
    
    protected function configure()
    {
        $this->setName('build')
            ->setDefinition([
                new Option('config', null, Option::VALUE_OPTIONAL, "build.php path"),
                new Option('module', null, Option::VALUE_OPTIONAL, "module name"),
            ])
            ->setDescription('Build Application Dirs');
    }

    protected function execute(Input $input, Output $output)
    {
        if ($input->hasOption('module')) {
            AppBuild::module($input->getOption('module'));
            $output->writeln("Successed");
            return;
        }

        if ($input->hasOption('config')) {
            $build = include $input->getOption('config');
        } else {
            $build = include App::getAppPath() . 'build.php';
        }

        if (empty($build)) {
            $output->writeln("Build Config Is Empty");
            return;
        }

        AppBuild::run($build);
        $output->writeln("Successed");

    }
}
