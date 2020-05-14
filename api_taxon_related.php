<?php

// find names related to this name, e.g. original names, replacement names, synonyms...

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/wikidata_api.php');


$id = 'Q15723542';

if (isset($_REQUEST['id']))
{
	$id = $_REQUEST['id'];
}



$query = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX prov: <http://www.w3.org/ns/prov#>
	PREFIX pr: <http://www.wikidata.org/prop/reference/>
	PREFIX wdt: <http://www.wikidata.org/prop/direct/>
	PREFIX wd: <http://www.wikidata.org/entity/>
	PREFIX dwc: <http://rs.tdwg.org/dwc/terms/>
	
	

	CONSTRUCT 
	{
		<http://example.rss>
		rdf:type schema:DataFeed;
		schema:name "Related names";
		schema:dataFeedElement ?item .

		?item 
			rdf:type schema:DataFeedItem;
			rdf:type ?item_type;
			schema:name ?name;
			dwc:nomenclaturalStatus ?state; 

	

	}
	WHERE
	{
	
		VALUES ?taxon { wd:' . $id . ' } 

  # Examples to play with 
  # Q67947916 Pionus chalcopterus
  # Q39135925 Tephrosia heterantha
  # Q15542377 Poissonia heterantha
  # Q14400 Allosaurus
  # Q131421 Mononykus (replacement)
  # Q2575797 Leptocleidus Müller, 1936 later homonym
  # Q5414606 Lilliput Wesołowska & Russell-Smith, 2008
  # Q21357142 Heraclides rumiko
  # Q19636617 Papilio rumiko
  
  # First we see if this name is a new combination,
  # or is the original combination for other name(s)
  # protonym = zoology, basionym = botany
  
  # original name
  # has basionym or protonym
  {   
    ?taxon wdt:P566|wdt:P1403 ?item .
    ?item wdt:P225 ?name . 
     BIND("tax. nov." AS ?state) 
   }
  
  UNION
  
  # is protonym or basionym (via subject has role)
  {   
    ?taxon p:P2868 ?statement .
    { ?statement ps:P2868 wd:Q810198} UNION { ?statement ps:P2868 wd:Q14192851 } .
    ?statement pq:P642 ?item .
    ?item wdt:P225 ?name .
    BIND("comb. nov." AS ?state)
    
  }
  
  # has replacement name(s)
  UNION 
    {
     ?taxon wdt:P694 ?item .
      ?item rdfs:label ?name .
      BIND("nom. nov" AS ?state)
      FILTER (LANG(?name) = "en")
     }    

   # has been replaced
  UNION  
    {
     ?taxon  wdt:P31 wd:Q17276484 .
     ?taxon p:P31 ?replaced_statement .
      {
      ?replaced_statement pq:P1366 ?item .
       ?item rdfs:label ?name .
       BIND("nom. nov" AS ?state)
      FILTER (LANG(?name) = "en")
      }
      
      UNION
      {
        ?replaced_statement pq:P1889 ?item .
        ?item rdfs:label ?name .        
        BIND("homonym" AS ?state)
        FILTER (LANG(?name) = "en")
      }     
    }  
		
	

}';



$callback = '';
if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

if ($callback != '')
{
	echo $callback . '(';
}
echo sparql_construct_stream($config['sparql_endpoint'], $query);
if ($callback != '')
{
	echo ')';
}


?>
