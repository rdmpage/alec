<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/api_utils.php');
require_once (dirname(__FILE__) . '/wikidata_api.php');

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
	
	$obj = get_work($id);

	
	if ($obj != '')
	{
		/*
		if ($format == 'citeproc')
		{
			if (isset($obj->_source->search_result_data->csl))
			{
				$obj = $obj->_source->search_result_data->csl;
			}
		}
		*/		
		
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
		//$obj = do_search($q);	
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