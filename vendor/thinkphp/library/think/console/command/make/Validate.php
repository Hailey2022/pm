<?php
namespace think\console\command\make;
use think\console\command\Make;
class Validate extends Make
{
    protected $type = "Validate";
    protected function configure()
    {
        parent::configure();
        $this->setName('make:validate')
            ->setDescription('Create a validate class');
    }
    protected function getStub()
    {
        $stubPath = __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR;
        return $stubPath . 'validate.stub';
    }
    protected function getNamespace($appNamespace, $module)
    {
        return parent::getNamespace($appNamespace, $module) . '\validate';
    }
}
