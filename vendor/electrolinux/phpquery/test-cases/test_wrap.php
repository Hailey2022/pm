<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;
$testName = 'Wrap';
$p = phpQuery::newDocumentFile('test.html')
	->find('p')
		->slice(1, 3);
$p->wrap('<div class="wrapper">');
$result = true;
foreach($p as $node) {
	if (! pq($node)->parent()->is('.wrapper'))
		$result = false;
}
if ($result)
	print "Test '{$testName}' PASSED :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!! ";
$p->dump();
print "\n";
$testName = 'WrapAll';
$testResult = 1;
phpQuery::newDocumentFile('test.html')
	->find('p')
		->slice(1, 3)
			->wrapAll('<div class="wrapper">');
$result = pq('.wrapper');
if ( $result->size() == $testResult )
	print "Test '{$testName}' PASSED :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!! ";
$result->dump();
print "\n";
$testName = 'WrapInner';
$testResult = 3;
phpQuery::newDocumentFile('test.html')
	->find('li:first')
		->wrapInner('<div class="wrapper">');
$result = pq('.wrapper p');
if ( $result->size() == $testResult )
	print "Test '{$testName}' PASSED :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!! ";
print $result->dump();
print "\n";
$testName = 'WrapAllTest';
//$testResult = 3;
//phpQuery::newDocumentFile('test.html')
//	->find('li:first')
//		->wrapInner('<div class="wrapper">');
//$result = pq('.wrapper p');
//if ( $result->size() == $testResult )
//	print "Test '{$testName}' PASSED :)";
//else
//	print "Test '{$testName}' <strong>FAILED</strong> !!! ";
//print $result->dump();
//print "\n";
?>