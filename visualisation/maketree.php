<?php

// Tree rooted on this taxon display as HTML

error_reporting(E_ALL);

require_once(dirname(dirname(__FILE__)) . '/config.inc.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');


require_once(dirname(__FILE__) . '/tree.php');



$query = 'PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>
SELECT ?root ?root_name ?parent ?parent_name ?child ?child_name ?rank_string ?msw ?year WHERE
{ 
  VALUES ?root_name {"Cetacea"}
 ?root wdt:P225 ?root_name .
 ?child wdt:P171+ ?root . 
 ?child wdt:P171 ?parent .
 #?child wdt:P225 ?child_name .
  
   ?child p:P225 ?child_statement . 
   ?child_statement ps:P225 ?child_name .  
  
  ?child wdt:P105 ?rank .
  ?rank rdfs:label ?rank_string .
  FILTER(LANG(?rank_string)="en")
  
  # MSW identifier
  OPTIONAL {
    ?child wdt:P959 ?msw .
 }
 
 ?parent wdt:P225 ?parent_name .
 }

';


$url = 'https://query.wikidata.org/bigdata/namespace/wdq/sparql?query=' . urlencode($query);
$json = get($url, 'application/json');

file_put_contents('whale.json', $json);

$json = file_get_contents('whale.json');


//echo $json;

$obj = json_decode($json);

print_r($obj);

//exit();

if (0)
{
$nodes = array();

$root = null;


foreach ($obj->results->bindings as $binding)
{
	if (!$root)
	{
		$root = new Node($binding->root_name->value);
		
		$root->SetId(str_replace('http://www.wikidata.org/entity/', '', $binding->root->value));
				
		$nodes[$root->GetId()] = $root;
		
		echo "Root\n";
	}


	$parent_name = $binding->parent_name->value;
	$parent_id = str_replace('http://www.wikidata.org/entity/', '', $binding->parent->value);
	
	$child_name =  $binding->child_name->value;
	$child_id = str_replace('http://www.wikidata.org/entity/', '', $binding->child->value);
	
	if (!isset($nodes[$parent_id ]))
	{
		$parent = new Node($parent_name);
		$parent->SetId($parent_id );
		$nodes[$parent->GetId()] = $parent;
	}
	$p = $nodes[$parent_id];
	
	if (!isset($nodes[$child_id ]))
	{
		$child = new Node($child_name);
		$child->SetId($child_id );
		$nodes[$child->GetId()] = $child;
	}
	$q = $nodes[$child_id];
	
	
	$q->SetAncestor($p);
	
	echo $p->label . "->" . $q->label . "\n";
	
	if ($p->GetChild())
	{
		$p = $p->GetChild();		
		while ($p)
		{
			$r = $p;
			$p = $p->GetSibling();
		}
		echo "Set " . $q->label . " as sibling of " . $r->label . "\n";
		$r->SetSibling($q);
	}
	else
	{
		echo "Set " . $q->label . " as child of " . $p->label . "\n";
		$p->SetChild($q);
	}


}
}

echo "\n\nNodes\n";
foreach ($nodes as $n)
{
	echo  $n->id . ' ' . $n->label . "\n";

}
echo "\n\n";

//exit();

/*
$t = new Tree();
$t->SetRoot($root);

//$t->Dump();
echo $t->WriteDot();
*/


echo  "digraph{\n";
foreach ($obj->results->bindings as $binding)
{
	$parent_id = str_replace('http://www.wikidata.org/entity/', '', $binding->parent->value);
	$child_id = str_replace('http://www.wikidata.org/entity/', '', $binding->child->value);


	echo $child_id . "->" . $parent_id . ";\n";
}
echo "}\n";




?>
