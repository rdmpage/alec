# Modelling synonyms

Modelling homotypic synonyms (multiple names for same species). 

Wikidata [Homotypic names](https://www.wikidata.org/wiki/Wikidata:WikiProject_Taxonomy/Tutorial#Homotypic_names)

Examples to try

https://alec-demo.herokuapp.com/?id=Q1595713 (other names [Minyobates opisthomelas](https://alec-demo.herokuapp.com/?id=Q24774249) and [Dendrobates opisthomelas](https://alec-demo.herokuapp.com/?id=Q24773971) (which lists all the other names).

Dendrobates opisthomelas
- Andinobates opisthomelas (has taxon data and image)
- Minyobates opisthomelas
- Ranitomeya opisthomelas

[Colostethus fraterdanieli](https://alec-demo.herokuapp.com/?id=Q73791) has synonym Leucostethus fraterdanieli which doesn’t have a Wikidata item, but is used as page name by two Wikis.

## Need to do

So, need query to find all names, and to merge any taxon information attached to those names. Also has implications for building classifications, as same species may appear in multiple parts of the tree.


## Queries

Source -> target

```
select ?source ?source_name ?x ?target ?target_name ?relationshipPropLabel ?roleLabel where {
  
 VALUES ?source { wd:Q65931777 } # Caesalpinia decapetala
  #VALUES ?source { wd:Q67234988 }
  #VALUES ?target { wd:Q1595713 }
  
  {
    ?source ?relationship ?target .
     ?source wdt:P225 ?source_name .
    ?relationshipProp wikibase:directClaim ?relationship .
    ?target wdt:P225 ?target_name .
    FILTER(?relationship IN (wdt:P1403, wdt:P1420, wdt:P566, wdt:P694, wdt:P1366 )) .
    
  }
  UNION
  {    
    ?source p:P2868 ?statement .
    ?source wdt:P225 ?source_name .
    ?statement ps:P2868 ?role .
    ?statement pq:P642 ?target .
    ?target wdt:P225 ?target_name . 
    FILTER(?role IN (wd:Q810198, wd:Q14192851, wd:Q42310380, wd:Q59511375))
  }
  
  SERVICE wikibase:label {
        bd:serviceParam wikibase:language "en" .
    }
 }
```

Need to add to this query so we can get all names that share a basionym/protonym. Do this as a separate query.

