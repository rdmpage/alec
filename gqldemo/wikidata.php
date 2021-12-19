<?php

require_once (dirname(dirname(__FILE__)) . '/vendor/autoload.php');
require_once (dirname(__FILE__) . '/utils.php');

use ML\JsonLD\JsonLD;


$config = array();
$config['sparql_endpoint'] = 'https://query.wikidata.org/bigdata/namespace/wdq/sparql';
$config['hack_uri']	= "http://example.com/";

// JSON-LD context
$context = new stdclass;
$context->{'@vocab'} = 'http://schema.org/';
$context->gql = $config['hack_uri'];

$context->wd = 'http://www.wikidata.org/entity/';

/*
// This is one way to handle multilingual strings

// English
$context->name_en = new stdclass;
$context->name_en->{'@id'} = 'name';
$context->name_en->{'@language'} = 'en';

// German
$context->name_de = new stdclass;
$context->name_de->{'@id'} = 'name';
$context->name_de->{'@language'} = 'de';
*/

// id
$context->id = '@id';

// type
$context->type = '@type';	


// author is always an array
$author = new stdclass;
$author->{'@id'} = "author";
$author->{'@container'} = "@set";
$context->author = $author;

// identifier is always an array
$identifier = new stdclass;
$identifier->{'@id'} = "identifier";
$identifier->{'@type'} = "@id";
$identifier->{'@container'} = "@set";
$context->identifier = $identifier;

// ISSN is always an array
$issn = new stdclass;
$issn->{'@id'} = "issn";
$issn->{'@container'} = "@set";
$context->{'issn'} = $issn;

// ISBN is always an array
$isbn = new stdclass;
$isbn->{'@id'} = "isbn";
$isbn->{'@container'} = "@set";
$context->{'isbn'} = $isbn;



// Always an array
$successorOf = new stdclass;
$successorOf->{'@id'} = "successorOf";
$successorOf->{'@container'} = "@set";
$context->{'successorOf'} = $successorOf;

$predecessorOf = new stdclass;
$predecessorOf->{'@id'} = "predecessorOf";
$predecessorOf->{'@container'} = "@set";
$context->{'predecessorOf'} = $predecessorOf;


$context->bibo = 'http://purl.org/ontology/bibo/';
$context->doi		= "bibo:doi";	


$context->zoobank	= "http://zoobank.org/";	
$context->pmid 		= "https://www.ncbi.nlm.nih.gov/pubmed/";
$context->pmc 		= "https://www.ncbi.nlm.nih.gov/pmc/articles/";	
$context->jstor 	= "https://www.jstor.org/stable/";	
$context->biostor 	= "https://biostor.org/reference/";	
$context->archive 	= "https://archive.org/details/";
$context->bhl 		= "http://www.biodiversitylibrary.org/bibliography/";


// hack
$context->orcid 	= "gql:orcid";	
$context->twitter 	= "gql:twitter";
$context->container = "gql:container";
$context->titles	= "gql:titles";


$config['context'] = $context;

/*
//----------------------------------------------------------------------------------------
// get root of JSON-LD document. If we have @graph we assume document is framed so there 
// is only one root (what could possibly go wrong?)
function get_root($obj)
{
	$root = $obj;
	if (is_array($root))
	{
		$root = $root[0];
	}
	if (isset($root->{'@graph'}))
	{
		$root = $root->{'@graph'};
	
		if (is_array($root))
		{
			$root = $root[0];
		}
	}
	
	return $root;
}
*/


//----------------------------------------------------------------------------------------
// SPARQL and Wikidata will often return strings that have language flags so process
// these here. For now we strip language flags and return an array of unique strings.
function literals_to_array($value)
{
	$strings = array();
	
	if (is_object($value))
	{
		$strings[] = $value->{"@value"};
	}
	else
	{
		if (is_array($value))
		{
			foreach ($value as $v)
			{
				$strings[] = $v->{"@value"};
			}
		
			$strings = array_unique($strings);
		}
		else
		{
			$strings[] = $value;
		}
	}
	
	return $strings;
}

