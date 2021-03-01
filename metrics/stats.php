<?php

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
	
	print_r($result);
	
	return $result;
	
}

$qids = array(
'Q56886408',
'Q95996254',
'Q28938179',
'Q96688631',
'Q100356537',
'Q100708851',
'Q101140164',
'Q101147277',
'Q99966938',
'Q101391426',
'Q104052472',
'Q101391492',
'Q99838137',
'Q105118008',
'Q28300019',
'Q59679048',
'Q101392012',

'Q101391461',
'Q100968633',
'Q104713977',
'Q99617476',
'Q100709441',
'Q95996238',
'Q104785280',
'Q96107549',
'Q103980684',
'Q100641678',
'Q100828592',
);

$qids=array("Q30039764",
"Q30040321",
"Q30040729",
"Q30047200",
"Q30047512",
"Q30047902",
"Q30048790",
"Q30048995",
"Q30050212",
"Q30051250",
"Q30051465",
"Q30052070",
"Q30052228",
"Q30053449",
"Q30053507",
"Q30054637",
"Q30092476",
"Q30495022",
"Q30586722",
"Q30617719",
"Q30654389",
"Q30804348",
"Q30831729",
"Q30837860",
"Q30917886",
"Q30920760",
"Q30938542",
"Q30944266",
"Q30951923",
"Q30976549",
"Q30982071",
"Q30982437",
"Q30989906",
"Q30996156",
"Q31021785",
"Q31032834",
"Q31048071",
"Q31056101",
"Q31152493",
"Q31158687",
"Q31835658",
"Q32286633",
"Q32286689",
"Q32286739",
"Q32286971",
"Q32288185",
"Q32288221",
"Q32288579",
"Q32288623",
"Q32288670",
"Q32288704",
"Q32288886",
"Q32562611",
"Q33105620",
"Q33105623",
"Q33105627",
"Q33105631",
"Q33105632",
"Q33105640",
"Q33105641",
"Q33105644",
"Q33105646",
"Q33105648",
"Q33105651",
"Q33105654",
"Q33105655",
"Q33105658",
"Q33105659",
"Q33105664",
"Q33105667",
"Q33105673",
"Q33105682",
"Q33105686",
"Q33105699",
"Q33105703",
"Q33105707",
"Q33105708",
"Q33105712",
"Q33105714",
"Q33105718",
"Q33105723",
"Q33105725",
"Q33105727",
"Q33105728",
"Q33105729",
"Q33105732",
"Q33105735",
"Q33105737",
"Q33105738",
"Q33105739",
"Q33105741",
"Q33105743",
"Q33105749",
"Q33105753",
"Q33105758",
"Q33105760",
"Q33105767",
"Q33105770",
"Q33105775",
"Q33105787",);


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
	
	$xml = file_get_contents($filename);

	$result = xml_edits($xml);
	
	print_r($result);
	
	// filter to only include things I've created
	
	$go = false;
	
	if ($result->creator == 'Rdmpage')
	{
		$go = true;
	}
	
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

echo "Frequency of edits of different properties\n";
asort($property_edits);
print_r($property_edits);

echo "Frequency of edits of properties per item (0 means no properties have been edited)\n";

ksort($property_edits_per_item);
print_r($property_edits_per_item);


echo "Frequency of edits by editor\n";

asort($edits_by_editor);
print_r($edits_by_editor);


echo "History of edits\n";
ksort($edit_times);
//print_r($edit_times);


// data to plot

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

foreach ($edit_times as $created => $histories)
{
	//echo $created . "\n";
	
	//print_r($histories);

	foreach ($histories as $history)
	{
		foreach ($history as $stamp)
		{
			echo ($created + $stamp) . "\t" . $created . "\n";
			
		}
	}
	

}












