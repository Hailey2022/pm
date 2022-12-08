<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<?php
require_once('../phpQuery/phpQuery.php');
//phpQuery::$debug = 2;
phpQuery::plugin('Scripts');


//$doc = phpQuery::newDocumentXML('<article><someMarkupStuff/><p>p</p></article>');
//print $doc['article']->children(':empty')->get(0)->tagName;

//$doc = phpQuery::newDocumentFile('test.html');
//setlocale(LC_ALL, 'pl_PL.UTF-8');
//$string =  strftime('%B %Y', time());
//$doc['p:first']->append($string)->dump();


//$doc = phpQuery::newDocument('<p> p1 <b> b1 </b> <b> b2 </b> </p><p> p2 </p>');
//print $doc['p']->contents()->not('[nodeType=1]');

//print phpQuery::newDocumentFileXML('tmp.xml');


//$doc = phpQuery::newDocumentXML('text<node>node</node>test');
//pq('<p/>', $doc)->insertBefore(pq('node'))->append(pq('node'));
//$doc->contents()->wrap('<p/>');
//$doc['node']->wrapAll('<p/>');
//	->contents()
//	->wrap('<p></p>');
//print $doc;


//$doc = phpQuery::newDocumentXML('<p>123<span/>123</p>');
//$doc->dump();
//$doc->children()->wrapAll('<div/>')->dump();


//$doc = phpQuery::newDocumentXML('<p class="test">123<span/>123</p>');
//$doc['[class^="test"]']->dump();


























//$doc = phpQuery::newDocument('<div class="class1 class2"/><div class="class1"/><div class="class2"/>');
//$doc['div']->filter('.class1, .class2')->dump()->dumpWhois();





//













//$doc = phpQuery::newDocument('<select
//name="toto"><option></option><option value="1">1</option></select><div><input
//type="hidden" name="toto"/></div>');
//print $doc['[name=toto]']->val('1');

//$doc = phpQuery::newDocumentFile('http://www.google.pl/search?hl=en&q=test&btnG=Google+Search');
//print $doc;


//$doc = phpQuery::newDocumentXML('<foo><bar/></foo>');
//$doc['foo']->find('bar')->andSelf()->addClass('test');
//$doc->dump();


//print phpQuery::newDocument('<html><body></body></html>')
//	->find('body')
//	->load('http://localhost/phpinfo.php');






//$doc = '<head><title>SomeTitle</title>
//</head>
//<body bgcolor="#ffffff" text="#000000" topmargin="1" leftmargin="0">blah
//</body>';
//$pq = phpQuery::newDocument($doc);
//echo $pq;

# http://code.google.com/p/phpquery/issues/detail?id=94#makechanges
//$doc = phpQuery::newDocument();
//$test = pq(
//'
//<li>
//	<label>Fichier : </label>
//	<input type="file" name="pjModification_fichier[0]"/>
//	<br/>
//	<label>Titre : </label>
//	<input type="text" name="pjModification_titre[0]" class="pieceJointe_titre"/>
//</li>
//'
//);


//$doc = phpQuery::newDocument('<select name="section"><option
//value="-1">Niveau</option><option value="1">6°</option><option
//value="2">5°</option><option
//value="3">4°</option><option value="4">3°</option></select>');
//$doc = phpQuery::newDocument('<select name="section"><option
//value="-1">Niveau</option><option value="1">6°</option><option
//value="2">5°</option><option
//value="3">4°</option><option value="4">3&deg;</option></select>');
//print $doc['select']->val(3)->end()->script('print_source');
//(16:27:56) jomofcw:        $option_element =
//(16:27:56) jomofcw:         pq('<option/>')
//(16:27:56) jomofcw:          ->attr('value',$section['id'])
//(16:27:56) jomofcw:          ->html($section['libelle'])
//(16:27:56) jomofcw:        ;
//(16:29:27) jomofcw: where $section['libelle'] is from a database UTF-8
//16:30
//(16:30:20) jomofcw: the value of $section['libelle'] is exactly "3&deg;" in database...

# http://code.google.com/p/phpquery/issues/detail?id=98
//$doc = phpQuery::newDocument('<select id="test"><option value="0">a</option><option
//value="10">b</option><option value="20">c</option></select>');
//print $doc['select']->val(0)->end()->script('print_source');


//$doc = phpQuery::newDocumentXML("
//<s:Schema id='RowsetSchema'>


//rs:maydefer='true' rs:writeunknown='true'>

//rs:fixedlength='true'/>


//rs:nullable='true' rs:maydefer='true' rs:writeunknown='true'>




//</s:Schema>");
//foreach($doc['Schema ElementType AttributeType'] as $campo){



//}


//function jsonSuccess($data) {
//	var_dump($data);
//}
//$url = 'http://api.flickr.com/services/feeds/photos_public.gne?tags=cat&tagmode=any&format=json';
//phpQuery::ajaxAllowHost('api.flickr.com');
//phpQuery::getJSON($url, array('jsoncallback' => '?'), 'jsonSuccess');
//var_dump(json_decode($json));
//require_once('../phpQuery/Zend/Json/Decoder.php');
//var_dump(Zend_Json_Decoder::decode($json));

#var_dump(''.phpQuery::newDocumentFile("http://www.chefkoch.de/magazin/artikel/943,0/AEG-Electrolux/Frischer-Saft-aus-dem-Dampfgarer.html"));
















//$doc = phpQuery::newDocumentXML("<node1/><node2/>");
//$doc['node1']->data('foo', 'bar');
//var_dump($doc['node1']->data('foo'));
//$doc['node1']->removeData('foo');
//var_dump($doc['node1']->data('foo'));
//$doc['node1']->data('foo.bar', 'bar');
//var_dump($doc['node1']->data('foo.bar'));
//var_dump(phpQuery::$documents[$doc->getDocumentID()]->data);


//$doc = phpQuery::newDocumentXHTML("<p><br/></p>");
//print $doc;

$doc = phpQuery::newDocument('<div id="content"></div><div id="content"></div>');
//$content_string = str_repeat('a', 99988);
$content_string = str_repeat(str_repeat('a', 350)."\n", 350);
//var_dump(strlen($content_string));
?><pre class='1'><?php
//print $content_string;
?></pre><?php
pq('#content')->php('echo $content_string;');
//pq('#content')->php('echo '.var_export($content_string, true));
$doc->dumpTree();
?><pre class='2'><?php
var_dump($doc->php());
?></pre><?php
eval('?>'.$doc->php()); 