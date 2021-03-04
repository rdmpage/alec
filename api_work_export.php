<?php

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/wikidata_api.php');


$id = 'Q54800926';
$id = 'Q89202623';

$id = 'Q29469527';
$id = 'Q35800184';

$id = 'Q89665527'; // Chinese text

//$id = 'Q30582014'; // ORCIDs

//$id = 'Q94499626'; // book

$id = 'Q99572444';

if (isset($_REQUEST['id']))
{
	$id = $_REQUEST['id'];
}

$callback = '';
if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}


//----------------------------------------------------------------------------------------
// Get one item from Wikidata
function get_work($qid, $debug = false)
{
	global $config;	
	
	$data = null;

	$uri = 'http://www.wikidata.org/entity/' . $qid;

	$sparql = 'PREFIX schema: <http://schema.org/>
PREFIX bibo: <http://purl.org/ontology/bibo/>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

CONSTRUCT
{
 ?item a ?type . 
  
 ?item schema:name ?title .
 
 ?item schema:url ?url .
  
 # scholarly article
 ?item schema:name ?title .
 ?item schema:volumeNumber ?volume .
 ?item schema:issueNumber ?issue .
 ?item schema:pagination ?page .
 ?item schema:datePublished ?datePublished .
  ?item schema:abstract ?abstract . # pending https://schema.org/abstract
  
 # author(s)
  ?item schema:author ?author .
  ?author schema:name ?author_name .
  ?author schema:position ?author_order .
  
  ?author schema:identifier ?orcid_author_identifier .
 ?orcid_author_identifier a <http://schema.org/PropertyValue> .
 ?orcid_author_identifier <http://schema.org/propertyID> "orcid" .
 ?orcid_author_identifier <http://schema.org/value> ?orcid .
  
  
  
 # container
  ?item schema:isPartOf ?container .
  ?container schema:name ?container_title .
  ?container schema:issn ?issn .
  
  
  # person
  ?item schema:description ?description .
  ?item schema:alternateName ?alternateName .
  ?item schema:birthDate ?birthDate .
  ?item schema:deathDate ?deathDate .
 # identifiers as property values
 
 # bhl
 ?item schema:identifier ?bhl_page_identifier .
 ?bhl_page_identifier a <http://schema.org/PropertyValue> .
 ?bhl_page_identifier <http://schema.org/propertyID> "bhl page" .
 ?bhl_page_identifier <http://schema.org/value> ?bhl_page .

 # biostor
 ?item schema:identifier ?biostor_identifier .
 ?biostor_identifier a <http://schema.org/PropertyValue> .
 ?biostor_identifier <http://schema.org/propertyID> "biostor" .
 ?biostor_identifier <http://schema.org/value> ?biostor .

 # doi
 ?item schema:identifier ?doi_identifier .
 ?doi_identifier a <http://schema.org/PropertyValue> .
 ?doi_identifier <http://schema.org/propertyID> "doi" .
 ?doi_identifier <http://schema.org/value> ?doi .

 # handle
 ?item schema:identifier ?handle_identifier .
 ?handle_identifier a <http://schema.org/PropertyValue> .
 ?handle_identifier <http://schema.org/propertyID> "handle" .
 ?handle_identifier <http://schema.org/value> ?handle .

 # internetarchive
 ?item schema:identifier ?internetarchive_identifier .
 ?internetarchive_identifier a <http://schema.org/PropertyValue> .
 ?internetarchive_identifier <http://schema.org/propertyID> "internetarchive" .
 ?internetarchive_identifier <http://schema.org/value> ?internetarchive .
 
 # IPNI author
 ?item schema:identifier ?ipni_author_identifier .
 ?ipni_author_identifier a <http://schema.org/PropertyValue> .
 ?ipni_author_identifier <http://schema.org/propertyID> "ipni_author" .
 ?ipni_author_identifier <http://schema.org/value> ?ipni_author .
 

 # jstor
 ?item schema:identifier ?jstor_identifier .
 ?jstor_identifier a <http://schema.org/PropertyValue> .
 ?jstor_identifier <http://schema.org/propertyID> "jstor" .
 ?jstor_identifier <http://schema.org/value> ?jstor .
 
 # ORCID
 ?item schema:identifier ?orcid_identifier .
 ?orcid_identifier a <http://schema.org/PropertyValue> .
 ?orcid_identifier <http://schema.org/propertyID> "orcid" .
 ?orcid_identifier <http://schema.org/value> ?orcid .
 
 # pmc
 ?item schema:identifier ?pmc_identifier .
 ?pmc_identifier a <http://schema.org/PropertyValue> .
 ?pmc_identifier <http://schema.org/propertyID> "pmc" .
 ?pmc_identifier <http://schema.org/value> ?pmc .

 # pmid
 ?item schema:identifier ?pmid_identifier .
 ?pmid_identifier a <http://schema.org/PropertyValue> .
 ?pmid_identifier <http://schema.org/propertyID> "pmid" .
 ?pmid_identifier <http://schema.org/value> ?pmid .
 
 # CNKI
 ?item schema:identifier ?cnki_identifier .
 ?cnki_identifier a <http://schema.org/PropertyValue> .
 ?cnki_identifier <http://schema.org/propertyID> "cnki" .
 ?cnki_identifier <http://schema.org/value> ?cnki . 
 
 # ResearchGate
 ?item schema:identifier ?rg_author_identifier .
 ?rg_author_identifier a <http://schema.org/PropertyValue> .
 ?rg_author_identifier <http://schema.org/propertyID> "researchgate author" .
 ?rg_author_identifier <http://schema.org/value> ?rg_author .
 
 
 # VIAF
 ?item schema:identifier ?viaf_identifier .
 ?viaf_identifier a <http://schema.org/PropertyValue> .
 ?viaf_identifier <http://schema.org/propertyID> "viaf" .
 ?viaf_identifier <http://schema.org/value> ?viaf .
 

 # zoobank author
 ?item schema:identifier ?zoobank_author_identifier .
 ?zoobank_author_identifier a <http://schema.org/PropertyValue> .
 ?zoobank_author_identifier <http://schema.org/propertyID> "zoobank_author" .
 ?zoobank_author_identifier <http://schema.org/value> ?zoobank_author_uuid .


 # zoobank publication
 ?item schema:identifier ?zoobank_pub_identifier .
 ?zoobank_pub_identifier a <http://schema.org/PropertyValue> .
 ?zoobank_pub_identifier <http://schema.org/propertyID> "zoobank_pub" .
 ?zoobank_pub_identifier <http://schema.org/value> ?zoobank_pub_uuid .


 # persee
 ?item schema:identifier ?persee_identifier .
 ?persee_identifier a <http://schema.org/PropertyValue> .
 ?persee_identifier <http://schema.org/propertyID> "persee" .
 ?persee_identifier <http://schema.org/value> ?persee .

 
 # Book identifiers
  
  # Google Books
 ?item schema:identifier ?googlebooks_identifier .
 ?googlebooks_identifier a <http://schema.org/PropertyValue> .
 ?googlebooks_identifier <http://schema.org/propertyID> "google books" .
 ?googlebooks_identifier <http://schema.org/value> ?googlebooks .     
  
  # ISBNs
  ?item schema:isbn ?isbn13 .
  ?item schema:isbn ?isbn10 .

# licensing
 ?item schema:license ?license_url .
 
	# PDF
	?item schema:encoding ?encoding .
	?encoding schema:fileFormat "application/pdf" .
	?encoding schema:contentUrl ?citation_pdf_url .
 


}
WHERE
{
   VALUES ?item { wd:' . $qid . ' }
  
  #?item ?p ?o .
  
  ?item wdt:P31 ?type .
  
  
   ?item wdt:P1476 ?title .
     

  
  
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
  		 BIND( IRI (CONCAT (STR(?author), "#orcid")) as ?orcid_author_identifier)
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
  
  # people -------------------------------------------------------------------------------
  
  OPTIONAL {
   ?item schema:description ?description .
   # filter languages otherwise we can be inundated
  FILTER(
       LANG(?description) = "en" 
  	|| LANG(?description) = "fr" 
  	|| LANG(?description) = "de" 
  	|| LANG(?description) = "es" 
  	|| LANG(?description) = "zh"
  	)
   }  
  
   OPTIONAL {
   ?item skos:altLabel ?alternateName .
  # filter languages otherwise we can be inundated
  FILTER(
       LANG(?alternateName) = "en" 
  	|| LANG(?alternateName) = "fr" 
  	|| LANG(?alternateName) = "de" 
  	|| LANG(?alternateName) = "es" 
  	|| LANG(?alternateName) = "zh"
  	)   
   }   
  

  # scholarly articles -------------------------------------------------------------------
  OPTIONAL {
   ?item wdt:P478 ?volume .
  }   
  
  OPTIONAL {
   ?item wdt:P433 ?issue .
  }  
  
  OPTIONAL {
   ?item wdt:P304 ?page .
  }
  
  # first line as proxy for abstract
  OPTIONAL {
   ?item wdt:P1922 ?abstract .
  }  
  
  # full text
   OPTIONAL {
   ?item wdt:P953 ?url .  
  }

  
  # identifiers --------------------------------------------------------------------------
    
  
  # identifiers as property value pairs
  
 OPTIONAL {
   ?item wdt:P687 ?bhl_page .   
   BIND( IRI (CONCAT (STR(?item), "#bhl_page")) as ?bhl_page_identifier)
  }    
  
 OPTIONAL {
   ?item wdt:P5315 ?biostor .   
   BIND( IRI (CONCAT (STR(?item), "#biostor")) as ?biostor_identifier)
  }   
  
  # Make DOI lowercase
 OPTIONAL {
   ?item wdt:P356 ?doi_string .   
   BIND( IRI (CONCAT (STR(?item), "#doi")) as ?doi_identifier)
   BIND( LCASE(?doi_string) as ?doi)
  }    
  
 OPTIONAL {
   ?item wdt:P1184 ?handle .   
   BIND( IRI (CONCAT (STR(?item), "#handle")) as ?handle_identifier)
  } 

 OPTIONAL {
   ?item wdt:P888 ?jstor .   
   BIND( IRI (CONCAT (STR(?item), "#jstor")) as ?jstor_identifier)
  } 
  

  OPTIONAL {
   ?item wdt:P724 ?internetarchive .   
   BIND( IRI (CONCAT (STR(?item), "#internetarchive")) as ?internetarchive_identifier)
  }  
  
 OPTIONAL {
   ?item wdt:P6769 ?cnki .   
   BIND( IRI (CONCAT (STR(?item), "#cnki")) as ?cnki_identifier)
  } 
  
  
  # people
  
 OPTIONAL {
   ?item wdt:P586 ?ipni_author .   
   BIND( IRI (CONCAT (STR(?item), "#ipni_author")) as ?ipni_author_identifier)
  } 
  
 OPTIONAL {
   ?item wdt:P496 ?orcid .   
   BIND( IRI (CONCAT (STR(?item), "#orcid")) as ?orcid_identifier)
  }   
  
  OPTIONAL {
   ?item wdt:P698 ?pmid .   
   BIND( IRI (CONCAT (STR(?item), "#pmid")) as ?pmid_identifier)
  } 
  
  OPTIONAL {
   ?item wdt:P932 ?pmc .   
   BIND( IRI (CONCAT (STR(?item), "#pmc")) as ?pmc_identifier)
  }        
  
 OPTIONAL {
   ?item wdt:P2038 ?rg_author .   
   BIND( IRI (CONCAT (STR(?item), "#rg_author")) as ?rg_author_identifier)
  }   
  

  
 OPTIONAL {
   ?item wdt:P214 ?viaf .   
   BIND( IRI (CONCAT (STR(?item), "#viaf")) as ?viaf_identifier)
  }   
  

 OPTIONAL {
   ?item wdt:P2006 ?zoobank_author_uuid .   
   BIND( IRI (CONCAT (STR(?item), "#zoobank_author")) as ?zoobank_author_identifier)
  }   

  
 OPTIONAL {
   ?item wdt:P2007 ?zoobank_pub_uuid .   
   BIND( IRI (CONCAT (STR(?item), "#zoobank_pub")) as ?zoobank_pub_identifier)
  }  
  
 OPTIONAL {
   ?item wdt:P6944 ?bloodhound .   
   BIND( IRI (CONCAT (STR(?item), "#bloodhound")) as ?bloodhound_identifier)
  }   
  
  
 OPTIONAL {
   ?item wdt:P2732 ?persee .   
   BIND( IRI (CONCAT (STR(?item), "#persee")) as ?persee_identifier)
  }        
  

  
  # Book identifiers
  
  # Google Books
 OPTIONAL {
   ?item wdt:P675 ?googlebooks .   
   BIND( IRI (CONCAT (STR(?item), "#googlebooks")) as ?googlebooks_identifier)
  }        
    
    # ISBN
 OPTIONAL {
   ?item wdt:P212 ?isbn13 .   
  }  
 OPTIONAL {
   ?item wdt:P957 ?isbn10 .   
  }        
  
  # license
  OPTIONAL {
   ?item wdt:P275 ?license .  
   ?license wdt:P856 ?license_url .  
  }   
  
    # periodical
  OPTIONAL {
   ?item wdt:P236 ?issn .  
  }   

 
    
 OPTIONAL {
   ?item wdt:P856 ?url .  
  }   
 
 
     
 OPTIONAL {
   ?species_wiki schema:about ?item; 
   	schema:isPartOf <https://species.wikimedia.org/>;
   
  } 
  
   # full text as PDF we can view
  OPTIONAL {
    {
  		# Wayback machine
  		?item p:P953 ?encoding .
  		?encoding ps:P953 ?fulltext_url . # URL
  		?encoding pq:P2701 wd:Q42332 . # PDF
  		?encoding pq:P1065 ?citation_pdf_url . # Archive URL
  	}
  	UNION
  	{
  		# Internet Archive
  		?item wdt:P724 ?archive .
  		?item p:P724 ?encoding .
  		BIND( IRI(CONCAT("https://archive.org/download/", ?archive, "/", $archive, ".pdf")) as ?citation_pdf_url)

  	}
  } 
}   
';
	
	// Get item
	$url = 'https://query.wikidata.org/bigdata/namespace/wdq/sparql?query=' . urlencode($sparql);
	
	if (0)
	{
		$json = get(
			$config['sparql_endpoint']. '?query=' . urlencode($sparql), 
			'application/ld+json'
		);
	}
	else
	{
		$json = post(
			$config['sparql_endpoint'], 
			'application/ld+json',
			'query=' . $sparql
		);
	}	
		
	/*
	//$json = get($url, 'application/ld+json');
	$json = post(
		'https://query.wikidata.org/bigdata/namespace/wdq/sparql', 
		'application/ld+json',
		'query=' . $sparql
		);
		*/
	
	if (0)
	{
		echo '<pre>';
		echo $json;
		echo '</pre>';
	}
	
	// Frame the JSON-LD to make it easier to parse
	$doc = json_decode($json);
		
	$context = (object)array(
		'@vocab' 	 	=> 'http://schema.org/'			
		//'bibo' 			=> 'http://purl.org/ontology/bibo/',			
		//'identifiers' 	=> 'https://registry.identifiers.org/registry/'
		//'wd' 			=> 'http://www.wikidata.org/entity/'
	);
		
	// author is always an array
	$author = new stdclass;
	$author->{'@id'} = "author";
	//$author->{'@author'} = "@set";
	$author->{'@author'} = "@list"; 

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

	// ISSN
	$issn = new stdclass;
	$issn->{'@id'} = "issn";
	$issn->{'@container'} = "@set";
	
	$context->{'issn'} = $issn;


	// license
	$license = new stdclass;
	$license->{'@id'} = "license";
	$license->{'@type'} = "@id";
	
	$context->{'license'} = $license;
	

	// sameAs
	$sameas = new stdclass;
	$sameas->{'@id'} = "sameAs";
	$sameas->{'@type'} = "@id";
	$sameas->{'@container'} = "@set";
	
	$context->{'sameAs'} = $sameas;
	
	
	
	// url
	$url = new stdclass;
	$url->{'@id'} = "url";
	$url->{'@type'} = "@id";
	$url->{'@container'} = "@set";
	
	$context->{'url'} = $url;
	

	// publisher
	$publisher = new stdclass;
	$publisher->{'@id'} = "publisher";
	$publisher->{'@type'} = "@id";
	$publisher->{'@container'} = "@set";
	
	$context->{'publisher'} = $publisher;
	
	
		// encoding
		$encoding = new stdclass;
		$encoding->{'@id'} = "encoding";
		$encoding->{'@container'} = "@set";
		$context->{'encoding'} = $encoding;

	
	// contentUrl
	$context->contentUrl = new stdclass;
	$context->contentUrl->{'@type'} = '@id';
	$context->contentUrl->{'@id'} = 'contentUrl';
	

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
		
		/*
		echo '<pre>';
		print_r($data);
		echo '</pre>';
		*/
		
		
		// OK, RDF doesn't have a notion of order,
		// and for this app I want authors in the correct order. So, SPARQL stores 
		// order in "schema:positoon" so we use that to build an ordered array.
		
		if (isset($data->{'@graph'}[0]->author))
		{
			$author_list = array();
			
			if (is_object($data->{'@graph'}[0]->author))
			{
				// one author
				unset($author->position);
				$author_list[] = $data->{'@graph'}[0]->author;			
			}
			else
			{
				// array of authors
				foreach ($data->{'@graph'}[0]->author as $author_item)
				{
					if (isset($author_item->position))
					{
						$index = (Integer)$author_item->position;
						unset($author_item->position);
						$author_list[$index] = $author_item;
					}
			
				}
				ksort($author_list, SORT_NUMERIC);
			}
	
			$data->{'@graph'}[0]->author = array_values($author_list);
		}
		
	}
	
	if ($debug)
	{
		echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";	
	}
	
	return $data;
}


