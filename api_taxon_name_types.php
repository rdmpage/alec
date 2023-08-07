<?php

// find type specimens for this name

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/wikidata_api.php');


$id = 'Q21276570';

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
		schema:name "Types";
		schema:dataFeedElement ?item .

		?item 
			rdf:type schema:DataFeedItem;
			rdf:type ?item_type;
			schema:name ?name;
			
			
		


		

	}
	WHERE
	{
	
		VALUES ?taxon { wd:' . $id . ' } 
	
		

  # find thing which has a role "of or for" this taxon 
  {
    ?statement pq:P642 ?taxon .
    ?statement ps:P2868 ?item_type .
    ?item p:P2868 ?statement .
  }
  UNION
  {
    ?taxon wdt:P427 ?item .
    ?item wdt:P31 ?item_type .
  }
  
  # ensure ?item is a nomenclatural type
  ?item_type wdt:P279* wd:Q3707858 .
  
  # labels
  ?item_type rdfs:label ?type_of_type_label .
  ?item rdfs:label ?name .
  
  FILTER(LANG(?type_of_type_label) = "en")
  FILTER(LANG(?name) = "en")	


	

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
