<?php

// find type specimens for this name

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/wikidata_api.php');


$id = 'Q66006968';

if (isset($_REQUEST['id']))
{
	$id = $_REQUEST['id'];
}



$query = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX prov: <http://www.w3.org/ns/prov#>
	PREFIX pr: <http://www.wikidata.org/prop/reference/>
	PREFIX wdt: <http://www.wikidata.org/prop/direct/>
	PREFIX wd: <http://www.wikidata.org/entity/>

	CONSTRUCT 
	{
		<http://example.rss>
		rdf:type schema:DataFeed;
		schema:name "Taxa for which this is a type";
		schema:dataFeedElement ?item .

		?item 
			rdf:type schema:DataFeedItem;
			rdf:type ?item_type;
			schema:name ?name;

	}
	WHERE
	{
	
		VALUES ?specimen { wd:' . $id . ' } 
	
		
  # find taxon for which this specimen has a role  
  ?specimen p:P2868 ?statement .
  
  
  ?statement pq:P642 ?item .
  ?item wdt:P225 ?name .

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
echo sparql_construct_stream($config['sparql_endpoint'], $query);
if ($callback != '')
{
	echo ')';
}


?>
