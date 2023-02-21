<?php
namespace mindplay\test\lib;
use mindplay\test\lib\ResultPrinter\ResultPrinter;
abstract class xTest
{
    private $result;
    private $testRunner;
    private $resultPrinter;
    private $expectedException = null;
    private $expectedExceptionMessage = '';
    private $expectedExceptionCode;
    public function setResultPrinter(ResultPrinter $resultPrinter)
    {
        $this->resultPrinter = $resultPrinter;
    }
    public function run(xTestRunner $testRunner)
    {
        $this->testRunner = $testRunner;
        $this->resultPrinter->testHeader($this);
        $reflection = new \ReflectionClass(get_class($this));
        $methods = $reflection->getMethods();
        $passed = $count = 0;
        if (method_exists($this, 'init')) {
            try {
                $this->init();
            } catch (\Exception $exception) {
                echo '<tr style="color:white; background:red;"><td>init() failed</td><td><pre>' . $exception . '</pre></td></tr></table>';
                return false;
            }
        }
        foreach ($methods as $method) {
            if (substr($method->name, 0, 4) == 'test') {
                $this->result = null;
                $test = $method->name;
                $name = substr($test, 4);
                if (count($_GET) && isset($_GET[$name]) && $_GET[$name] !== '') {
                    continue;
                }
                $this->testRunner->startCoverageCollector($test);
                if (method_exists($this, 'setup')) {
                    $this->setup();
                }
                $exception = null;
                try {
                    $this->$test();
                } catch (\Exception $exception) {
                }
                try {
                    $this->assertException($exception);
                } catch (xTestException $subException) {
                }
                $count++;
                if ($this->result === true) {
                    $passed++;
                }
                if (method_exists($this, 'teardown')) {
                    $this->teardown();
                }
                $this->setExpectedException(null, '', null);
                $this->testRunner->stopCoverageCollector();
                $this->resultPrinter->testCaseResult($method, $this->getResultColor(), $this->getResultMessage());
            }
        }
        $this->resultPrinter->testFooter($this, $count, $passed);
        return $passed == $count;
    }
    private function assertException(\Exception $e = null)
    {
        if (!is_string($this->expectedException)) {
            if ($e && !(($e instanceof xTestException) && $e->getCode() == xTestException::FAIL)) {
                $this->result = (string)$e;
            }
            return;
        }
        $this->check(
            $e instanceof \Exception,
            'Exception of "' . $this->expectedException . '" class was not thrown'
        );
        $this->check(
            get_class($e) == $this->expectedException,
            'Exception with "' . get_class($e) . '" class thrown instead of "' . $this->expectedException . '"'
        );
        if (is_string($this->expectedExceptionMessage) && !empty($this->expectedExceptionMessage)) {
            $this->check(
                $e->getMessage() == $this->expectedExceptionMessage,
                'Exception with "' . $e->getMessage() . '" message thrown instead of "' . $this->expectedExceptionMessage . '"'
            );
        }
        if ($this->expectedExceptionCode !== null) {
            $this->check(
                $e->getCode() == $this->expectedExceptionCode,
                'Exception with "' . $e->getCode() . '" code thrown instead of "' . $this->expectedExceptionCode . '"'
            );
        }
        $this->pass();
    }
    private function getResultColor()
    {
        if ($this->result !== true) {
            $color = 'red';
        } elseif ($this->result === null) {
            $color = 'blue';
        } else {
            $color = 'green';
        }
        return $color;
    }
    private function getResultMessage()
    {
        if ($this->result === true) {
            $result = 'PASS';
        } elseif ($this->result === null) {
            $result = 'FAIL: Incomplete Test';
        } else {
            $result = 'FAIL' . (is_string($this->result) ? ': ' . $this->result : '');
        }
        return $result;
    }
    protected function check($pass, $result = false)
    {
        if ($pass) {
            $this->pass();
        } else {
            $this->fail($result);
        }
    }
    protected function pass()
    {
        if ($this->result === null) {
            $this->result = true;
        }
    }
    protected function fail($result = false)
    {
        $this->result = $result;
        throw new xTestException();
    }
    protected function eq($a, $b, $fail = false)
    {
        if ($a === $b) {
            $this->pass();
        } else {
            $this->fail($fail === false ? var_export($a, true) . ' !== ' . var_export($b, true) : $fail);
        }
    }
    public function setExpectedException($exceptionName, $exceptionMessage = '', $exceptionCode = null)
    {
        $this->expectedException = $exceptionName;
        $this->expectedExceptionMessage = $exceptionMessage;
        $this->expectedExceptionCode = $exceptionCode;
    }
}
