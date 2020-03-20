<?php

// API to Wikidata


error_reporting(E_ALL ^ E_DEPRECATED);

require_once(dirname(__FILE__) .  '/lib.php');

require_once(dirname(__FILE__) .  '/vendor/digitalbazaar/json-ld/jsonld.php');




//----------------------------------------------------------------------------------------
// Get one work from Wikidata
// Use SPARQL CONSTRUCT then convert to CSL 
function get_work($qid, $debug = false)
{

	$uri = 'http://www.wikidata.org/entity/' . $qid;

	$sparql = 'PREFIX schema: <http://schema.org/>
PREFIX identifiers: <https://registry.identifiers.org/registry/>
PREFIX bibo: <http://purl.org/ontology/bibo/>


CONSTRUCT
{
 ?item a ?type . 
  
 ?item schema:name ?title .
  
 # basic
 ?item schema:name ?title .
 ?item schema:volumeNumber ?volume .
 ?item schema:issueNumber ?issue .
 ?item schema:pagination ?page .
 ?item schema:datePublished ?datePublished .
  
 # author(s)
  ?item schema:author ?author .
  ?author schema:name ?author_name .
  ?author schema:position ?author_order .
  ?author identifiers:orcid ?orcid .
  
 # container
  ?item schema:isPartOf ?container .
  ?container schema:name ?container_title .
  ?container schema:issn ?issn .
 
 # identifiers
 ?item identifiers:doi ?doi .
 ?item identifiers:pubmed ?pmid .
 ?item identifiers:pmc ?pmc .
 
  
 ?item bibo:handle ?handle .

 ?item schema:identifier ?biostor .
 ?item schema:identifier ?bhl .
 ?item schema:identifier ?zoobank_pub .
 ?item schema:identifier ?jstor .

# subjects
 ?item schema:about ?subject .

  
}
WHERE
{
   VALUES ?item { wd:' . $qid . ' }
  
  #?item ?p ?o .
  
  ?item wdt:P31 ?type .
  
  OPTIONAL {
   ?item wdt:P1476 ?title .
  }    
  
  # authors
 OPTIONAL {
   {
   ?item p:P2093 ?author .
    ?author ps:P2093 ?author_name .
    ?author pq:P1545 ?author_order. 
     
   }
   UNION
   {
     ?item p:P50 ?statement .
     OPTIONAL
     {
         ?statement pq:P1545 ?author_order. 
     }
     ?statement ps:P50 ?author. 
     ?author rdfs:label ?author_name .  
     FILTER (lang(?author_name) = "en")

       
     OPTIONAL
     {
       ?author wdt:P496 ?orcid .
     }
    }
  }    
    
  
  # container
  OPTIONAL {
   ?item wdt:P1433 ?container .
   ?container wdt:P1476 ?container_title .
   OPTIONAL {
     ?container wdt:P236 ?issn .
    }    
  }
  
  # date
   OPTIONAL {
   ?item wdt:P577 ?date .
   BIND(STR(?date) as ?datePublished) 
  }
  OPTIONAL {
   ?item wdt:P478 ?volume .
  }   
  
  OPTIONAL {
   ?item wdt:P433 ?issue .
  }  
  
  OPTIONAL {
   ?item wdt:P304 ?page .
  }
  
  # identifiers
   
  OPTIONAL {
   ?item wdt:P687 ?bhl_id .
   BIND(CONCAT("https://www.biodiversity.org/page/", ?bhl_id) as ?bhl)    
  }
  OPTIONAL {
    ?item wdt:P5315 ?biostor_id .
    BIND(CONCAT("https://biostor.org/reference/", ?biostor_id) as ?biostor)
  } 
  
  OPTIONAL {
   ?item wdt:P356 ?doi .
  }  
  
  OPTIONAL {
   ?item wdt:P1184 ?handle .
  }    
  
  OPTIONAL {
   ?item wdt:P888 ?jstor_id .
   BIND(CONCAT("https://www.jstor.org/stable/", ?jstor_id) as ?jstor)
   
  }
  
  OPTIONAL {
   ?item wdt:P698 ?pmid .
  }  
  
  OPTIONAL {
   ?item wdt:P932 ?pmc .
  }    
  
  OPTIONAL {
   ?item wdt:P2007 ?zoobank_pub_uuid .
   BIND(CONCAT("http://zoobank.org/References/", ?zoobank_pub_uuid) as ?zoobank_pub)
  }   
  
   OPTIONAL {
   ?item wdt:P921 ?subject_id .
    ?subject_id rdfs:label ?subject .  
  	FILTER (lang(?subject) = "en")

  }    
  
  
}   
';
	
	// Get item
	$url = 'https://query.wikidata.org/bigdata/namespace/wdq/sparql?query=' . urlencode($sparql);
	$json = get($url, 'application/ld+json');
	
	// Frame the JSON-LD to make it easier to parse
	$doc = json_decode($json);
	
	$context = (object)array(
		'@vocab' 	 	=> 'http://schema.org/',			
		'bibo' 			=> 'http://purl.org/ontology/bibo/',			
		'identifiers' 	=> 'https://registry.identifiers.org/registry/'
	);
		
	// author is always an array
	$author = new stdclass;
	$author->{'@id'} = "author";
	$author->{'@container'} = "@set";

	$context->{'author'} = $author;

	// ISSN is always an array
	$issn = new stdclass;
	$issn->{'@id'} = "issn";
	$issn->{'@container'} = "@set";

	$context->{'issn'} = $issn;
	
	// identifier
	$identifier = new stdclass;
	$identifier->{'@id'} = "identifier";
	$identifier->{'@container'} = "@set";
	
	$context->{'identifier'} = $identifier;
	
	// about
	$about = new stdclass;
	$about->{'@id'} = "about";
	$about->{'@container'} = "@set";

	$context->{'about'} = $about;
	

	

	// Find work type
	$n = count($doc);
	$type = '';
	$i = 0;
	while ($i < $n && $type == '')
	{
		if ($doc[$i]->{'@id'} == $uri)
		{
			$type =  $doc[$i]->{'@type'}[0];
		}
		$i++;
	}


	if (0)
	{
		$data = jsonld_compact($doc, $context);
	}
	else
	{

		$frame = (object)array(
				'@context' => $context,
				'@type' => $type
			);
		
		$data = jsonld_frame($doc, $frame);
		
	}
	
	if ($debug)
	{
		echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";	
	}
	
	
	// convert to CSL-JSON

	$citeproc_obj = array();
	
	$citeproc_obj['type']['id'] = $qid;

	// classify the work type
	switch ($type)
	{
		case 'http://www.wikidata.org/entity/Q13442814':
			$citeproc_obj['type'] = 'article-journal';
			break;
		
		default:
			break;		
	}

	// set default language
	$default_language = 'en';

	foreach ($data->{'@graph'}[0] as $k => $v )
	{
		switch ($k)
		{
	
			# title
			case 'name':
				if (is_array($v))
				{
					$title = '';
					if (!isset($citeproc_obj['multi']))
					{
						$citeproc_obj['multi'] = new stdclass;
						$citeproc_obj['multi']->_key = new stdclass;							
					}
					$citeproc_obj['multi']->_key->title = new stdclass;
					foreach ($v as $literal)
					{
						if ($title == '')
						{
							$title = $literal->{'@value'};
						}
					
						if ($literal->{'@language'} == $default_language)
						{
							$title = $literal->{'@value'};							
						}
					
						$citeproc_obj['multi']->_key->title->{$literal->{'@language'}} = $literal->{'@value'};
					}
				
					$citeproc_obj['title'] = $title;
				}
				else
				{
					$citeproc_obj['title'] = $v->{'@value'};
				}					
				break;
	
			# authors
			case 'author':
				$authors = array();
				
				$counter = 1;
			
				foreach ($v as $a)
				{
					if (isset($a->position))
					{
						$index = (Integer)$a->position;
					}
					else
					{
						$index = $counter;
					}
					$authors[$index] = new stdclass;
					
					if (is_object($a->name))
					{
						$authors[$index]->literal = $a->name->{'@value'};
					}
					else
					{
						$authors[$index]->literal = $a->name;
					}
					
					// ORCID?
					if (isset($a->{'identifiers:orcid'}))
					{
						$authors[$index]->ORCID = 'https://orcid.org/' . $a->{'identifiers:orcid'};
					}
					
					// Wikidata?
					if (preg_match('/https?:\/\/www.wikidata.org\/entity\/(?<id>Q\d+)/', $a->{'@id'}, $m))
					{
						$authors[$index]->WIKIDATA = $m['id'];
					}
					
					$counter++;
				}
			
				// Just want a simple array of author objects
				ksort($authors);
			
				$citeproc_obj['author'] = array_values($authors);
		
				break;
			
			# container
			case 'isPartOf':
				if (isset($v->issn))
				{
					$citeproc_obj['ISSN'] = $v->issn;
				}			
			
				if (is_array($v->name))
				{
					$title = '';
					if (!isset($citeproc_obj['multi']))
					{
						$citeproc_obj['multi'] = new stdclass;
						$citeproc_obj['multi']->_key = new stdclass;							
					}
					$citeproc_obj['multi']->_key->{'container-title'} = new stdclass;
					foreach ($v->name as $literal)
					{
						if ($title == '')
						{
							$title = $literal->{'@value'};
						}
					
						if ($literal->{'@language'} == $default_language)
						{
							$title = $literal->{'@value'};							
						}
					
						$citeproc_obj['multi']->_key->{'container-title'}->{$literal->{'@language'}} = $literal->{'@value'};
					}
				
					$citeproc_obj['container-title'] = $title;
				}
				else
				{
					$citeproc_obj['container-title'] = $v->name->{'@value'};
				}					
				break;					
	
			# date
			case 'datePublished':
				$v = preg_replace('/^\+/', '', $v);
				$v = preg_replace('/T.*$/', '', $v);
			
				$parts = explode('-', $v);
			
				$citeproc_obj['issued'] = new stdclass;
				$citeproc_obj['issued']->{'date-parts'} = array();
				$citeproc_obj['issued']->{'date-parts'}[0] = array();

				$citeproc_obj['issued']->{'date-parts'}[0][] = (Integer)$parts[0];

				if ($parts[1] != '00')
				{		
					$citeproc_obj['issued']->{'date-parts'}[0][] = (Integer)$parts[1];
				}

				if ($parts[2] != '00')
				{		
					$citeproc_obj['issued']->{'date-parts'}[0][] = (Integer)$parts[2];
				}
				break;
					
			# location
			case 'volumeNumber':
				$citeproc_obj['volume'] = $v;
				break;
			
			case 'issueNumber':
				$citeproc_obj['issue'] = $v;
				break;
			
			case 'pagination':
				$citeproc_obj['page'] = $v;
				break;

			# identifiers
			case 'identifiers:doi':
				$citeproc_obj['DOI'] = $v;
				break;

			case 'identifiers:pubmed':
				$citeproc_obj['PMID'] = $v;
				break;			

			case 'identifiers:pmc':
				$citeproc_obj['PMCID'] = $v;
				break;	
				
			case 'identifier':
				$citeproc_obj['alternative-id'] = array();
				foreach ($v as $identifier)
				{
					$citeproc_obj['alternative-id'][] = $identifier;
				}
				break;
				
			case 'about':
				$citeproc_obj['subject'] = array();
				foreach ($v as $about)
				{
					$citeproc_obj['subject'][] = $about->{'@value'};
				}
				break;
				
			
			# URL(s)	
		
			# PDF	
	
			default:
				break;
		}
	}


	return $citeproc_obj;


}		



if (0)
{
	$qid = 'Q56097590';

	//$qid = 'Q47164672'; // Chinese

	$qid = 'Q21283951'; // DOI, PMID, PMC
	
	//$qid = 'Q19689597';
	
	// $qid = 'Q21189593';
	
	$qid = 'Q58677102';
	
	//$qid = 'Q19689597';
	
	$citeproc_obj = get_work($qid, true);


	//print_r($citeproc_obj);

	$csljson = json_encode($citeproc_obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);	
	echo $csljson;


}

		
?>
