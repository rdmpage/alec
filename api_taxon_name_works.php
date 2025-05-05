<?php

// find work that is source for this name

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/wikidata_api.php');


$id = 'Q56886408';
$id = 'Q2570594';

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
		schema:name "Reference";
		schema:dataFeedElement ?item .

		?item 
			rdf:type schema:DataFeedItem;
			rdf:type ?item_type;
			schema:name ?title;
			schema:datePublished ?datePublished;


# doi
 schema:identifier ?doi_identifier .
					 ?doi_identifier rdf:type schema:PropertyValue .
					 ?doi_identifier schema:propertyID "doi" .
					 ?doi_identifier schema:value ?doi .	

	}
	WHERE
	{
		hint:Query hint:optimizer "None" .
		VALUES ?taxon { wd:' . $id . ' } 
	
		# filter by type of work
		# wd:Q13442814 article
		VALUES ?item_type { wd:Q13442814 } 
		
		# scholarly graph
		{
			# publication with taxon as subject
			?item wdt:P921 ?taxon . 
		}  
		# main graph
		UNION 
		{
			SERVICE wdsubgraph:wikidata_main 
			{
				# Wikidata reference
				?taxon p:P225 ?statement. 
				?statement prov:wasDerivedFrom ?provenance .
	  
				# is it stated in a Wikidata item?
				?provenance pr:P248 ?item .
			}
		}
		UNION 
		{
			SERVICE wdsubgraph:wikidata_main 
			{
				# publication in which this taxon name was established
				?taxon wdt:P5326 ?item . 
			}  
		}	
   		
  		?item wdt:P31 ?item_type .
  		
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
// Use scholarly graph
echo sparql_construct_stream($config['sparql_scholarly_endpoint'], $query);
if ($callback != '')
{
	echo ')';
}


?>
