# Visualising results

## Classifications in Wikidata

Classifications in Wikidata can be complex and not trees. 

For example, if we trace the parents of the frog family “ Leptodactylidae” back we get a graph like this:

Likewise, if we do the same for the albatross genus Diomedea we get a similarly complex diagram:

These multiple classifications likely reflect several factors. If you deal with just extant species you are likely to have fairly shallow classifications, for example, the kingdom, phylum, class, order, family, genus used by GBIF. Some taxonomic groups may routinely use ranks such as subfamily, and in well-studied groups there may be additional taxa base don phylogenetic research (e.g., the [RTA clade](https://en.wikipedia.org/wiki/RTA_clade) in  spiders).

Anecdotally (certainly for vertebrates), many of the additional levels in the classifications in Wikidata come from fossil taxa. In the case of birds, extant Aves are a fairly isolated group, but as we go down the tree towards their common ancestor with crocodilians we encounter dinosaurs and other taxa. So if you are a palaeontologist the jump from, say Aves to Tetrapods skips over a fairly significant part of the tree.

Faced with this complexity, how do we display a classification in a simple way? One approach may be to display only a classification from a particular source, for example Mammal Species of the World. This requires that Wikidata has that classification, and enough information for you to extract it by a SPARQL query (for example if each node in the classification that is in MSW has a reference to MSW attached to that node).

Another approach is to extract a simplified classification from the graph. Technically, the graphs shown above are DAGs [Directed acyclic graph](https://en.wikipedia.org/wiki/Directed_acyclic_graph).  An obvious way to simplify a DAG is to find the shortest path in that DAG. For example, the path x….z is a path through the DAG. Shortest paths are reasonably easy to find (see https://en.wikipedia.org/wiki/Topological_sorting#Application_to_shortest_path_finding). At the moment this looks the best bet for displaying classifications from Wikidata.

### Preferred classifications

In some cases the classification in Wikidata is complicated, but this complexity isn’t reflected in SPARQL results because parts of that classification have different “ranks”. For example, for the plant order Fagales there are currently seven parents:

- fabids
- Rosanae
- Hamamelididae
- eurosids I
- Monochlamydeae
- Archichlamydeae
- Juglandanae

One of these is flagged “Preferred rank” (fabids) and the others are “Normal rank”. As a result only the rabies appear in the list of parents.

