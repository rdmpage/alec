<?php

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/wikidata_api.php');


$id = 'Q7087177';

if (isset($_REQUEST['id']))
{
	$id = $_REQUEST['id'];
}



$query = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	CONSTRUCT 
	{
		<http://example.rss>
		rdf:type schema:DataFeed;
		schema:name "Taxon names in authored publications";
		schema:dataFeedElement ?item .

		?item 
			rdf:type schema:DataFeedItem;
			rdf:type ?item_type;
			schema:name ?name;
	}
	WHERE
	{
	VALUES ?author { wd:' . $id . ' }
 
 # work authored
 ?work wdt:P50 ?author .
  
 # taxon name referenced by work
 SERVICE wdsubgraph:wikidata_main {
 ?provenance pr:P248 ?work . 
 ?statement prov:wasDerivedFrom ?provenance .
    
 ?item p:P225 ?statement . 
 ?item wdt:P31 ?item_type .
 ?item wdt:P225 ?name .	
 }
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
echo sparql_construct_stream($config['sparql_scholarly_endpoint'], $query);
if ($callback != '')
{
	echo ')';
}


?>
