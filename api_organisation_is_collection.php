<?php

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/wikidata_api.php');


$id = 'Q978464';

if (isset($_REQUEST['id']))
{
	$id = $_REQUEST['id'];
}

$query = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX rdfs: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	CONSTRUCT 
	{
		<http://example.rss>
		rdf:type schema:DataFeed;
		schema:name "What things are in this collection?";
		schema:dataFeedElement ?item .

		?item 
			rdf:type schema:DataFeedItem;
			rdf:type ?item_type;
			schema:name ?title;
			schema:image ?image;
	}
	WHERE
{
  # organisation
  VALUES ?organisation { wd:' . $id . ' }
    
	?item wdt:P195 ?organisation .
	?item wdt:P31 ?type .
	
   ?item wdt:P217 ?title .
   
	OPTIONAL {
   		?item wdt:P18 ?image .
  	}  
   
	

 }';
 
 //echo $query;



$callback = '';
if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

if ($callback != '')
{
	echo $callback . '(';
}
echo sparql_construct_stream($config['sparql_endpoint'], $query);
if ($callback != '')
{
	echo ')';
}


?>
