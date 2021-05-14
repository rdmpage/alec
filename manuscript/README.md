# Notes on the manuscript

## Generating nice DOI graphs


dot -Tpng bibmodel.dot -o bibmodel.png

dot -Tpng taxonmodel.dot -o taxonmodel.png

dot -Tpdf taxonmodel.dot -o taxonmodel.pdf

neato -Tpng clusters.dot -o clusters.png