//----------------------------------------------------------------------------------------
// Handle titles in same way as DataCite
function titles_to_array($value)
{
	$strings = array();
	
	if (is_object($value))
	{
		$title = new stdclass;
		$title->lang = $value->{"@language"};
		$title->title = $value->{"@value"};
		
		$strings[] = $title;
	}
	else
	{
		if (is_array($value))
		{
			foreach ($value as $v)
			{
				$title = new stdclass;
				$title->lang = $v->{"@language"};
				$title->title = $v->{"@value"};
				
				$strings[] = $title;
			}
		}
		else
		{
			$title = new stdclass;
			$title->title = $value;
			$strings[] = $title;
		}
	}
	
	return $strings;
}

//----------------------------------------------------------------------------------------
// We may sometimes get multiple values when we expect only one (e.g., for DOIs or ORCIDs)
// so arbitrarily pick one value to avoid type clashes (e.g. when schema expects a string
// but instead gets an array
function pick_one($value)
{
	$result = $value;
	
	if (is_array($value))
	{
		$result = $value[0];
	}
	
	return $result;
}


//----------------------------------------------------------------------------------------
// Query for a single thing
function one_object_query($args, $sparql)
{
	global $config;
	
	// do query
	$json = get(
		$config['sparql_endpoint'] . '?query=' . urlencode($sparql),			
		'application/ld+json'
	);
	
	$doc = JsonLD::compact($json, json_encode($config['context']));
	
	// print_r($doc);
	
	if (isset($doc->{'@graph'}))
	{
		// We need to frame it on the thing that is the subject of the
		// query, i.e. $args['id']
		$n = count($doc->{'@graph'});
		$type = '';
		$i = 0;
		while ($i < $n && $type == '')
		{
			if ($doc->{'@graph'}[$i]->id == $args['id'])
			{
				if (is_array($doc->{'@graph'}[$i]->type))
				{
					$type = $doc->{'@graph'}[$i]->type[0];
				}
				else
				{
					$type = $doc->{'@graph'}[$i]->type;
				}
				
			}
			$i++;
		}
		
		$frame = (object)array(
				'@context' => $config['context'],
				'@type' => $type
			);

		$framed = JsonLD::frame($json, json_encode($frame));
		
		$doc = $framed->{'@graph'}[0];
		
	
	}
	
	// post process 
	
	if (isset($doc->name))
	{
		$doc->name = literals_to_array($doc->name);
	}
	
	if (isset($doc->titles))
	{
		$doc->titles = titles_to_array($doc->titles);
	}
	
	
	if (isset($doc->description))
	{
		$doc->description = literals_to_array($doc->description);
	}
		
	// cleanup
	if (isset($doc->{"@context"}))
	{
		unset($doc->{"@context"});
	}
	
	return $doc;
}	

//----------------------------------------------------------------------------------------
// Query for a list
function list_object_query($args, $sparql)
{
	global $config;
	
	// do query
	$json = get(
		$config['sparql_endpoint'] . '?query=' . urlencode($sparql),			
		'application/ld+json'
	);
		
	$doc = JsonLD::compact($json, json_encode($config['context']));
	
	// print_r($doc);
	
	// post process to create a simple list
	
	$result = array();
	
	if (isset($doc->{"@graph"}))
	{
		foreach ($doc->{"@graph"} as $d)
		{
			if (isset($d->name))
			{
				$d->name = literals_to_array($d->name);
			}	
			
			if (isset($d->titles))
			{
				$d->titles = titles_to_array($d->titles);
			}	
			
			// unique?
			if (isset($d->doi) && is_array($d->doi))
			{
				$d->doi = $d->doi[0];
			}	
			if (isset($d->datePublished) && is_array($d->datePublished))
			{
				$d->datePublished = $d->datePublished[0];
			}	
			
			
			
			$result[] = $d;			
		}
	}
	else
	{
		if (isset($doc->name))
		{
			$doc->name = literals_to_array($doc->name);
		}	

		if (isset($doc->titles))
		{
			$doc->titles = titles_to_array($doc->titles);
		}	
		
		$result[] = $doc;
	}
	
	return $result;
}	

