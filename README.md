## Algorithm overview

1. Start a graph where vertices are quantities and edges are pack sizes
2. Recursively build the graph by subtracting pack sizes from ancestors, starting from the root vertex (initial quantity)
   - Packs are subtracted from the current vertex (quantity) in descending order
   - Either a new vertex is created for the calculated quantity or an existing vertex is located in cache
   - A directed edge between the current vertex and new vertex is created to track the pack size used (i.e. a new permutation)
   - Vertices with a quantity <= 0 are treated as a candidate and no further subtraction occurs
   - Vertices with a quantity > 0 continue to recurse
   - Permutation generation is halted when a number of paths to 0 are found to prevent an exhaustive and expensive search
   - The available pack sizes are reduced on each iteration over the root as this helps produce different permutations
3. Candidate vertices are sorted (by quantity) descending, with the first being chosen as it's either 0 or closest to 0
4. The graph is pruned to remove paths to vertices that are below the chosen candidate vertex
5. The graph is pruned to remove other vertices which don't have any outgoing edges (i.e. they're a dead end)
6. A breadth-first search is performed to find the shortest number of edges between the root vertex and the candidate vertex
7. Each edge is iterated over, using their weight to tally the number of packs used at each size
8. An array is returned where the key is the pack size and the value is the count, with 0 values being filtered out

## Getting started

```
composer install
```

GraphViz is needed for generating a graph visualisation on the CLI.
Note that this could take a very long time.

## CLI

```
php cli.php 500 23,31,53,151,757
```

Outputs:

```
4x 23
2x 53
2x 151
```

## Tests

```
./vendor/bin/phpunit tests --testdox
```
