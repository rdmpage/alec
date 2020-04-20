# Taxonomic hierarchy in Wikidata

## path from taxon to root

```
select ?parent_name ?grand_parent_name
where
{
  VALUES ?node { wd:Q2501775 }
  ?node wdt:P171+  ?parent .
  ?parent wdt:P225 ?parent_name .
  ?parent wdt:P171  ?grand_parent .
  ?grand_parent wdt:P225 ?grand_parent_name .
}
```

[Try it](https://w.wiki/M2B)

Instead of a single path we have a complex graph.

## original description(?) of a taxon

## discover barcodes via Uniprot

https://sparql.uniprot.org/sparql

```
SELECT * WHERE {
?nucleotide <http://purl.uniprot.org/core/organism> <http://purl.uniprot.org/taxonomy/259920> .
?uniprot <http://www.w3.org/2000/01/rdf-schema#seeAlso> ?nucleotide .
?nucleotide <http://purl.uniprot.org/core/locatedOn> ?accession .
?uniprot <http://www.w3.org/2000/01/rdf-schema#seeAlso> <http://purl.uniprot.org/pfam/PF00115>
}
```

## Names that a work is the source for

```
PREFIX prov: <http://www.w3.org/ns/prov#>
PREFIX pr: <http://www.wikidata.org/prop/reference/>
PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>

SELECT *
WHERE
{
	VALUES ?work { wd:Q59328831 }
	
	?provenance pr:P248 ?work . 
    ?statement prov:wasDerivedFrom ?provenance .
    ?taxon p:P225 ?statement . 
    ?taxon wdt:P31 wd:Q16521 .
    ?taxon wdt:P225 ?taxon_name .

}
```

# Reference for name

```
PREFIX prov: <http://www.w3.org/ns/prov#>
PREFIX pr: <http://www.wikidata.org/prop/reference/>
PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>

SELECT *
WHERE
{
  # taxon name
  ?wikidata wdt:P225 "Cactaceae" .
  
  # reference
  ?wikidata p:P225 ?statement. 
  ?statement prov:wasDerivedFrom ?provenance .
  
  # is it stated in a Wikidata item?
  ?provenance pr:P248 ?reference .
   OPTIONAL
  {
    ?reference wdt:P1476 ?title .
  }
  
  
  # page(s)
  OPTIONAL
  {
    ?provenance pr:P304 ?pages .
  }
  
  # BHL
 OPTIONAL
  {
    ?provenance pr:P687 ?bhl .
  }  
  
}
```

# BHL pages that are references for taxon names

```
PREFIX prov: <http://www.w3.org/ns/prov#>
PREFIX pr: <http://www.wikidata.org/prop/reference/>
PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>

SELECT *
WHERE
{

  # BHL
  ?provenance pr:P687 ?bhl .
  
  ?statement prov:wasDerivedFrom ?provenance .
  ?taxon p:P225 ?statement. 
  ?taxon wdt:P31 wd:Q16521 .
  ?taxon wdt:P225 ?taxon_name .
    
  # work
  OPTIONAL
  {
    ?provenance pr:P248 ?work .
    ?work wdt:P1476 ?title .
  }
  
 OPTIONAL
  {
    ?provenance pr:P304 ?page .
   }  
  
} LIMIT 1000
```


## Synonyms, replacements, etc.

```
SELECT * WHERE
{
  
  # Q67947916
  # Q39135925 plant
  # Q14400 Allosaurus
  # Q131421 Mononykus
  # Q2575797 Leptocleidus Müller, 1936 later homonym
  # Q5414606 Lilliput Wesołowska & Russell-Smith, 2008
  VALUES ?taxon { wd:Q5414606 }
  
  # botany
  
  # has basionym?
  OPTIONAL {   
    ?taxon wdt:P566 ?basionym .
    ?basionym wdt:P225 ?basionym_name .
  }
  
  # is basionym?
   OPTIONAL {   
    ?taxon p:P2868 ?statement .
    ?statement ps:P2868 wd:Q810198 .
    ?statement pq:P642 ?basionym_for .
    ?basionym_for wdt:P225 ?basionym_for_name .
  }
  
  # zoology
  # has protonym (original combination)?
  OPTIONAL {   
    ?taxon wdt:P1403 ?protonym .
    ?protonym wdt:P225 ?protonym_name .
  }  
  
   # is protonym?
   OPTIONAL {   
    ?taxon p:P2868 ?statement .
    ?statement ps:P2868 wd:Q14192851 .
    ?statement pq:P642 ?protonym_for .
    ?protonym_for wdt:P225 ?protonym_for_name .
  } 
  
  # has replacement name(s)
  OPTIONAL  
    {
     ?taxon wdt:P694 ?replaced_synonym .
      ?replaced_synonym rdfs:label ?replaced_synonym_name .
      FILTER (LANG(?replaced_synonym_name) = "en")
     # ?replaced_synonym wdt:P31 wd:Q17276484 .
     #?replaced_synonym p:P31 ?replaced_synonym_statement .
     # ?replaced_synonym_statement pq:P1366 ?replaced_by .
    }  
  
  # has been replaced
  OPTIONAL  
    {
     ?taxon  wdt:P31 wd:Q17276484 .
     ?taxon p:P31 ?replaced_statement .
      {
      ?replaced_statement pq:P1366 ?replaced_by .
       ?replaced_by rdfs:label ?replaced_by_name .
        FILTER (LANG(?replaced_by_name) = "en")
      }
      UNION
      {
        ?replaced_statement pq:P1889 ?different_from .
        ?different_from rdfs:label ?different_from_name .
        FILTER (LANG(?different_from_name) = "en")
      }
     
    }
  
  # other related names...
  
  # taxon synonym 
  OPTIONAL { 
    {
    ?taxon wdt:P1420 ?synonym .
    ?synonym wdt:P225 ?synonym_name .
    }
    UNION
    {
      ?synonym wdt:P1420 ?taxon .
      ?synonym wdt:P225 ?synonym_name .
    }
  }
 
  
}


```

Get a list of later homonyms

```
select * where
{
  ?x wdt:P31 wd:Q17276484 .
  
}
LIMIT 100
```




## Parent child pairs from MSW by Andra Waagmeester

```
SELECT ?taxon ?taxonLabel ?parentTaxon WHERE {
   ?taxon wdt:P31 wd:Q16521 ;
          p:P171* [
          ps:P171 ?parentTaxon ;
          prov:wasDerivedFrom [
            pr:P248 wd:Q1538807
            ] ;
     ]
   SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }
}
```

[Try it](https://w.wiki/NVK)