//----------------------------------------------------------------------------------------
// Query for a single thing, in this case a person
function person_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?item a ?type . 

	 ?item schema:name ?label .
	 ?item schema:description ?description .
	 ?item schema:image ?image .

	 ?item schema:birthDate ?birthDate .
	 ?item schema:deathDate ?deathDate .

	 ?item gql:orcid ?orcid .
	 ?item gql:twitter ?twitter .
	 ?item gql:researchgate ?researchgate .
	}
	WHERE
	{
	   VALUES ?item { ' . $args['id'] . ' }
  
	  ?item wdt:P31 ?type .
	
	  OPTIONAL {
	   ?item rdfs:label ?label .
	   # filter languages otherwise we can be inundated
	  FILTER(
		   LANG(?label) = "en" 
		|| LANG(?label) = "fr" 
		|| LANG(?label) = "de" 
		|| LANG(?label) = "es" 
		|| LANG(?label) = "zh"
		)
	  }    
  
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
  
	  OPTIONAL {
	   ?item wdt:P569 ?date_of_birth .
	   BIND(SUBSTR(STR(?date_of_birth), 1, 10) as ?birthDate) 
	  }  
  
	  OPTIONAL {
	   ?item wdt:P570 ?date_of_death .
	   BIND(SUBSTR(STR(?date_of_death), 1, 10) as ?deathDate) 
	  }    
  
	  # identifiers
	 OPTIONAL {
	   ?item wdt:P496 ?orcid .   
	  }  
  
	 OPTIONAL {
	   ?item wdt:P2002 ?twitter .   
	  } 
	  
	 OPTIONAL {
	   ?item wdt:P2038 ?researchgate .   
	  } 	      
	}   
	';
	
	$doc = one_object_query($args, $sparql);

	return $doc;
}		

