<?php

// Linkage count 

$config['cache']						= dirname(__FILE__) . '/cache';
$config['wikidata_sparql_endpoint'] 	= 'https://query.wikidata.org/bigdata/namespace/wdq/sparql';


//----------------------------------------------------------------------------------------
// post
function post($url, $format = 'application/ld+json', $data =  null)
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
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


$force = false;
$force = true;

$filename = 'sample.txt';

// Read list of QIDs we want to sample

$qids = array();

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
	
	if ($line != '')
	{
		$qids[] = $line;
	}

}	


// just do some test cases 
//$qids = array('Q28960244');

$scores = array();

$counter = 1;

$num = 0;

foreach ($qids as $qid)
{
	$scores[$qid] = array();
 
 $sparql = 'PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>
SELECT (COUNT(?o) AS ?link) ?p WHERE
{ 
  VALUES ?work { wd:' . $qid . ' }
  
  {    
    # outbound
    ?work ?p ?o 
  }
  UNION
  {
    # inbound
      ?o ?p ?work .   
  }
  # links to things
  ?o wdt:P31 ?type .
  
  # exclusions
 
 }
GROUP BY ?link ?p';

// Count use as reference 
$sparql = 'PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>
SELECT ?o ?p WHERE
{ 
  VALUES ?work { wd:' . $qid . ' }
  
  {    
    # outbound
    ?work ?p ?o 
  }
  UNION
  {
    # inbound
      ?o ?p ?work .   
  }
  UNION
  {
  	  VALUES ?p { wdt:P248 }
      ?provenance pr:P248 ?work . 
      ?statement prov:wasDerivedFrom ?provenance .
 	  ?o p:P225 ?statement . 
   
  }
  # links to things
  ?o wdt:P31 ?type .
 }';

	$filename = $config['cache'] . '/' . $qid . '-sparql.json';
	
	// echo $filename . "\n";
	
	if (!file_exists($filename) || $force)
	{

		// echo " Not found\n";

		$json = post(
					$config['wikidata_sparql_endpoint'], 
					'application/json',
					'query=' . $sparql
				);
				
		file_put_contents($filename, $json);
		
		// Give server a break every 10 items
		if (($counter++ % 10) == 0)
		{
			$rand = rand(10000000, 30000000);
			echo "\n-- ...sleeping for " . round(($rand / 1000000),2) . ' seconds' . "\n\n";
			usleep($rand);
		}
	}
	
	$json = file_get_contents($filename);
	
 	$obj = json_decode($json);
 	
 	print_r($obj);
 	
 	
 	// We can have multiple values if, for example, an item has more than one type (P31)
 	// so we can't just simply group and count. So we do this manually here
 	$local_scores = array();
 	
	foreach ($obj->results->bindings as $binding)
	{
		$property = str_replace('http://www.wikidata.org/prop/direct/', '', $binding->p->value);
		
		$object = str_replace('http://www.wikidata.org/entity/', '', $binding->o->value);
		
		if (!isset($local_scores[$property]))
		{
			$local_scores[$property] = array();
		}
		$local_scores[$property][] = $object;

	}	 
	
	print_r($local_scores);
	
	// sumamrise scores
	foreach ($local_scores as $k => $v)
	{
		$scores[$qid][$k] = count(array_unique($v));
	}
	
	if ($num++ > 100)	
	{
		// break;
	}
 	
}


print_r($scores);

// Get total link count

// Any properties to ignore?
$ignore = array(
	'P407', // language of work
	'P31', // instance of
	'P921', // main subject
	'P6216', // copyright status
	'P275', // copyright license
);

$total = 0;

$freq = array();

foreach ($scores as $score)
{
	$item_total = 0;

	foreach ($score as $k => $v)
	{
		if (!in_array($k, $ignore))
		{
			$item_total += $v;
		}
	}
	
	if (!isset($freq[$item_total]))
	{
		$freq[$item_total] = 0;
	}
	$freq[$item_total]++;
	
	$total += $item_total;

}

echo count($scores) . "\n";
echo $total . "\n";
echo $total / count($scores) . "\n";


$max = max(array_keys($freq));

for ($i = 0; $i < $max; $i++)
{
	if (!isset($freq[$i]))
	{
		$freq[$i] = 0;
	}
	
}

ksort($freq);
print_r($freq);

$data_to_export = array();

foreach ($freq as $k => $v)
{
	$data_to_export[] = "$k\t$v";
}

$output = '';
$output .= "Links\tFrequency\n";
$output .=  join("\n", $data_to_export);
$output .= "\n";
file_put_contents('density.tsv', $output);


// Get types of connections

$props = array();

foreach ($scores as $score)
{
	foreach ($score as $k => $v)
	{
		if (!in_array($k, $ignore))
		{
			if (!isset($props[$k]))
			{
				$props[$k] = 0;
			}
			$props[$k] += $v;
		}
	}

}

$property_map = array(
'P31' => 'instance of',
'P50' => 'author',
'P275' => 'copyright license',
'P304' => 'page(s)',
'P356' => 'DOI',
'P364' => 'original language of film or TV show',
'P407' => 'language of work or name',
'P433' => 'issue',
'P478' => 'volume',
'P577' => 'publication date',
'P687' => 'BHL Page ID',
'P698' => 'PubMed ID',
'P724' => 'Internet Archive ID',
'P888' => 'JSTOR article ID',
'P921' => 'main subject',
'P932' => 'PMCID',
'P953' => 'full work available at URL',
'P1433' => 'published in',
'P1476' => 'title',
'P2007' => 'ZooBank publication ID',
'P2093' => 'author name string',
'P2860' => 'cites work',
'P2888' => 'exact match',
'P3181' => 'OpenCitations bibliographic resource ID',
'P5315' => 'BioStor work ID',
'P5875' => 'ResearchGate publication ID',
'P6179' => 'Dimensions Publication ID',
'P6216' => 'copyright status',
'P6535' => 'BHL part ID',
'P6982' => 'Australian Faunal Directory publication ID',
'P8608' => 'Fatcat ID',


'P1144' => 	'Library of Congress Control Number (LCCN) (bibliographic)',
'P850' => 	'WoRMS-ID for taxa',
'P6678' => 	'WoRMS source ID',
'P1184' => 	'Handle ID',
'P291' => 	'place of publication',
'P648' => 	'Open Library ID',
'P373' => 	'Commons category',
'P1104' => 	'number of pages',
'P8091' => 	'Archival Resource Key',

'P859' => 'sponsor',

'P361' => 'part of',
'P5326' => 'publication in which this taxon name was established',

'P248' => 'stated in',

);

print_r($props);

foreach ($props as $k => $v)
{
	echo $property_map[$k] . " " . $v . "\n";
}


$data_to_export = array();

foreach ($props as $k => $v)
{
	$data_to_export[] = $property_map[$k] . "\t" . $v;
}

$output = '';
$output .= "Property\tFrequency\n";
$output .=  join("\n", $data_to_export);
$output .= "\n";
file_put_contents('density_properties.tsv', $output);

?>


