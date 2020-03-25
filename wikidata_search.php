<?php

// Do a simple search of Wikidata and return a data feed 

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/lib.php');

//----------------------------------------------------------------------------------------
// Do SPARQL query to find item with an identifier
function identifier_sparql_query($identifier)
{
	global $config;
	$results = array();
	
	$json = get(
		$config['sparql_endpoint']. '?query=' . urlencode($identifier->sparql), 
		'application/json'
	);
	
	//echo $json;
	
	if ($json != '')
	{
		$obj = json_decode($json);
		
		//print_r($obj);
		
		if (isset($obj->results->bindings))
		{
			if (count($obj->results->bindings) != 0)	
			{
				$hit = new stdclass;
						
				$qid = $obj->results->bindings[0]->item->value;
				$qid = preg_replace('/https?:\/\/www.wikidata.org\/entity\//', '', $qid);
				
				if (isset($obj->results->bindings[0]->label))
				{
					$hit->label = $obj->results->bindings[0]->label->value;
				}

				if (isset($obj->results->bindings[0]->description))
				{
					$hit->description = $obj->results->bindings[0]->description->value;
				}

				//print_r($hit);
				
				$results[$qid] = $hit;
				
				
			}
		}
		
	}
	
	return $results;
	
}

//----------------------------------------------------------------------------------------
function wikidata_search($query)
{
	$results = array();
	$votes = array();
	
	$query = trim($query);
	
	// try to interpet the query, for example, is it an identifier?
	
	$identifier = new stdclass;
	
	if (!isset($identifier->value))
	{
		if (preg_match('/(https?:\/\/(dx.)?doi.org\/)?(doi:)?(?<id>10\.\d+\/.*)/i', $query, $m))
		{
			$identifier->namespace = 'doi';
			$identifier->value = $m['id'];
			
			
			$identifier->sparql = 'SELECT * WHERE { 
    ?item wdt:P356 "' . strtoupper($identifier->value) . '" .
  
    OPTIONAL {
     ?item rdfs:label ?label .
     FILTER (lang(?label) = "en")
    } 
 
    OPTIONAL {
     ?item schema:description ?description .
     FILTER (lang(?description) = "en")
    } 
}';			
		}
	
	}

	// ORCID
	if (!isset($identifier->value))
	{
		if (preg_match('/(https?:\/\/orcid.org\/)?(?<id>0000-000(1-[5-9]|2-[0-9]|3-[0-4])\d{3}-\d{3}[\dX])/i', $query, $m))
		{
			$identifier->namespace = 'orcid';
			$identifier->value = $m['id'];
			
			$identifier->sparql = 'SELECT * WHERE { 
    ?item wdt:P496 "' . $identifier->value . '" .
  
    OPTIONAL {
     ?item rdfs:label ?label .
     FILTER (lang(?label) = "en")
    } 
 
    OPTIONAL {
     ?item schema:description ?description .
     FILTER (lang(?description) = "en")
    } 
}';
		}
	
	}
	
	// UUID (could be many different namespaces)
	// ZooBank author or publication
	// AFD publication
	if (!isset($identifier->value))
	{
		if (preg_match('/(?<id>[a-z0-9]{8}(-[a-z0-9]{4}){3}-[a-z0-9]{12})/i', $query, $m))
		{
			$identifier->namespace = 'uuid';
			$identifier->value = $m['id'];
			
			$identifier->sparql = 'SELECT * WHERE { 
  {
    # ZooBank author
    ?item wdt:P2006 "' . strtoupper($identifier->value) . '" .
  }
  UNION
 {
    # ZooBank publication
    ?item wdt:P2007 "' . strtoupper($identifier->value) . '" .
  }  
  UNION
 {
    # AFD publication
    ?item wdt:P6982 "' . strtolower($identifier->value) . '" .
  }  
  
    OPTIONAL {
     ?item rdfs:label ?label .
     FILTER (lang(?label) = "en")
    } 
 
    OPTIONAL {
     ?item schema:description ?description .
     FILTER (lang(?description) = "en")
    } 
}';
		}
	
	}
	
	
	// print_r($identifier);
	
	$go = true;
	
	if (isset($identifier->value))
	{
		// we have an identifer
		$results = identifier_sparql_query($identifier);
		
		$go = (count($results) == 0);
	
	}
	
	if ($go)
	{
		// if not a recognised identifier, or nothing found, we do entity and text searching
	
		//first up do an entity query

		$parameters = array(
			'action' => 'wbsearchentities',
			'search' => $query,
			'type' => 'item',
			'format' => 'json',
			'language' => 'en'
		);

		$url = 'https://www.wikidata.org/w/api.php?' . http_build_query($parameters, null, '&', PHP_QUERY_RFC3986);

		//echo $url . "\n";

		$json = get($url, 'application/json');

		// echo $json;

		$obj = json_decode($json);

		// print_r($obj);

		if ($obj)
		{
			foreach ($obj->search as $hit)
			{	
				if (!isset($results[$hit->title]))
				{
					$results[$hit->title] = $hit;
				}
	
				if (!isset($votes[$hit->title]))
				{
					$votes[$hit->title] = 0;
				}
				$votes[$hit->title]++;
			}
		}
	
		// then do a text search
		$parameters = array(
			'action' => 'query',
			'list'	=> 'search',
			'srsearch' => $query,
			'srprop'	=> 'titlesnippet|snippet',
			'format' => 'json',
		);

		$url = 'https://www.wikidata.org/w/api.php?' . http_build_query($parameters, null, '&', PHP_QUERY_RFC3986);

		$json = get($url, 'application/json');

		// echo $json;

		$obj = json_decode($json);

		// print_r($obj);

		if ($obj)
		{
			foreach ($obj->query->search as $hit)
			{
	
				if (!isset($results[$hit->title]))
				{
					$results[$hit->title] = $hit;
				}
	
				if (!isset($votes[$hit->title]))
				{
					$votes[$hit->title] = 0;
				}
				$votes[$hit->title]++;
			}
		}	
	
		// to do: order results by votes(?)
	}

	// schema.org DataFeed
	$output = new stdclass;

	$output->{'@context'} = (object)array('@vocab' => 'http://schema.org/');

	$output->{'@graph'} = array();
	$output->{'@graph'}[0] = new stdclass;
	$output->{'@graph'}[0]->{'@id'} = "http://example.rss";
	$output->{'@graph'}[0]->{'@type'} = "DataFeed";
	
	$output->{'@graph'}[0]->name = "Search";
	
	$output->{'@graph'}[0]->dataFeedElement = array();


	foreach ($results as $qid => $hit)
	{
		$item = new stdclass;
		$item->{'@id'} = 'http://www.wikidata.org/entity/' . $qid;
		$item->{'@type'} = array("DataFeedItem");
		
		if (isset($hit->titlesnippet))
		{
			$item->name = strip_tags($hit->titlesnippet);
			$item->name = html_entity_decode($item->name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			if ($hit->snippet != '')
			{
				$item->description = $hit->snippet;
			}
		}
		else
		{
			$item->name = $hit->label;
			if (isset($hit->description))
			{
				$item->description = $hit->description;
			}
		}
	
		$output->{'@graph'}[0]->dataFeedElement[] = $item;
	}

	return $output;	

}

// test
if (0)
{

	$query = '10.3897/phytokeys.42.8408';
	$query = 'https://orcid.org/0000-0002-7975-1450';

	$output = wikidata_search($query);
	print_r($output);

}


// test
if (0)
{

	$query = 'Reijo Jussila';

	$query = 'Bulletin de l\'Herbier Boissier';

	$query = 'Bertolonia';

	//$query = 'Six New Species of Land Snails';
	//$query = 'Three new species of Bertolonia';

	wikidata_search($query);
}


?>
