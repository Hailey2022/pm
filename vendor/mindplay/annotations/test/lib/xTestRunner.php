<?php
namespace mindplay\test\lib;
use mindplay\test\lib\ResultPrinter\CliResultPrinter;
use mindplay\test\lib\ResultPrinter\ResultPrinter;
use mindplay\test\lib\ResultPrinter\WebResultPrinter;
class xTestRunner
{
    protected $rootPath;
    protected $coverage;
    protected $resultPrinter;
    protected $hasCoverage;
    public static function createResultPrinter()
    {
        if (PHP_SAPI == 'cli') {
            return new CliResultPrinter(new Colors());
        }
        return new WebResultPrinter();
    }
    public function __construct($rootPath, ResultPrinter $resultPrinter)
    {
        if (!is_dir($rootPath)) {
            throw new \Exception("{$rootPath} is not a directory");
        }
        $this->hasCoverage = version_compare(PHP_VERSION, '8.0.0', '<');
        $this->rootPath = $rootPath;
        $this->resultPrinter = $resultPrinter;
        if ($this->hasCoverage) {
            try {
                $this->coverage = new \PHP_CodeCoverage();
                $this->coverage->filter()->addDirectoryToWhitelist($rootPath);
            } catch (\PHP_CodeCoverage_Exception $e) {
            }
        }
    }
    public function getRootPath()
    {
        return $this->rootPath;
    }
    public function startCoverageCollector($testName)
    {
        if (isset($this->coverage)) {
            $this->coverage->start($testName);
        }
    }
    public function stopCoverageCollector()
    {
        if (isset($this->coverage)) {
            $this->coverage->stop();
        }
    }
    public function run($directory, $suffix)
    {
        $this->resultPrinter->suiteHeader($this, $directory . '/*' . $suffix);
        $passed = true;
        $facade = new \File_Iterator_Facade;
        $old_handler = set_error_handler(array($this, 'handlePHPErrors'));
        foreach ($facade->getFilesAsArray($directory, $suffix) as $path) {
            $test = require($path);
            if (!$test instanceof xTest) {
                throw new \Exception("'{$path}' is not a valid unit test");
            }
            $test->setResultPrinter($this->resultPrinter);
            $passed = $passed && $test->run($this);
        }
        if ($old_handler) {
            set_error_handler($old_handler);
        } else {
            restore_error_handler();
        }
        if ($this->hasCoverage){
            $this->resultPrinter->createCodeCoverageReport($this->coverage);
        }
        $this->resultPrinter->suiteFooter($this);
        return $passed;
    }
    public function handlePHPErrors($errno, $errstr)
    {
        throw new xTestException($errstr, xTestException::PHP_ERROR);
    }
}
