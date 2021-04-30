<?php

// Edit history for a set of QIDs

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
function compare($old, $new)
{
	$diff = array();

	// labels
	//------
	$labels1 = array();
	foreach ($old->labels as $k => $v)
	{
		$labels1[] = $k;
	}

	//------
	$labels2 = array();
	foreach ($new->labels as $k => $v)
	{
		$labels2[] = $k;
	}
	
	$diff['labels'] = array_merge(array_diff($labels1, $labels2), array_diff($labels2, $labels1));

	// descriptions
	//------
	$descriptions1 = array();
	foreach ($old->descriptions as $k => $v)
	{
		$descriptions1[] = $k;
	}

	//------
	$descriptions2 = array();
	foreach ($new->descriptions as $k => $v)
	{
		$descriptions2[] = $k;
	}
	
	$diff['descriptions'] = array_merge(array_diff($descriptions1, $descriptions2), array_diff($descriptions2, $descriptions1));

	
	// claims
	
	// get list of claims in old and new edit
	$claims1 = array();
	foreach ($old->claims as $k => $v)
	{
		$claims1[$k] = count($v);
	}

	$claims2 = array();
	foreach ($new->claims as $k => $v)
	{
		$claims2[$k] = count($v);
	}


	// compare
	//print_r($claims1);
	//print_r($claims2);
	
	// get list of all property keys in the two edits
	$properties1 = array_keys($claims1);
	$properties2 = array_keys($claims2);
	
	// print_r($properties1);
	// print_r($properties2);
	
	$p = array_merge($properties1, $properties2);
	$p = array_unique($p);
	// print_r($p);
	

	// Go through properties and compare number of claims, we are only interested
	// in major edits, i.e., adding or deleteing a claim
	
	foreach($p as $prop)
	{
		// both edits have this property, do they differ in number?
		if (isset($claims1[$prop]) && isset($claims2[$prop]))
		{
			if ($claims1[$prop] == $claims2[$prop])
			{
				
			}
			else
			{
				if (!isset($diff[$prop]))
				{
					$diff[$prop] = 0;
				}
			
				$diff[$prop] = abs($claims1[$prop] - $claims2[$prop]);
			}
		}
		else
		{
			// only one edit has this property
			if (isset($claims1[$prop]))
			{
				if (!isset($diff[$prop]))
				{
					$diff[$prop] = 0;
				}			
				$diff[$prop] = $claims1[$prop];
			}
			else
			{
				if (!isset($diff[$prop]))
				{
					$diff[$prop] = 0;
				}			
				$diff[$prop] = $claims2[$prop];
			}
		}
	
	}
	
	//print_r($diff);

	//echo "-----------------------\n";
	
	
	return $diff;
}

