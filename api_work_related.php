<?php

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/wikidata_api.php');


$id = 'Q54800926';

if (isset($_REQUEST['id']))
{
	$id = $_REQUEST['id'];
}



$query = 'CONSTRUCT 
{
	<http://example.rss>
	rdf:type schema:DataFeed;
	schema:name "Related";
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
    {
		SELECT ?item ?item_type ?title ?datePublished ?description ?doi (COUNT(?item) AS ?c)		
		WHERE 
		{
			VALUES ?work { wd:' . $id . ' }

			?x wdt:P2860 ?work .
			?y wdt:P2860 ?work .

			?x wdt:P2860 ?item .
			?y wdt:P2860 ?item .

			BIND(REPLACE(STR(?x), "http://www.wikidata.org/entity/Q", "") AS ?x_num)
			BIND(REPLACE(STR(?y), "http://www.wikidata.org/entity/Q", "") AS ?y_num)

			#?item wdt:P2860 ?work .
			#?item wdt:P31 ?type .

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
      
      		FILTER (?x != ?y)
      		FILTER(xsd:integer(?x_num) < xsd:integer(?y_num))
      
      		FILTER (?work != ?item)
		}
       	GROUP BY ?item ?item_type ?title ?datePublished ?description ?doi
    }
    FILTER (?c >= 5)
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
