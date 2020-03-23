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

/*
$site = 'local';

switch ($site)
{

	case 'local':
	default:
		// Server-------------------------------------------------------------------------
		$config['web_server']	= 'http://localhost'; 
		$config['site_name']	= 'LOIS';

		// Files--------------------------------------------------------------------------
		$config['web_dir']		= dirname(__FILE__);
		$config['web_root']		= '/~rpage/lois-kg/www/';
		break;
}
*/

$config['sparql_endpoint'] 	= 'https://query.wikidata.org/bigdata/namespace/wdq/sparql';



?>
