# Export format ideas

## RIS

Not working

```
SELECT ?title ?VL ?IS ?pages ?SP ?EP ?Y1 ?DO  WHERE {
  ?work wdt:P1433 wd:Q16859933;
    wdt:P1476 ?title.
  OPTIONAL { ?work wdt:P478 ?VL. }
  OPTIONAL { ?work wdt:P433 ?IS. }
  OPTIONAL { ?work wdt:P304 ?pages .
     BIND (REPLACE(?pages, '[-|—].*$', '') AS ?SP) 
      
  }
  OPTIONAL { ?work wdt:P577 ?date. BIND (YEAR(?date) AS ?Y1) }
  OPTIONAL { ?work wdt:P356 ?DO. }
  
}
LIMIT 5

```