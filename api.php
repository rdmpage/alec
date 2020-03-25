<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/api_utils.php');
require_once (dirname(__FILE__) . '/wikidata_api.php');
require_once (dirname(__FILE__) . '/wikidata_search.php');

// require_once (dirname(__FILE__) . '/search.php');


//--------------------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}

//--------------------------------------------------------------------------------------------------
// URL (e.g., PDF) exists
function display_head ($url, $callback)
{
	$obj = new stdclass;
	$obj->url = $url;
	$obj->found = false;

	$status = 404;
	
	if (api_head($url))
	{
		$status = 200;
		$obj->found = true;
	}
		
	api_output($obj, $callback, $status);
}	
	
//--------------------------------------------------------------------------------------------------
// One record
function display_one ($id, $format= '', $callback = '')
{

	$obj = null;
	$status = 404;

	$obj = get_item($id);
	
	//$obj = json_decode('{ "@context": { "@vocab": "http://schema.org/", "bibo": "http://purl.org/ontology/bibo/", "identifiers": "https://registry.identifiers.org/registry/", "author": { "@id": "author", "@container": "@set" }, "issn": { "@id": "issn", "@container": "@set" }, "identifier": { "@id": "identifier", "@container": "@set" }, "about": { "@id": "about", "@container": "@set" } }, "@graph": [ { "@id": "http://www.wikidata.org/entity/Q50090145", "@type": "http://www.wikidata.org/entity/Q13442814", "author": [ { "@id": "http://www.wikidata.org/entity/statement/Q50090145-0777CDAC-37F7-4B64-8DBC-701A0FAE2F6F", "name": "H A Ten Hove", "position": "1" }, { "@id": "http://www.wikidata.org/entity/statement/Q50090145-D5E8B126-4C5A-469A-8AAC-E8980BFDF1E9", "name": "J C A Weerdenburg", "position": "2" } ], "datePublished": "1978-02-01T00:00:00Z", "identifier": [ "https://biostor.org/reference/160599", "https://www.biodiversity.org/page/1569425", "https://www.jstor.org/stable/1540777" ], "isPartOf": { "@id": "http://www.wikidata.org/entity/Q1954904", "issn": [ "1939-8697", "0006-3185" ], "name": { "@language": "en", "@value": "Biological Bulletin" } }, "issueNumber": "1", "name": { "@language": "en", "@value": "A GENERIC REVISION OF THE BRACKISH-WATER SERPULID FICOPOMATUS SOUTHERN 1921 (POLYCHAETA: SERPULINAE), INCLUDING MERCIERELLA FAUVEL 1923, SPHAEROPOMATUS TREADWELL 1934, MERCIERELLOPSIS RIOJA 1945 AND NEOPOMATUS PILLAI 1960." }, "pagination": "96-120", "volumeNumber": "154", "identifiers:doi": "10.2307/1540777", "identifiers:pubmed": "29323962" } ] }');
	
	if ($obj != '')
	{
		
		$status = 200;
	}
		
	api_output($obj, $callback, $status);
}	


//--------------------------------------------------------------------------------------------------
// Search
function display_search ($q, $callback = '')
{	
	$status = 404;
				
	if ($q == '')
	{
		$obj = new stdclass;
		
		$status = 200;
	}
	else
	{	
		$status = 200;
		$obj = wikidata_search($q);	
	}
	
	api_output($obj, $callback, 200);
}


//--------------------------------------------------------------------------------------------------
function main()
{
	global $config;

	$callback = '';
	$handled = false;
	
	
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
	
	if (isset($_GET['callback']))
	{	
		$callback = $_GET['callback'];
	}
	
	// Submit job
	if (!$handled)
	{
		if (isset($_GET['id']))
		{	
			$id = $_GET['id'];
			
			$format = '';
			
			if (isset($_GET['format']))
			{
				$format = $_GET['format'];
			}			
			
			if (!$handled)
			{
				display_one($id, $format, $callback);
				$handled = true;
			}
			
		}
	}
	
	if (!$handled)
	{
		if (isset($_GET['pdf']))
		{	
			$pdf = $_GET['pdf'];
			
			display_head($pdf, $callback);
			$handled = true;
		}
	}
	
	
	if (!$handled)
	{
		if (isset($_GET['q']))
		{	
			$q = $_GET['q'];
			
			// Elastic
			$from = 0;
			$size = 10;
			
			$filter = null;
			
			display_search($q, $callback);
			
			$handled = true;
		}
			
	}
	
	if (!$handled)
	{
		default_display();
	}

}


main();

?>