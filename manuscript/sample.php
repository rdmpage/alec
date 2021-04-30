<?php

// Given a file of Wikidata QIDs generate a random sample of n QIDs

$n = 1000;


// Read list of QIDs
$qids = array();

$filename = 'ion-wikidata.txt'; // Wikidata QIDs for publications

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
	
	if ($line != '')
	{
		$qids[] = $line;
	}

}	

// print_r($qids);

$min = 0;
$max = count($qids) - 1;

$sample = array();

for ($i = 0; $i < $n; $i++)
{
	$r = rand($min, $max);
	
	echo $r . "\n";
	$sample[] = $qids[$r];
}

print_r($sample);

file_put_contents('sample.txt', join("\n", $sample));


?>
