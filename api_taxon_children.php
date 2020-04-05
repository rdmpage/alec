<?php



require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/wikidata_api.php');


$id = 'Q2501775';

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
		schema:name "Child taxa";
		schema:dataFeedElement ?item .

		?item 
			rdf:type schema:DataFeedItem;
			rdf:type ?item_type;
			schema:name ?taxonName;
			schema:description ?description;	
			schema:image ?image;
			
 							
	}
	WHERE
	{
	VALUES ?parent { wd:' . $id . ' }

	?item wdt:P171 ?parent .
	?item wdt:P31 ?type .

	?item wdt:P225 ?taxonName .

	OPTIONAL {
   		?item wdt:P18 ?image .
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
echo sparql_construct_stream($config['sparql_endpoint'], $query);
if ($callback != '')
{
	echo ')';
}


?>
