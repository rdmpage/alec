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

