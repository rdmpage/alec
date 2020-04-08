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






