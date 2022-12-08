<?php
require('phpQuery/phpQuery.php');








$doc = phpQuery::newDocument('<div/>');



$doc['div']->append('<ul></ul>');

$doc['div ul'] = '<li>1</li> <li>2</li> <li>3</li>';


$li = null;

$doc['ul > li']
	->addClass('my-new-class')
	->filter(':last')
		->addClass('last-li')

		->toReference($li);



phpQuery::selectDocument($doc);


$ul = pq('ul')->insertAfter('div');



foreach($ul['> li'] as $li) {
	
	$tagName = $li->tagName;
	$childNodes = $li->childNodes;
	
	pq($li)->addClass('my-second-new-class');
}



print phpQuery::getDocument($doc->getDocumentID());

print phpQuery::getDocument(pq('div')->getDocumentID());

print pq('div')->getDocument();

print $doc->htmlOuter();

print $doc;

print $doc['ul'];