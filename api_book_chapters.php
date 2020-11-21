<?php

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/wikidata_api.php');


$id = 'Q102181870';

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
		schema:name "Chapters";
		schema:dataFeedElement ?item .

		?item 
			rdf:type schema:DataFeedItem;
			rdf:type ?item_type;
			schema:name ?title;
			schema:datePublished ?datePublished;
			schema:description ?description;	
			
# doi
 schema:identifier ?doi_identifier .
					 ?doi_identifier rdf:type schema:PropertyValue .
					 ?doi_identifier schema:propertyID "doi" .
					 ?doi_identifier schema:value ?doi .
 							
	}
	WHERE
	{
	VALUES ?book { wd:' . $id . ' }

	?item wdt:P361 ?book .
	?item wdt:P31 ?type .

	?item wdt:P1476 ?title .

	OPTIONAL {
	?item wdt:P577 ?date .
	BIND(STR(?date) as ?datePublished) 
	}  		
	
  # Make DOI lowercase
 OPTIONAL {
   ?item wdt:P356 ?doi_string .   
   BIND( IRI (CONCAT (STR(?item), "#doi")) as ?doi_identifier)
   BIND( LCASE(?doi_string) as ?doi)
  }   		
			} LIMIT 2000';



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
