<?php
namespace think\console\command\optimize;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Container;
class Route extends Command
{
    protected function configure()
    {
        $this->setName('optimize:route')
            ->setDescription('Build route cache.');
    }
    protected function execute(Input $input, Output $output)
    {
        $filename = Container::get('app')->getRuntimePath() . 'route.php';
        if (is_file($filename)) {
            unlink($filename);
        }
        file_put_contents($filename, $this->buildRouteCache());
        $output->writeln('<info>Succeed!</info>');
    }
    protected function buildRouteCache()
    {
        Container::get('route')->setName([]);
        Container::get('route')->setTestMode(true);
        $path = Container::get('app')->getRoutePath();
        $files = is_dir($path) ? scandir($path) : [];
        foreach ($files as $file) {
            if (strpos($file, '.php')) {
                $filename = $path . DIRECTORY_SEPARATOR . $file;
                $rules = include $filename;
                if (is_array($rules)) {
                    Container::get('route')->import($rules);
                }
            }
        }
        if (Container::get('config')->get('route_annotation')) {
            $suffix = Container::get('config')->get('controller_suffix') || Container::get('config')->get('class_suffix');
            include Container::get('build')->buildRoute($suffix);
        }
        $content = '<?php ' . PHP_EOL . 'return ';
        $content .= var_export(Container::get('route')->getName(), true) . ';';
        return $content;
    }
}
