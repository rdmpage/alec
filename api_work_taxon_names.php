<?php

// for a work find all the names that this work is the source for 

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/wikidata_api.php');


$id = 'Q56886408';
$id = 'Q93875424';

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
		schema:name "Taxonomic names";
		schema:dataFeedElement ?item .

		?item 
			rdf:type schema:DataFeedItem;
			rdf:type ?item_type;
			schema:name ?name;

	}
	WHERE
	{
	VALUES ?work { wd:' . $id . ' }
	VALUES ?item_type { wd:Q16521 }
	
	SERVICE wdsubgraph:wikidata_main
	{
		{
			# reference for taxon name
			?provenance pr:P248 ?work . 
			?statement prov:wasDerivedFrom ?provenance .
		
			?item p:P225 ?statement . 
		}
		UNION
		{
		  # publication in which this taxon name was established
		  ?item wdt:P5326 ?work . 
		}  
		
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
