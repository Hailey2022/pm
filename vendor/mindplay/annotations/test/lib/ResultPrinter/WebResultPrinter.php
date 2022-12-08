<?php
namespace mindplay\test\lib\ResultPrinter;


use mindplay\test\lib\xTest;
use mindplay\test\lib\xTestRunner;

class WebResultPrinter extends ResultPrinter
{

    
    public function suiteHeader(xTestRunner $testRunner, $pattern)
    {
        echo '<html>
                   <head>
                       <title>Unit Tests</title>
                       <style type="text/css">
                           table { border-collapse:collapse; }
                           td, th { text-align:left; padding:2px 6px; border:solid 1px #aaa; }
                       </style>
                   </head>
                   <body>
                       <h2>Unit Tests</h2>';

        echo '<h4>Codebase: ' . $testRunner->getRootPath() . '</h4>';
        echo '<h4>Test Suite: ' . $pattern . '</h4>';
    }

    
    public function suiteFooter(xTestRunner $testRunner)
    {
        echo '</body></html>';
    }

    
    public function createCodeCoverageReport(\PHP_CodeCoverage $coverage = null)
    {
        if (!isset($coverage)) {
            echo '<h3>Code coverage analysis unavailable</h3><p>To enable code coverage, the xdebug php module must be installed and enabled.</p>';

            return;
        }

        $writer = new \PHP_CodeCoverage_Report_HTML;
        $writer->process($coverage, FULL_PATH . '/test/runtime/coverage');

        echo '<a href="runtime/coverage" target="_blank">Code coverage report</a>';
    }

    
    public function testHeader(xTest $test)
    {
        $class = get_class($test);

        echo '<h3>Test Class: ' . htmlspecialchars($class) . '</h3>';
        echo '<table id="' . $class . '-results"><tr><th>Test</th><th>Result</th></tr>';
    }

    
    public function testCaseResult(\ReflectionMethod $testCaseMethod, $resultColor, $resultMessage)
    {
        echo '<tr style="color:white; background:' . $resultColor . '"><td>(' . $testCaseMethod->getStartLine() . ') ';
        echo '<a style="color:white" href="?' . $this->getTestCaseName($testCaseMethod) . '">' . $this->getTestCaseName($testCaseMethod, true) . '</a>';
        echo '</td><td><pre>' . htmlspecialchars($resultMessage) . '</pre></td></tr>';
    }

    
    public function testFooter(xTest $test, $total, $passed)
    {
        echo '<tr style="background-color:gray; color:white"><tr><th>' . $total . ' Tests</th><th>';

        if ($passed == $total) {
            echo 'All Tests Passed' . PHP_EOL;
        } else {
            echo ($total - $passed) . ' Tests Failed' . PHP_EOL;
        }

        echo '</th></tr></table>';

        if ($passed == $total) {
            $class = get_class($test);
            echo '<h4 id="' . $class . '-toggle" style="cursor:pointer" onclick="' . "document.getElementById('{$class}-results').style.display='table'; document.getElementById('{$class}-toggle').style.display='none'; return false;" . '">&raquo; All Tests Passed</h4><script type="text/javascript">document.getElementById("' . $class . '-results").style.display="none";</script>';
        }
    }
}
