<?php

// Do a simple search of Wikidata and return a data feed 

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/lib.php');

$qid = 'Q6495649';

if (isset($_GET['qid']))
{
	$qid = $_GET['qid'];
}


$image_url = 'https://via.placeholder.com/100x100/EEEEEE/EEEEEE';

$image_url = $config['web_root'] . '/images/100x100.png';

$url = 'https://www.wikidata.org/w/api.php?action=wbgetclaims&property=P18&entity=' . $qid . '&format=json';

$json = get($url, '*/*');

$obj = json_decode($json);

//print_r($obj);

if (isset($obj->claims->{'P18'}))
{
	$image_filename = $obj->claims->{'P18'}[0]->mainsnak->datavalue->value;
	$image_filename = str_replace(' ', '_', $image_filename);

	//$image_url = 'https://commons.wikimedia.org/w/thumb.php?f=' . $image_filename . '&w=200';
	
	// new method
	$hash = md5($image_filename);
	
	$image_url = 'https://upload.wikimedia.org/wikipedia/commons/thumb/' 
		. substr($hash, 0, 1) . '/' .  substr($hash, 0, 2) 
		. '/' . $image_filename . '/200px-' . $image_filename;
		
	if (!preg_match('/\.jpg$/', $image_url))
	{
		$image_url .= '.jpg';
	}
}

header("Location: $image_url");	

?>
