<?php


/*
Graph showing history of journal
*/


error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/graph.php');


$id = 'Q26714324';

if (isset($_REQUEST['id']))
{
	$id = $_REQUEST['id'];
}

//----------------------------------------------------------------------------------------
function get_edges_to(&$G, $qid, $p)
{
	$query = 'PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>
SELECT * WHERE
{ 
  VALUES ?target { wd:' . $qid . '}
  ?source ' . $p . ' ?target . 
  ?source rdfs:label ?source_label .
  ?target rdfs:label ?target_label .
  
  FILTER(LANG(?source_label) = "en" )
  FILTER(LANG(?target_label ) = "en" )
  
 }';
 
 //echo $query . "\n";
 
 	// target
 	$G->AddNode($qid);
 
	$url = 'https://query.wikidata.org/bigdata/namespace/wdq/sparql?query=' . urlencode($query);
 	$json = get($url, 'application/json');
 	$obj = json_decode($json);
 
 	$result = array();
 	
 	// sources
	foreach ($obj->results->bindings as $binding)
	{
		if (isset($binding->source->value))
		{
			$source_id = str_replace('http://www.wikidata.org/entity/', '', $binding->source->value);
			$source_label = $binding->source_label->value;
		
			$result[] = $source_id;
			
			$G->AddNode($source_id, $source_label);
			
			$G->AddEdge($source_id, $qid); 
		}
	}	
	
	$result = array_unique($result);
 
 	return $result;
}

//----------------------------------------------------------------------------------------
function get_edges_from(&$G, $qid, $p)
{
	$query = 'PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>
SELECT * WHERE
{ 
  VALUES ?source { wd:' . $qid . '}
  ?source ' . $p . ' ?target . 
  ?source rdfs:label ?source_label .
  ?target rdfs:label ?target_label .
  
  FILTER(LANG(?source_label) = "en" )
  FILTER(LANG(?target_label ) = "en" )
  
 }';
 
 	//echo $query . "\n";
 	
 	// source
 	$G->AddNode($qid); 	
 
	$url = 'https://query.wikidata.org/bigdata/namespace/wdq/sparql?query=' . urlencode($query);
 	$json = get($url, 'application/json');
 	$obj = json_decode($json);
  
 	$result = array();
 	
 	// target
	foreach ($obj->results->bindings as $binding)
	{
		if (isset($binding->target->value))
		{
			$target_id = str_replace('http://www.wikidata.org/entity/', '', $binding->target->value);
			$target_label = $binding->target_label->value;
		
			$result[] = $target_id;
			
			$G->AddNode($target_id, $target_label);
			
			$G->AddEdge($qid, $target_id); 
			
		}
	}	
	
	$result = array_unique($result);
 
 	return $result;
}
 
$G = new Graph();

$stack = array();
$done = array();

$stack[] = $id;

while (count($stack) > 0)
{
	$qid = array_pop($stack);
	$done[] = $qid;
	
	$o = get_edges_to($G, $qid, 'wdt:P1366|wdt:P156');
		
	foreach ($o as $s)
	{
		if (!in_array($s, $done))
		{
			$stack[] = $s;
		}
	}
	
	$o = get_edges_from($G, $qid, 'wdt:P1366|wdt:P156');
	
	
	foreach ($o as $t)
	{
		if (!in_array($t, $done))
		{
			$stack[] = $t;
		}
	}	
	
}


$dot = $G->WriteDot();

header("Content-type: text/plain");

echo $dot;



?>
