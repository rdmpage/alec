<?php

//----------------------------------------------------------------------------------------
// Does URL exist?
function api_head($url, $userAgent = '', $content_type = '')
{
	global $config;
	
	$result = false;

	$ch = curl_init(); 
	curl_setopt ($ch, CURLOPT_URL, $url); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt ($ch, CURLOPT_HEADER,		  true);   
	
	if ($userAgent != '')
	{
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	}	
	
	if ($content_type != '')
	{
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array ("Accept: " . $content_type));
    }
    
    //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
    curl_setopt($ch, CURLOPT_NOBODY, true);
	
	$curl_result = curl_exec ($ch); 
	
	if (curl_errno ($ch) != 0 )
	{
		echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
	}
	else
	{
		$info = curl_getinfo($ch);
		
		$http_code = $info['http_code'];
		
		$result = ($http_code == 200);
	}
	return $result;
}

//----------------------------------------------------------------------------------------
// 
function api_get($url, $format='application/json')
{
	$data = '';
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: " . $format));

	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	if ($http_code == 200)
	{
		$data = $response;
	}
	
	curl_close($ch);
	
	return $data;
}

//----------------------------------------------------------------------------------------
function api_output($obj, $callback = '', $status = 400)
{
	
	// $obj may be array (e.g., for citeproc)
	if (is_array($obj))
	{
		if (isset($obj['status']))
		{
			$status = $obj['status'];
		}
	}
	
	// $obj may be object
	if (is_object($obj))
	{
		if (isset($obj->status))
		{
			$status = $obj->status;
		}
	}

	switch ($status)
	{
		case 303:
			header('HTTP/1.1 404 See Other');
			break;

		case 404:
			header('HTTP/1.1 404 Not Found');
			break;
			
		case 410:
			header('HTTP/1.1 410 Gone');
			break;
			
		case 500:
			header('HTTP/1.1 500 Internal Server Error');
			break;
			 			
		default:
			break;
	}
	
	header("Content-type: text/plain");
	header("Cache-control: max-age=3600");
	
	if ($callback != '')
	{
		echo $callback . '(';
	}
	echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	if ($callback != '')
	{
		echo ')';
	}
}



?>