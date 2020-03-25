<?php

// Do a simple search of Wikidata and return a data feed 

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/lib.php');

$qid = 'Q6495649';

if (isset($_GET['qid']))
{
	$qid = $_GET['qid'];
}


$image_url = 'https://via.placeholder.com/100x100';


$url = 'https://www.wikidata.org/w/api.php?action=wbgetclaims&property=P18&entity=' . $qid . '&format=json';

$json = get($url, '*/*');

//echo $json;

$obj = json_decode($json);

//print_r($obj);

if (isset($obj->claims->{'P18'}))
{
	$image_url = 'https://commons.wikimedia.org/w/thumb.php?f=' . $obj->claims->{'P18'}[0]->mainsnak->datavalue->value . '&w=200';
}


header("Location: $image_url");	


?>
