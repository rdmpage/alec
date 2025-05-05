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
$site = 'heroku';

switch ($site)
{
	case 'heroku':
		$config['web_root']		= '/';
		$config['site_name'] 	= 'ALEC';
		break;	

	case 'local':
	default:
		$config['web_root']		= '/alec/';
		$config['site_name'] 	= 'ALEC';
		break;
}

$config['sparql_endpoint'] 				= 'https://query.wikidata.org/bigdata/namespace/wdq/sparql';

// Wikidata graph split
$config['sparql_endpoint'] 				= 'https://query-main.wikidata.org/bigdata/namespace/wdq/sparql';
$config['sparql_scholarly_endpoint'] 	= 'https://query-scholarly.wikidata.org/bigdata/namespace/wdq/sparql';

?>
