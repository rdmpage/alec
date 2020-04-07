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






