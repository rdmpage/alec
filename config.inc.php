<?php

/**
 * @file config.inc.php
 *
 * Global configuration variables (may be added to by other modules).
 *
 */

global $config;

// Date timezone
date_default_timezone_set('UTC');


$site = 'local';
//$site = 'heroku';

switch ($site)
{
	case 'heroku':
		$config['web_root']		= '/';
		$config['site_name'] 	= 'ALEC';
		break;	

	case 'local':
	default:
		$config['web_root']		= '/~rpage/alec/';
		$config['site_name'] 	= 'ALEC';
		break;
}

$config['sparql_endpoint'] 	= 'https://query.wikidata.org/bigdata/namespace/wdq/sparql';



?>
