<?php
namespace mindplay\test\lib\ResultPrinter;
use mindplay\test\lib\xTest;
use mindplay\test\lib\xTestRunner;
abstract class ResultPrinter
{
    public function suiteHeader(xTestRunner $testRunner, $pattern)
    {
    }
    public function suiteFooter(xTestRunner $testRunner)
    {
    }
    public function createCodeCoverageReport(\PHP_CodeCoverage $coverage = null)
    {
    }
    public function testHeader(xTest $test)
    {
    }
    public function testFooter(xTest $test, $total, $passed)
    {
    }
    public function testCaseResult(\ReflectionMethod $testCaseMethod, $resultColor, $resultMessage)
    {
    }
    protected function getTestCaseName(\ReflectionMethod $testCaseMethod, $humanFormat = false)
    {
        $ret = substr($testCaseMethod->name, 4);
        return $humanFormat ? ltrim(preg_replace('/([A-Z])/', ' \1', $ret)) : $ret;
    }
}