//----------------------------------------------------------------------------------------
// Common point of failure is an updated wiki namespace
function xml_edits($xml, $limit = 0)
{
	// detect namespace
	
	
	$namespace = 'http://www.mediawiki.org/xml/export-0.10/';
	
	if (preg_match('/xmlns="(?<namespace>http:\/\/www.mediawiki.org\/xml\/export-(\d+(\.\d+))\/)"/U', $xml, $m))
	{
		$namespace = $m['namespace'];
	}

	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	// Add namespaces to XPath to ensure our queries work
	$xpath->registerNamespace("wiki", $namespace);
	$xpath->registerNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
	
	
	$result = new stdclass;
	$result->id = '';
	$result->creator = '';
	$result->user_id_list = array();
	$result->user_name_list = array();	
	$result->user_name_edits = array();	
	
	// edits that add or remove data
	$result->property_edits = array();
	$result->label_edits = 0;
	$result->description_edits = 0;
	
	// relative time of edit
	$result->creation = 0;
	$result->time_since = array();
	
	$result->edits = array();
	
	$revision_count = 0;
	
	foreach($xpath->query ("//wiki:page/wiki:title") as $node)
	{
		$result->id = $node->firstChild->nodeValue;
	}
	
	$nodeCollection = $xpath->query ("//wiki:revision");
	foreach($nodeCollection as $node)
	{
		$edit = new stdclass;
	
		$nc = $xpath->query ("wiki:id", $node);
		foreach ($nc as $n)
		{
			$edit->id = $n->firstChild->nodeValue;
		}
		$nc = $xpath->query ("wiki:timestamp", $node);
		foreach ($nc as $n)
		{
			$edit->time = $n->firstChild->nodeValue;
			$edit->timestamp = strtotime($n->firstChild->nodeValue);
			
			if ($result->creation == 0)
			{
				$result->creation = $edit->timestamp;
			}
			else
			{
				$result->time_since[] = $edit->timestamp - $result->creation;
			}
		}
	
		// user id
		$nc = $xpath->query ("wiki:contributor/wiki:id", $node);
		foreach ($nc as $n)
		{
			$edit->userid = $n->firstChild->nodeValue;
			if (!in_array($edit->userid, $result->user_id_list))
			{
				array_push($result->user_id_list, $edit->userid);
			}
		}
		// user IP address
		$nc = $xpath->query ("wiki:contributor/wiki:ip", $node);
		foreach ($nc as $n)
		{
			$edit->userid = $n->firstChild->nodeValue;
			if (!in_array($edit->userid, $result->user_id_list))
			{
				array_push($result->user_id_list, $edit->userid);
			}
		}
		
		// user name
		$nc = $xpath->query ("wiki:contributor/wiki:username", $node);
		foreach ($nc as $n)
		{
			$edit->username = $n->firstChild->nodeValue;
			$result->user_name_list[$edit->userid] = $edit->username;
			
			if (!isset($result->user_name_edits[$edit->username]))
			{
				$result->user_name_edits[$edit->username] = 0;
			}
			$result->user_name_edits[$edit->username]++;
			
			if ($result->creator == '')
			{
				$result->creator = $edit->username;
			}
		}
		
		// text
		$nc = $xpath->query ("wiki:text", $node);
		foreach ($nc as $n)
		{
			$edit->text = $n->firstChild->nodeValue;
		}
		
		
		// text is JSON, so we should process that here...
		
		
		$num_edits = count($result->edits);
		if ($num_edits > 1)
		{
		
			$previous_edit = json_decode($result->edits[$num_edits - 1]->text);
			$this_edit = json_decode($edit->text);
			
			$diff = compare($previous_edit, $this_edit);
			
			foreach ($diff as $k => $v)
			{
				switch ($k)
				{
					case 'descriptions':
						$result->description_edits += count($v);
						break;

					case 'labels':
						$result->label_edits += count($v);
						break;
						
					default:
						if (!isset($result->property_edits[$k]))
						{
							$result->property_edits[$k] = 0;
						}
						$result->property_edits[$k] += 1;
						break;
				
				}
			}
	
		
			// print_r($this_edit);
		}
		
		
		
		if (($limit == 0) or ($revision_count < $limit))
		{
			array_push($result->edits, $edit);
		}
		else
		{
			break;
		}
		$revision_count++;
		
	}
	
	// clean
	unset($result->edits);
	
	// print_r($result);
	
	return $result;
	
}

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


// aggregate stats

// properties
$property_edits = array();

$property_edits_per_item = array();

// time
$time_since = array();

// number of edits
$num_edits = array();

// edits by editors
$edits_by_editor = array();

// timing of edits
$edit_times = array();