//----------------------------------------------------------------------------------------
// List works authored by a person
function person_works_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
	PREFIX gql: <' . $config['hack_uri'] . '>


	CONSTRUCT
	{
	 ?item a ?type . 

	 ?item gql:titles ?title .
	 ?item schema:datePublished ?datePublished .

	 ?item bibo:doi ?doi .
	}
	WHERE
	{
	  VALUES ?author { ' . $args['id'] . ' }
	  
  	  ?item wdt:P50 ?author .
	  ?item wdt:P31 ?type .
	  
	  ?item wdt:P1476 ?title .
	

	  OPTIONAL {
	   ?item wdt:P356 ?doi .
	  }  
  
	  OPTIONAL {
	    ?item wdt:P577 ?date .
	    BIND(SUBSTR(STR(?date), 1, 10) as ?datePublished) 
	  } 
	} 
	';
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// Get work
function work_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
	PREFIX gql: <' . $config['hack_uri'] . '>


	CONSTRUCT
	{
		?item a ?type . 

		# work 
		?item gql:titles ?title .
		?item schema:datePublished ?datePublished .

		# author(s)
		?item schema:author ?author .
		?author schema:name ?author_name .
		?author schema:position ?author_order .

		?author gql:orcid ?orcid . 

		# location

		# container
		?item gql:container ?container .
		?container gql:titles ?container_title .
		?container schema:issn ?issn .

		?item schema:volumeNumber ?volume .
		?item schema:issueNumber ?issue .
		?item schema:pageStart ?pageStart .
		?item schema:pageEnd ?pageEnd .
		?item schema:pagination ?pages .

		# identifiers
		?item schema:identifier ?pmc . 
		?item schema:identifier ?pmid . 
		?item schema:identifier ?zoobank . 
		?item schema:identifier ?jstor . 
		?item schema:identifier ?biostor . 
		?item schema:identifier ?internetarchive .

		?item bibo:doi ?doi .
		?item schema:isbn ?isbn .
	}
	WHERE
	{
	  VALUES ?item { ' . $args['id'] . ' }
	  
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
		 }
		}
	  }    	
	  
	  # location
	  
  # container
  OPTIONAL {
   ?item wdt:P1433|wdt:P361 ?container .
   ?container wdt:P1476 ?container_title .
   OPTIONAL {
     ?container wdt:P236 ?issn .
    }    
  }	  
	  
  OPTIONAL {
   ?item wdt:P478 ?volume .
  }   
  
  OPTIONAL {
   ?item wdt:P433 ?issue .
  }  
  
  OPTIONAL {
   ?item wdt:P304 ?pages .
  }
	  
	    
	# identifiers
	  OPTIONAL {
	   ?item wdt:P356 ?doi .
	  }  
	  
	# identifiers
	  OPTIONAL {
	   ?item wdt:P212|wdt:P957 ?isbn .
	  }   	  
  
    # so we can have identifiers with "standard" short names
  	  OPTIONAL {
	   ?item wdt:P698 ?pmid_identifier .
	   BIND( IRI (CONCAT ("https://www.ncbi.nlm.nih.gov/pubmed/", STR(?pmid_identifier))) as ?pmid)
	  }  
	  
  	  OPTIONAL {
	   ?item wdt:P932 ?pmc_identifier .
	   BIND( IRI (CONCAT ("https://www.ncbi.nlm.nih.gov/pmc/articles/PMC", STR(?pmc_identifier))) as ?pmc)
	  }  
    
  	  OPTIONAL {
	   ?item wdt:P2007 ?zoobank_identifier .
	   BIND( IRI (CONCAT ("http://zoobank.org/", STR(?zoobank_identifier))) as ?zoobank)
	  }  

  	  OPTIONAL {
	   ?item wdt:P888 ?jstor_identifier .
	   BIND( IRI (CONCAT ("https://www.jstor.org/stable/", STR(?jstor_identifier))) as ?jstor)
	  }  

  	  OPTIONAL {
	   ?item wdt:P5315 ?biostor_identifier .
	   BIND( IRI (CONCAT ("https://biostor.org/reference/", STR(?biostor_identifier))) as ?biostor)
	  }  

		  OPTIONAL {
   ?item wdt:P724 ?internetarchive_identifier .   
   BIND( IRI (CONCAT ("https://archive.org/details/", STR(?internetarchive_identifier))) as ?internetarchive)
  }  


  
  # date
	  OPTIONAL {
	    ?item wdt:P577 ?date .
	    BIND(SUBSTR(STR(?date), 1, 10) as ?datePublished) 
	  } 
	} 
	';
	
	//echo $sparql;
	//exit();
	
	
	$doc = one_object_query($args, $sparql);
	
	// container
	if (isset($doc->container))
	{
		if (isset($doc->container->titles))
		{
			$doc->container->titles = titles_to_array($doc->container->titles);
		}
	}
	
	// simplify authors
	if (isset($doc->author))
	{
		$authorList = array();
		
		foreach ($doc->author as $author)
		{		
			if (isset($author->name))
			{
				$a = new stdclass;
				$a->name = literals_to_array($author->name);
				
				if (isset($author->orcid))
				{
					$a->orcid = pick_one($author->orcid);
				}
				
				if (preg_match('/wd:Q/', $author->id))
				{
					$a->id = $author->id;
				}
			
				if (isset($author->position))
				{
					$index = (Integer)$author->position;
					$authorList[$index] = $a;
				}
				else
				{
					$authorList[] = $a;
				}
			}
			
		}
		ksort($authorList, SORT_NUMERIC);
		$doc->author = $authorList;
	}
	

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// List of works cited 
function work_cites($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
	PREFIX gql: <' . $config['hack_uri'] . '>


	CONSTRUCT
	{
	 ?item a ?type . 

	 ?item gql:titles ?title .
	 ?item schema:datePublished ?datePublished .

	 ?item bibo:doi ?doi .
	}
	WHERE
	{
	  VALUES ?work { ' . $args['id'] . ' }

	  ?work wdt:P2860 ?item .

	  ?item wdt:P31 ?type .
	  
	  ?item wdt:P1476 ?title .
	

	  OPTIONAL {
	   ?item wdt:P356 ?doi .
	  }  
  
	  OPTIONAL {
	    ?item wdt:P577 ?date .
	    BIND(SUBSTR(STR(?date), 1, 10) as ?datePublished) 
	  } 
	} 
	';
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// List of works citing this work 
function work_cited_by($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
	PREFIX gql: <' . $config['hack_uri'] . '>


	CONSTRUCT
	{
	 ?item a ?type . 

	 ?item gql:titles ?title .
	 ?item schema:datePublished ?datePublished .

	 ?item bibo:doi ?doi .
	}
	WHERE
	{
	  VALUES ?work { ' . $args['id'] . ' }

	  ?item wdt:P2860 ?work .

	  ?item wdt:P31 ?type .
	  
	  ?item wdt:P1476 ?title .
	

	  OPTIONAL {
	   ?item wdt:P356 ?doi .
	  }  
  
	  OPTIONAL {
	    ?item wdt:P577 ?date .
	    BIND(SUBSTR(STR(?date), 1, 10) as ?datePublished) 
	  } 
	} 
	';
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// related works based on co-citation 
function work_related($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
	PREFIX gql: <' . $config['hack_uri'] . '>


	CONSTRUCT
	{
	 ?item a ?type . 

	 ?item gql:titles ?title .
	 ?item schema:datePublished ?datePublished .

	 ?item bibo:doi ?doi .
	}
	WHERE 
	{
		{
			SELECT ?item ?title ?datePublished ?description ?doi ?doi_identifier (COUNT(?item) AS ?c)		
			WHERE 
			{
				VALUES ?work {  ' . $args['id'] . '  }

				?citing_work wdt:P2860 ?work .
				?citing_work wdt:P2860 ?item .

				?item wdt:P1476 ?title .

			  OPTIONAL {
			   ?item wdt:P356 ?doi .
			  }  
  
			  OPTIONAL {
				?item wdt:P577 ?date .
				BIND(SUBSTR(STR(?date), 1, 10) as ?datePublished) 
			  }     
			    
				FILTER (?work != ?item)
			}
			GROUP BY ?item ?title ?datePublished ?description ?doi ?doi_identifier
	  }
	  FILTER (?c >= 5)
	}	
	';
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// List of work(s) that correect this work
function work_errata($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
	PREFIX gql: <' . $config['hack_uri'] . '>


	CONSTRUCT
	{
	 ?item a ?type . 

	 ?item gql:titles ?title .
	 ?item schema:datePublished ?datePublished .

	 ?item bibo:doi ?doi .
	}
	WHERE
	{
	  VALUES ?work { ' . $args['id'] . ' }

	  ?work wdt:P2507 ?item .

	  ?item wdt:P31 ?type .
	  
	  ?item wdt:P1476 ?title .
	

	  OPTIONAL {
	   ?item wdt:P356 ?doi .
	  }  
  
	  OPTIONAL {
	    ?item wdt:P577 ?date .
	    BIND(SUBSTR(STR(?date), 1, 10) as ?datePublished) 
	  } 
	} 
	';
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// List of work(s) that have this entity as their subject (e.g., reviews of work, obituaries)
function work_about($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
	PREFIX gql: <' . $config['hack_uri'] . '>


	CONSTRUCT
	{
	 ?item a ?type . 

	 ?item gql:titles ?title .
	 ?item schema:datePublished ?datePublished .

	 ?item bibo:doi ?doi .
	}
	WHERE
	{
	  VALUES ?thing { ' . $args['id'] . ' }

	  ?item wdt:P921 ?thing .

	  ?item wdt:P31 ?type .
	  
	  ?item wdt:P1476 ?title .
	

	  OPTIONAL {
	   ?item wdt:P356 ?doi .
	  }  
  
	  OPTIONAL {
	    ?item wdt:P577 ?date .
	    BIND(SUBSTR(STR(?date), 1, 10) as ?datePublished) 
	  } 
	} 
	';
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	

//----------------------------------------------------------------------------------------
// List of work(s) that are contained in this work (e.g., articles in a journal)
function work_parts($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
	PREFIX gql: <' . $config['hack_uri'] . '>


	CONSTRUCT
	{
	 ?item a ?type . 

	 ?item gql:titles ?title .
	 ?item schema:datePublished ?datePublished .

	 ?item bibo:doi ?doi .
	}
	WHERE
	{
	  VALUES ?container { ' . $args['id'] . ' }

	# published in
	  ?item wdt:P1433 ?container .

	  ?item wdt:P31 ?type .
	  
	  ?item wdt:P1476 ?title .
	

	  OPTIONAL {
	   ?item wdt:P356 ?doi .
	  }  
  
	  OPTIONAL {
	    ?item wdt:P577 ?date .
	    BIND(SUBSTR(STR(?date), 1, 10) as ?datePublished) 
	  } 
	} 
	';
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	


//----------------------------------------------------------------------------------------
// Get container, such as a journal (or a book with chapters)
function container_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
	PREFIX gql: <' . $config['hack_uri'] . '>


	CONSTRUCT
	{
	 ?item a ?type . 

	 ?item gql:titles ?title .

	 # identifiers
	 ?item schema:identifier ?zoobank . 
	 ?item schema:issn ?issn . 
	 ?item schema:isbn ?isbn . 
	 ?item schema:identifier ?internetarchive .
	 ?item schema:identifier ?bhl .
	 
	 # dates
	 ?item schema:startDate ?startDate . 
	 ?item schema:endDate ?endDate . 
	 
	  ?item schema:successorOf ?predecessor .
	  ?predecessor gql:titles ?predecessor_name .
  
	  ?item schema:predecessorOf ?successor .
	  ?successor gql:titles ?successor_name .
	 
	 # url, RSS feed, publisher, replace/replacedby
	 
	}
	WHERE
	{
	  VALUES ?item { ' . $args['id'] . ' }
	  
	  ?item wdt:P31 ?type .
	  
	  ?item wdt:P1476 ?title .
	  

	 # identifiers
	 
	  OPTIONAL {
	   ?item wdt:P236 ?issn .  
	  } 	 
	  
	  OPTIONAL {
	   ?item wdt:P212|wdt:P957 ?isbn .
	  }   	   	  
	 
	  OPTIONAL {
	   ?item wdt:P356 ?doi .
	  }  
	  

		  OPTIONAL {
   ?item wdt:P4327 ?bhl_identifier .   
   BIND( IRI (CONCAT ("http://www.biodiversitylibrary.org/bibliography/", STR(?bhl_identifier))) as ?bhl)
  }
  
		  OPTIONAL {
   ?item wdt:P724 ?internetarchive_identifier .   
   BIND( IRI (CONCAT ("https://archive.org/details/", STR(?internetarchive_identifier))) as ?internetarchive)
  }	  
    
  	  OPTIONAL {
	   ?item wdt:P2007 ?zoobank_identifier .
	   BIND( IRI (CONCAT ("http://zoobank.org/", STR(?zoobank_identifier))) as ?zoobank)
	  }  

  
  
  # dates
  
  # inception
  OPTIONAL {
   ?item wdt:P571 ?startDateValue . 
   BIND(SUBSTR(STR(?startDateValue), 1, 10) as ?startDate)  
  }   
  # dissolved, abolished or demolished
  OPTIONAL {
   ?item wdt:P576 ?endDateValue .  
   BIND(SUBSTR(STR(?endDateValue), 1, 10) as ?endDate) 
  }   
  
  # replaces, follows
  OPTIONAL {
   ?item wdt:P1365|wdt:P155 ?predecessor .  
   ?predecessor  wdt:P1476  ?predecessor_name .
   }

  # replaced by, followed by
  OPTIONAL {
   ?item wdt:P1366|wdt:P156 ?successor .  
   ?successor  wdt:P1476  ?successor_name .
  } 
     
 OPTIONAL {
   ?item wdt:P856 ?url .  
  }  	
	
	  # Feed
  OPTIONAL {
   	 ?item wdt:P1019 ?feed .
   }
	
}	
	';
	
	//echo $sparql;
	//exit();
	
	
	$doc = one_object_query($args, $sparql);
	
	// container
	if (isset($doc->successorOf))
	{
		$n = count($doc->successorOf);
		for ($i = 0; $i < $n; $i++)
		{
			$doc->successorOf[$i]->titles = titles_to_array($doc->successorOf[$i]->titles);
		}

	}	
	
	if (isset($doc->predecessorOf))
	{
		$n = count($doc->predecessorOf);
		for ($i = 0; $i < $n; $i++)
		{
			$doc->predecessorOf[$i]->titles = titles_to_array($doc->predecessorOf[$i]->titles);
		}

	}	
	


	return $doc;	

}

//----------------------------------------------------------------------------------------
// Query for a single thing
function thing_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?item a ?type . 

	 ?item schema:name ?label .
	 ?item schema:description ?description .
	 
	 #?item schema:image ?image .
	}
	WHERE
	{
	   VALUES ?item { ' . $args['id'] . ' }
  
	  ?item wdt:P31 ?type .
	
	  OPTIONAL {
	   ?item rdfs:label ?label .
	   # filter languages otherwise we can be inundated
	  FILTER(
		   LANG(?label) = "en" 
		|| LANG(?label) = "fr" 
		|| LANG(?label) = "de" 
		|| LANG(?label) = "es" 
		|| LANG(?label) = "zh"
		)
	  }    
  
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

	}   
	';
	
	$doc = one_object_query($args, $sparql);
	
	$schema_types = array();
	
	$types = array();
	if (is_array($doc->type))
	{
		$types = $doc->type;
	}
	else
	{
		$types[] = $doc->type;
	}
	
	foreach ($types as $type)
	{
		switch ($type)
		{
			case 'wd:Q5':
				$schema_types[] = 'Person';
				break;

			case 'wd:Q1348305':
			case 'wd:Q13442814':
			case 'wd:Q18918145':
			case 'wd:Q191067':

			// "book"
			case 'wd:Q47461344': // written work
			case 'wd:Q571':		// book
			case 'wd:Q3331189': // version, edition, or translation

			case 'wd:Q1266946': // thesis
			case 'wd:Q187685': // PhD thesis

			case 'wd:Q1980247': // chapter
				$schema_types[] = 'CreativeWork';
				break;
	
			case 'wd:Q5633421':
			case 'wd:Q1002697':
			case 'wd:Q737498': 
			case 'wd:Q1700470':
				$schema_types[] = 'Periodical';
				break;
	
		
			default:
				$schema_types[] = 'Thing';
				break;	
		}
	
	}
	
	$schema_types = array_unique($schema_types);
	
	$doc->type = $schema_types;
	

	return $doc;
}		
	

		
if (0)		
{
	// Q21389139 Hsiu-fu Chao
	
	// The Mallee Emu-Wren (Stipiturus mallee) (Q59759659)


	$args = array(
		'id' => 'wd:Q80442065' // 'Q7356570'
	);
	//$result = person_query($args);
	//$result = person_works_query($args);
	
	$args = array(
		'id' => 'wd:Q59759659' // 'Q7356570'
	);	

	$args = array(
		'id' => 'wd:Q30582014' 
	);	
	
	$args = array(
		'id' => 'wd:Q89665527' 
	);	

	$args = array(
		'id' => 'wd:Q44978282' 
	);	
	//$result = work_query($args);
	
	
	// 'Q80442065' // Q1333409' // 'Q7356570'
	
	
	$args = array(
		'id' => 'wd:Q30582014' 
	);	
	//$result = container_query($args);
	
	
	
	$args = array(
		'id' => 'wd:Q102138515' 
	);	
	//$result = work_query($args);
	//$result = work_cites($args);
	$result = work_query($args);
	
	
	//$result = thing_query($args);
	
	
	print_r($result);
}
		
?>
