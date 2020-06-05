# Darwin Core occurrence in Wikidata

Wikidata has type locality, so we can build maps for type localities.

## Map of all localities

```
SELECT *
WHERE
{
   ?taxon wdt:P31 wd:Q16521 . 
   ?taxon wdt:P225 ?taxon_name . 
   ?taxon wdt:P5304 ?type_locality .
   ?type_locality p:P625 ?statement.
   ?type_locality rdfs:label ?type_locality_name .
   ?statement ps:P625 ?geo .
   FILTER (lang(?type_locality_name) = 'en')
 }
```

[Try it](https://w.wiki/T6x)

## Details for one taxon

Could try and model this using Darwin Core, which means we can do some interesting spatial queries within Wikidata :)


```
SELECT *
WHERE
{
    #values ?taxon { wd:Q47461565 }
   ?taxon wdt:P31 wd:Q16521 . 
   ?taxon wdt:P225 ?taxon_name . 
  
   #locality, which should also have a name
   ?taxon wdt:P5304 ?type_locality .
   ?type_locality rdfs:label ?type_locality_name .
   FILTER (lang(?type_locality_name) = 'en') .
  
   # get coordinates of type_locality, if known
   OPTIONAL {
     ?type_locality wdt:P625 ?geo 
    }
    
   ?taxon p:P5304 ?type_locality_statement .
  
  # May have a string describing the locality
  OPTIONAL {
    {
      ?type_locality_statement pq:P1932 ?verbatimLocality .
    }
    UNION
    {
      ?type_locality_statement pq:P2795 ?verbatimLocality .
    }
  }
  
  # May have a date
  OPTIONAL {
  ?type_locality_statement pq:P585 ?eventDate .
  }  

  # May have coordinates of actual type locality  
  OPTIONAL {
     ?type_locality_statement pq:P625 ?verbatimCoordinates .
  }
  
  
 }




```




