<?php

// Check sample

//----------------------------------------------------------------------------------------
function get($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   	


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


$filename = 'sample.txt';

$cache = dirname(__FILE__) . '/cache';


// Read list of QIDs we want to sample
/*
$qids = array();


$missed = array();

$count = 0;

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
	
	
	if ($line != '')
	{
		$qid = $line;
		
		echo $qid . "\n";
		
		
		
		$sample_filename = $cache . '/' . $qid . '.xml';
		//$sample_filename = $cache . '/' . $qid . '-sparql.json';
		
		if (file_exists($sample_filename))
		{
			//echo "$sample_filename not found\n";
			//exit();
			
			$count++;
		}
		
	}

}	

echo $count . "\n";
*/

$files = scandir($cache);
echo count($files) . "\n";


print_r($files);

$q = array();
foreach ($files as $file)
{
	if (preg_match('/(Q\d+)/', $file, $m))
	{
		$qid = $m[1];
		if (!isset($q[$qid]))
		{
			$q[$qid] = array();
		}
		$q[$qid][] = $file;
	}
}

$bad = 0;

foreach ($q as $qid => $file)
{
	if (count($file) < 2)
	{
		echo $file[0] . "\n";
		
		$bad++;
	}
}

echo $bad . "\n";











