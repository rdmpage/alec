<?php


// metrics on items 


error_reporting(E_ALL);

require_once(dirname(dirname(__FILE__)) . '/config.inc.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');



// set of works to look at

$qids = array(
'Q56886408'
);


// possible queries:
// how many things link to this?
// how many works cite this?
// how many times is it the reference for a statement?



foreach ($qids as $qid)
{
	$count = 0;


	// how many works cite this one?

	$query = 'PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>
SELECT (COUNT(?cites) AS ?metric) WHERE
{ 
  VALUES ?work { wd:' . $qid . '}
  ?cites wdt:P2860 ?work .
 
 }';
 

 
	$url = 'https://query.wikidata.org/bigdata/namespace/wdq/sparql?query=' . urlencode($query);
 	$json = get($url, 'application/json');
 	$obj = json_decode($json);
 	
 	print_r($obj);
  
  	
 	$result = array();
 	
 	// target
	foreach ($obj->results->bindings as $binding)
	{
		if (isset($binding->metric->value))
		{
			$count = $binding->metric->value;			
		}
	}	
	
	echo "count=$count\n";
 
 	//return $count;
 	
}




?>
