<?php

// Query for overlapping authors

//----------------------------------------------------------------------------------------
// get
function get($url, $format = "application/json")
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   	
	curl_setopt($ch, CURLOPT_HTTPHEADER, 
		array(
			"Accept: " . $format, 
			"User-agent: Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7B405"
			)
		);

	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	curl_close($ch);
	
	return $response;
}

//----------------------------------------------------------------------------------------
function get_count($sparql)
{
	$count = 0;

	$url = 'https://query.wikidata.org/bigdata/namespace/wdq/sparql?query=' . urlencode($sparql);
	$json = get($url);
	
	// echo $json;
		
	if ($json != '')
	{
		$obj = json_decode($json);
		
		// print_r($obj);
		
		
		/*
		if (isset($obj->results->bindings))
		{
			if (count($obj->results->bindings) != 0)	
			{
				$count = $obj->results->bindings[0]->count->value;
			}
		}
		*/
		if (isset($obj->results->bindings))
		{
			$count = count($obj->results->bindings);
		}
		
		
	}
	
	return $count;
}

$raw_counts = array();

if (1)
{

//----------------------------------------------------------------------------------------
// IPNI
$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  ?item wdt:P586 ?IPNI_author_ID.
  ?item wdt:P31 wd:Q5 .
}
GROUP BY ?item';

$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  ?item wdt:P586  ?IPNI_author_ID.
  MINUS {
    ?item wdt:P4081 ?bhl.
  }
  #MINUS {
  #  ?item wdt:P586 ?ipni.
  #}  
  MINUS {
    ?article schema:about ?item ;
    schema:isPartOf <https://species.wikimedia.org/> .
  }  
  MINUS {
    ?item wdt:P2006 ?zoobank.
  }
  
  ?item wdt:P31 wd:Q5 .
}
GROUP BY ?item';

$count = get_count($sparql);

$raw_counts[json_encode(array('ipni'))] = $count;


//----------------------------------------------------------------------------------------
// BHL 
$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  ?item wdt:P4081 ?bhl.
  ?item wdt:P31 wd:Q5 .
}
GROUP BY ?item';

$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  ?item wdt:P4081  ?bhl.
  #MINUS {
  #  ?item wdt:P4081 ?bhl.
  #}
  MINUS {
    ?item wdt:P586 ?ipni.
  }  
  MINUS {
    ?article schema:about ?item ;
    schema:isPartOf <https://species.wikimedia.org/> .
  }  
  MINUS {
    ?item wdt:P2006 ?zoobank.
  }
  
  ?item wdt:P31 wd:Q5 .
}
GROUP BY ?item';

$count = get_count($sparql);

$raw_counts[json_encode(array('bhl'))] = $count;


//----------------------------------------------------------------------------------------
// ZooBank 
$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
   ?item wdt:P2006 ?zoobank.
   ?item wdt:P31 wd:Q5 .
}
GROUP BY ?item';

$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  ?item wdt:P2006  ?zoobank.
  MINUS {
    ?item wdt:P4081 ?bhl.
  }
  MINUS {
    ?item wdt:P586 ?ipni.
  }  
  MINUS {
    ?article schema:about ?item ;
    schema:isPartOf <https://species.wikimedia.org/> .
  }  
  #MINUS {
   # ?item wdt:P2006 ?zoobank.
  #}
  
  ?item wdt:P31 wd:Q5 .
}
GROUP BY ?item';

$count = get_count($sparql);

$raw_counts[json_encode(array('zoobank'))] = $count;


//----------------------------------------------------------------------------------------
// Wikispecies 
$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  ?item wdt:P31 wd:Q5 .
  ?article 	schema:about ?item ;
  schema:isPartOf <https://species.wikimedia.org/> .
}
GROUP BY ?item';

$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  ?item wdt:P31 wd:Q5 .
    ?article schema:about ?item ;
    schema:isPartOf <https://species.wikimedia.org/> .

  MINUS {
    ?item wdt:P4081 ?bhl.
  }
  MINUS {
    ?item wdt:P586 ?ipni.
  }  
  #MINUS {
  #  ?article schema:about ?item ;
  #  schema:isPartOf <https://species.wikimedia.org/> .
  #}  
  MINUS {
    ?item wdt:P2006 ?zoobank.
  }
  
  
}
GROUP BY ?item';


$count = get_count($sparql);

$raw_counts[json_encode(array('wikispecies'))] = $count;