foreach ($qids as $qid)
{
	$filename = 'cache/' . $qid . '.xml';
	
	if (!file_exists($filename))
	{
		$url = 'https://www.wikidata.org/wiki/Special:Export/' . $qid . '?history';
		
		//echo $url;
		
		$xml =  get($url);
		
		//echo $xml;
		
		file_put_contents($filename, $xml);
	
	}
	
	$xml = file_get_contents($filename);

	$result = xml_edits($xml);
	
	// print_r($result);
	
	// filter to only include things I've created
	
	$go = false;
	
	if ($result->creator == 'Rdmpage')
	{
		$go = true;
	}
	
	// do everything regardles sof who created it
	$go = true;
	
	if ($go)
	{
	
		// how many edit events?
		$n = count($result->time_since);
		if (!isset($num_edits[$n]))
		{
			$num_edits[$n] = 0;
		}
		$num_edits[$n] += 1;
	
	
		// which properties are edited?
		foreach ($result->property_edits as $k => $v)
		{
			if (!isset($property_edits[$k]))
			{
				$property_edits[$k] = 0;
			}
			$property_edits[$k] += $v;
	
		}
	
		// how many properties are edited?
		$n = count($result->property_edits);
		if (!isset($property_edits_per_item[$n]))
		{
			$property_edits_per_item[$n] = 0;
		}
		$property_edits_per_item[$n] += 1;
	
		// who is doing the editing?
		foreach ($result->user_name_edits as $k => $v)
		{
			if (!isset($edits_by_editor[$k]))
			{
				$edits_by_editor[$k] = 0;
			}
			$edits_by_editor[$k] += $v;
	
		}
	
		// time
		if (!isset($edit_times[$result->creation]))
		{
			$edit_times[$result->creation] =array();
		}
		$edit_times[$result->creation][] = $result->time_since;
	
	}

	

}
echo "Frequency of numbers of edits x=num edits, y=num items with this number of edits\n";

ksort($num_edits);
print_r($num_edits);

//----------------------------------------------------------------------------------------
echo "Frequency of edits of different properties\n";
asort($property_edits);
print_r($property_edits);

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

);

$data_to_export = array();
foreach ($property_edits as $p => $count)
{
	$row = array();
	
	if (isset($property_map[$p]))
	{
		$row[] = $property_map[$p];
	}
	else
	{
		$row[] = $p;
	}
	
	$row[] = $count;
	
	$data_to_export[] = join("\t", $row);

}

$output = "";
$output .=  "Property\tNumber of edits\n";
$output .=  join("\n", $data_to_export);
$output .= "\n";
file_put_contents('prop_types.tsv', $output);



//----------------------------------------------------------------------------------------
echo "Frequency of edits of properties per item (0 means no properties have been edited)\n";

ksort($property_edits_per_item);
print_r($property_edits_per_item);



$data_to_export = array();
foreach ($property_edits_per_item as $c => $count)
{
	$row = array();
	$row[] = $c;
	$row[] = $count;
	
	$data_to_export[] = join("\t", $row);

}

echo "Frequency of edits of properties per item\n";
echo join("\n", $data_to_export);
echo "\n";

$output =  '';
$output .= "Number of edits\tFrequency\n";
$output .=  join("\n", $data_to_export);
$output .= "\n";
file_put_contents('prop_freq.tsv', $output);



//----------------------------------------------------------------------------------------
echo "Frequency of edits by editor\n";

arsort($edits_by_editor);
print_r($edits_by_editor);


$data_to_export = array();
foreach ($edits_by_editor as $user => $count)
{
	$row = array();
	$row[] = $user;
	
	if (preg_match('/bot/i', $user))
	{
		$row[] = $count;
		$row[] = '-';
	}
	else
	{
		$row[] = '-';
		$row[] = $count;	
	}
	
	$data_to_export[] = join("\t", $row);

}

$output = '';
$output .= "User\tBot\tPerson\n";
$output .=  join("\n", $data_to_export);
$output .= "\n";
file_put_contents('users.tsv', $output);



//----------------------------------------------------------------------------------------
echo "History of edits\n";
ksort($edit_times);
//print_r($edit_times);



if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}



//$y0 = array_key_first ($edit_times);

//echo $y0 . "\n";

$output = '';
$output .= "Edit timestamp\tCreated timestamp\n";

foreach ($edit_times as $created => $histories)
{
	//echo $created . "\n";
	
	//print_r($histories);

	foreach ($histories as $history)
	{
		foreach ($history as $stamp)
		{
			$output .= ($created + $stamp) . "\t" . $created . "\n";
			
		}
	}
	

}

file_put_contents('timestamps.tsv', $output);












