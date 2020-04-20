<?php

// proxy



$url = $_GET['url'];


$callback = '';
if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}


	$ch = curl_init(); 
	curl_setopt ($ch, CURLOPT_URL, $url); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');	
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
	
	
	//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json') );
	
	$response = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	if (curl_errno ($ch) != 0 )
	{
		echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
	}
	
	
	//echo $http_code . "\n";



if ($callback != '')
{
	echo $callback . '(';
}
echo $response;
if ($callback != '')
{
	echo ')';
}




?>