//----------------------------------------------------------------------------------------
// Wikispecies + Zoobank
$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  ?item wdt:P31 wd:Q5 .
  ?article 	schema:about ?item ;
  schema:isPartOf <https://species.wikimedia.org/> .
	?item wdt:P2006 ?zoobank.
}
GROUP BY ?item';


$count = get_count($sparql);

$raw_counts[json_encode(array('wikispecies', 'zoobank'))] = $count;

//----------------------------------------------------------------------------------------
// Wikispecies + IPNI
$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  ?item wdt:P31 wd:Q5 .
  ?article 	schema:about ?item ;
  schema:isPartOf <https://species.wikimedia.org/> .
  ?item wdt:P586 ?IPNI_author_ID.
}
GROUP BY ?item';


$count = get_count($sparql);

$raw_counts[json_encode(array('wikispecies', 'ipni'))] = $count;


//----------------------------------------------------------------------------------------
// Wikispecies + BHL
$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  ?item wdt:P31 wd:Q5 .
  ?article 	schema:about ?item ;
  schema:isPartOf <https://species.wikimedia.org/> .
  ?item wdt:P4081 ?bhl.
}
GROUP BY ?item';


$count = get_count($sparql);

$raw_counts[json_encode(array('wikispecies', 'bhl'))] = $count;


//----------------------------------------------------------------------------------------
// IPNI + BHL
$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  ?item wdt:P586 ?IPNI_author_ID.
  ?item wdt:P4081 ?bhl.
  ?item wdt:P31 wd:Q5 .
}
GROUP BY ?item';

$count = get_count($sparql);

$raw_counts[json_encode(array('bhl', 'ipni'))] = $count;


//----------------------------------------------------------------------------------------
// ZooBank + IPNI
$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  ?item wdt:P2006  ?zoobank.
  ?item wdt:P586 ?IPNI_author_ID.
  ?item wdt:P31 wd:Q5 .
}
GROUP BY ?item';

$count = get_count($sparql);

$raw_counts[json_encode(array('ipni', 'zoobank'))] = $count;


//----------------------------------------------------------------------------------------
// ZooBank + BHL
$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  ?item wdt:P2006 ?zoobank.
  ?item wdt:P4081 ?bhl.
  ?item wdt:P31 wd:Q5 .
}
GROUP BY ?item';

$count = get_count($sparql);

$raw_counts[json_encode(array('bhl', 'zoobank'))] = $count;
}

// 3-way

// bhl, ipni, wikispecies -zoobank
/*
$sparql = 'SELECT (COUNT(?item) AS $count) WHERE {
  
  ?item wdt:P4081 ?bhl.
  ?item wdt:P586 ?IPNI_author_ID.
  { ?item wdt:P31 wd:Q5 . ?article schema:about ?item ; schema:isPartOf <https://species.wikimedia.org/> .}
  #?item wdt:P2006 ?zoobank.
}';
$count = get_count($sparql);

$raw_counts[json_encode(array('bhl', 'ipni', 'wikispecies'))] = $count;
*/


// bhl, ipni, zoobank -wikispecies
// bhl, wikispecies, zoobank -ipni
// ipni, wikispecies, zoobank -bhl


//----------------------------------------------------------------------------------------
// Results

print_r($raw_counts);

// export as DOT file

$nodes = array();
$edges = array();

foreach ($raw_counts as $k => $v)
{
	$label_array = json_decode($k);
	asort($label_array);
	
	$node_name = 'node_' . join("_", $label_array);
	
	if (count($label_array) == 1)
	{
		$node_label = $label_array[0] . '\n' . $v;
	}
	else
	{
		$node_label = $v;
	}
	
	$node_size = round(log10($v),1) - 1;
	
	$nodes[] = $node_name . ' [shape=circle,fillcolor="yellow",style=filled,label="' . $node_label . '",fixedsize=true,width="' . $node_size . '"];';
	
	if (count($label_array) == 2)
	{
		$edges[] = 'node_' .  $label_array[0] . ' -- ' . $node_name  . ' -- ' .  'node_' .  $label_array[1] . ';';
	
	}
	
}


$filename = 'clusters.dot';

$dot = "graph G {
overlap = false;
";

foreach ($nodes as $n)
{
	$dot .= $n . "\n";
}

foreach ($edges as $e)
{
	$dot .=  $e . "\n";
}


$dot .=  "}\n";

file_put_contents($filename, $dot);



?>
