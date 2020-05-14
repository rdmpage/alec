# Bats

## Bat genera in Wikidata with optional dates

```
PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>
SELECT ?root_name ?parent_name ?child_name ?year WHERE
{ 
  VALUES ?root_name {"Chiroptera"}
 ?root wdt:P225 ?root_name .
 ?child wdt:P171+ ?root . 
 ?child wdt:P171 ?parent .
 #?child wdt:P225 ?child_name .
  
     ?child p:P225 ?child_statement . 
     ?child_statement ps:P225 ?child_name .


  # genus
 ?child wdt:P105 wd:Q34740 .

  OPTIONAL {
  ?child_statement pq:P574 ?date .
    BIND(year(?date) AS ?year)



  }
  
 ?parent wdt:P225 ?parent_name .
 }
ORDER BY ?child_name 
```

https://w.wiki/QM9

