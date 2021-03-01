<?php

// Tree rooted on this taxon display as HTML

error_reporting(E_ALL);

//----------------------------------------------------------------------------------------
function get($url, $content_type = '')
{	
	$data = null;

	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE
	);

	if ($content_type != '')
	{
		
		$opts[CURLOPT_HTTPHEADER] = array(
			"Accept: " . $content_type, 
			"User-agent: Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7B405" 
		);
		
	}
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	return $data;
}

//----------------------------------------------------------------------------------------


$start = "Boesenbergia";
$start = "Leptodactylus";
//$start = "Diomedea";
//$start = "Nothofagus";


$query = 'PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>
SELECT * WHERE
{ 
  VALUES ?taxon_name {"' . $start . '"}
 ?taxon wdt:P225 ?taxon_name .
 ?taxon wdt:P171+ ?node . 
   
 ?node p:P225 ?node_statement . 
 ?node_statement ps:P225 ?node_name .  
  
 OPTIONAL { 
    ?node wdt:P171 ?parent .
    ?parent p:P225 ?parent_statement .
   ?parent_statement ps:P225 ?parent_name . 
  }    
 }
';

//echo $query . "\n";
//exit();


$url = 'https://query.wikidata.org/bigdata/namespace/wdq/sparql?query=' . urlencode($query);

// caching

$filename = $start . '.json';


if (file_exists($filename))
{
	$json = file_get_contents($filename);
}
else
{
	$json = get($url, 'application/json');
	file_put_contents($filename);
}


//echo $json;

$obj = json_decode($json);

// print_r($obj);

//exit();


//-----------------


$nodes = array() ;
$adjacent = array();


foreach ($obj->results->bindings as $binding)
{
	$parent_id = '';

	if (isset($binding->parent->value))
	{

		$parent_id = str_replace('http://www.wikidata.org/entity/', '', $binding->parent->value);
		$parent_name = $binding->parent_name->value;
		
		if (!isset($nodes[$parent_id]))
		{
			$node = new stdclass;
			$node->id = $parent_id;
			$node->name = $parent_name;
			$nodes[$parent_id] = $node;		
		}		

	}
	
	
	$node_id = str_replace('http://www.wikidata.org/entity/', '', $binding->node->value);
	$node_name = $binding->node_name->value;


	if (!isset($dag[$node_id]))
	{
		$node = new stdclass;
		$node->id = $node_id;
		$node->name = $node_name;
		$nodes[$node_id] = $node;		
	}		
	
	
	if ($parent_id != '')
	{
		// edges
		if (!isset($adjacent[$node_id]))
		{
			$adjacent[$node_id] = array();
		}
		
		$adjacent[$node_id][] = $parent_id;
	}
	
	//print_r($dag);
	
	//echo "n=$node_id p=$parent_id\n";

}

//print_r($nodes);
//print_r($adjacent);

//----------------------------------------------------------------------------------------


// https://www.geeksforgeeks.org/shortest-path-for-directed-acyclic-graphs/
// https://en.wikipedia.org/wiki/Topological_sorting#Application_to_shortest_path_finding

//----------------------------------------------------------------------------------------
function topologicalSortUtil($v, &$visited, &$stack)
{
	global $adjacent;

	$visited[$v] = true;
	
	if (isset($adjacent[$v]))
	{
		foreach ($adjacent[$v] as $id)
		{
			if (!$visited[$id])
			{
				topologicalSortUtil($id, $visited, $stack);
			}
		}
	}
		
	$stack[] = $v;
}


//----------------------------------------------------------------------------------------
$stack = array();
$visited = array();

foreach ($nodes as $node)
{
	$visited[$node->id] = false;
}

foreach ($nodes as $node)
{
	if (!$visited[$node->id])
	{
		topologicalSortUtil($node->id, $visited, $stack);
	}

}

//print_r($stack);

foreach ($stack as $id)
{
	echo $nodes[$id]->name . "\n";
}

//$keys = array_flip($stack);

// print_r($stack);
// print_r($keys);

$dist = array();
$path = array();
foreach ($stack as $id)
{
	$dist[$id] = 100;
	$path[$id] = null;
}
$dist[array_keys($dist)[count($dist)-1]] = 0;

//print_r($dist);


while (count($stack) > 0)
{
	$u = array_pop($stack);
	
	echo $u . " == \n";
	
	if ($dist[$u] != 100)
	{
		if (isset($adjacent[$u]))
		{
			foreach ($adjacent[$u] as $id)
			{
				if ($dist[$id] > $dist[$u] + 1)
				{
					$dist[$id] = $dist[$u] + 1;
					$path[$id] = $u;
				}
			}
			echo "\n";
		}
	
	}
	
	
}

print_r($dist);
print_r($path);

$s = array_shift($path);
while ($s)
{
	//echo $s . "\n";
	echo $nodes[$s]->name . "\n";
	$s = $path[$s];

}

//exit();


//-----------------

$dag = array();


foreach ($obj->results->bindings as $binding)
{
/*
	$taxon_id = str_replace('http://www.wikidata.org/entity/', '', $binding->taxon->value);
	$taxon_name = $binding->root_name->value;
*/
	$parent_id = '';

	if (isset($binding->parent->value))
	{

		$parent_id = str_replace('http://www.wikidata.org/entity/', '', $binding->parent->value);
		$parent_name = $binding->parent_name->value;
		
		if (!isset($dag[$parent_id]))
		{
			$node = new stdclass;
			$node->id = $parent_id;
			$node->name = $parent_name;
			$node->edges = array();
		
			$dag[$parent_id] = $node;		
		}		

	}
	
	
	$node_id = str_replace('http://www.wikidata.org/entity/', '', $binding->node->value);
	$node_name = $binding->node_name->value;


	if (!isset($dag[$node_id]))
	{
		$node = new stdclass;
		$node->id = $node_id;
		$node->name = $node_name;
		$node->edges = array();
		
		$dag[$node_id] = $node;		
	}		
	
	
	if ($parent_id != '')
	{
		// edges
		$dag[$node_id]->edges[] = $parent_id;
	}
	
	//print_r($dag);
	
	//echo "n=$node_id p=$parent_id\n";

}


//print_r($dag);

//exit();

// dump dag

$dot = 'digraph{
/* rankdir=RL; */
';

// nodes
foreach ($dag as $node)
{
	$dot .= $node->id . ' [label="' . addcslashes($node->name, '"') . '"];' . "\n";

}

// edges
foreach ($dag as $node)
{
	foreach ($node->edges as $edge)
	{
		$dot .= $node->id . ' -> ' . $edge . ";\n";
	
	}

}


$dot .= '}';
$dot .= "\n";


file_put_contents($start . '-lineage_dag.dot', $dot);



?>