//----------------------------------------------------------------------------------------
// Item as JSON-LD object
function item_to_csl($data, $debug = true)
{
	
	$id = $data->{'@graph'}[0]->{'@id'};
	$qid = str_replace('http://www.wikidata.org/entity/', '', $id);
	
	$type = $data->{'@graph'}[0]->{'@type'};
	
	
	// convert to CSL-JSON

	$citeproc_obj = array();
	
	$citeproc_obj['id'] = $qid;

	// classify the work type
	switch ($type)
	{
		case 'http://www.wikidata.org/entity/Q13442814':
		case 'http://www.wikidata.org/entity/Q18918145':
		case 'http://www.wikidata.org/entity/Q191067':
			$citeproc_obj['type'] = 'article-journal';
			break;

	  case 'http://www.wikidata.org/entity/Q47461344': 	// written work
	  case 'http://www.wikidata.org/entity/Q571':		// book
	  case 'http://www.wikidata.org/entity/Q3331189': 	// version, edition, or translation
			$citeproc_obj['type'] = 'book';
			break;

		
		default:
			$citeproc_obj['type'] = 'unknown';
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
					
					// identifiers
					if (isset($a->identifier))
					{
						foreach ($a->identifier as $identifier)
						{
							if (is_object($identifier))
							{
								switch ($identifier->propertyID)
								{
									case 'orcid':
										$authors[$index]->ORCID = 'https://orcid.org/' . $identifier->value;
										break;

									default:
										break;
								}					
							}
						}					
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
				
			case 'isbn':
				$citeproc_obj['ISBN'] = $v;
				break;
				
			case 'identifier':
				foreach ($v as $identifier)
				{
					if (is_object($identifier))
					{
						switch ($identifier->propertyID)
						{
							case 'doi':
								$citeproc_obj['DOI'] = $identifier->value;
								break;
								
							case 'handle':
								$citeproc_obj['HANDLE'] = $identifier->value;
								break;

							case 'pmid':
								$citeproc_obj['PMID'] = $identifier->value;
								break;

							case 'pmc':
								$citeproc_obj['PMCID'] = 'PMC' . $identifier->value;
								break;

							case 'zoobank_pub':
								$citeproc_obj['ZOOBANK'] = $identifier->value;
								break;
								
							default:
								if (!isset($citeproc_obj['alternative-id']))
								{
									$citeproc_obj['alternative-id'] = array();
								}
								$citeproc_obj['alternative-id'][] = $identifier->value;
								break;
						}					
					}
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
			case 'encoding':
				$citeproc_obj['PDF'] = array();
				foreach ($v as $pdf)
				{
					$citeproc_obj['PDF'][] = $pdf->contentUrl;
				}
				break;
	
			default:
				break;
		}
	}


	return $citeproc_obj;


}	


$debug = false;
//$debug = true;

$citeproc_obj = new stdclass;

$item = get_work($id, $debug);

if ($item)
{
	$citeproc_obj = item_to_csl($item);
}


header("Content-type: application/json");

if ($callback != '')
{
	echo $callback . '(';
}


$csljson = json_encode($citeproc_obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);	
echo $csljson;

if ($callback != '')
{
	echo ')';
}




?>
