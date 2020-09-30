# Co-citation and author co-citation

Notes on computing co-citation and author co-citation

Examples to explore, see query below. Need to work through examples to get list of authors that are most often cited together.

```
SELECT ?item ?title ?datePublished ?description ?doi ?doi_identifier (COUNT(?item) AS ?c)		
		WHERE 
		{
			VALUES ?work { wd:Q57247416 }

			?citing_work wdt:P2860 ?work .
			?citing_work wdt:P2860 ?item .

			?item wdt:P1476 ?title .

			OPTIONAL {
				?item wdt:P577 ?date .
				BIND(STR(?date) as ?datePublished) 
			}  			
	
			# Make DOI lowercase
			OPTIONAL {
				?item wdt:P356 ?doi_string .   
				BIND( IRI (CONCAT (STR(?item), "#doi")) as ?doi_identifier)
				BIND( LCASE(?doi_string) as ?doi)
			}   	      
      
      		FILTER (?work != ?item)
		}
        GROUP BY ?item ?title ?datePublished ?description ?doi ?doi_identifier
```



