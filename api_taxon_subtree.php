<?php

// Tree rooted on this taxon (for d3 roundtree)

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/lib.php');


$id = 'Q766177';

if (isset($_REQUEST['id']))
{
	$id = $_REQUEST['id'];
}

$name = 'Oryzomys';
$name = 'Sigmodontinae';
$name = 'Rhinolophidae'; // not a tree has subfamily not in MSW
$name = 'Rhinolophus';

// Classification according to
$provenance = 'wd:Q1538807'; // MSW


$query = 'PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>
SELECT ?root_name ?parent_name ?child_name WHERE
{ VALUES ?root_name {"' . $name . '"}
 ?root wdt:P225 ?root_name .
 ?child wdt:P171+ ?root . 
 ?child wdt:P171 ?parent .
 ?child wdt:P225 ?child_name .
 ?parent wdt:P225 ?parent_name .
 }';
 
 $query = 'PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>
SELECT ?root_name ?parent_name ?child_name WHERE
{ 
 VALUES ?root_name {"' . $name . '"}
 ?root_statement ps:P225 ?root_name .
 ?root_statement prov:wasDerivedFrom ?root_provenance .
 ?root_provenance pr:P248 ' . $provenance . ' .
 ?root p:P225 ?root_statement .
 
 ?child wdt:P171+ ?root . 
 
 ?child p:P225 ?child_statement . 
 ?child_statement ps:P225 ?child_name .
 ?child_statement prov:wasDerivedFrom ?child_provenance .
 ?child_provenance pr:P248 ' . $provenance . ' .
 
 
 ?child wdt:P171 ?parent .
  
 ?parent p:P225 ?parent_statement .
 
 ?parent_statement ps:P225 ?parent_name .
 ?parent_statement prov:wasDerivedFrom ?parent_provenance .
 ?parent_provenance pr:P248 ' . $provenance . ' .

 }';



$callback = '';
if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

if ($callback != '')
{
	echo $callback . '(';
}

$url = 'https://query.wikidata.org/bigdata/namespace/wdq/sparql?query=' . urlencode($query);
$json = get($url, 'application/json');

echo $json;

if ($callback != '')
{
	echo ')';
}


?>
