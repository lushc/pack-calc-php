# Pack Calc

A solution for calculating the number of packs required to satisfy a requested quantity of items while abiding by the following rules:

1. Only whole packs can be sent. Packs cannot be broken open.
2. Within the constraints of rule 1, send out no more items than necessary to fulfil the order.
3. Within the constraints of rule 1 & 2, send out as few packs as possible to fulfil each order.

## Getting started

```
composer install
```

[GraphViz](https://graphviz.org/) is needed when generating a graph visualisation.

## CLI

Usage:

```
cli.php [options] <quantity> <pack_sizes>...
```

For example:

```
php cli.php 12001 250 500 1000 2000 5000

Outputs:

1x 250
1x 2000
2x 5000
```

### Options

Add the `-v` flag to output runtime and memory usage.
Add the `--viz` flag to visualise the graph used to determine the result.

## Unit tests

```
composer tests
```

Test cases used can be found in tests/PackCalcTest.php

## Implementation

### Algorithm

1. Start a graph where vertices are quantities and edges are pack sizes
   - In the case of a single pack size, a graph isn't necessary to calculate the required packs and so we skip to #8
   - When the quantity exceeds an arbitary threshold (sum of pack sizes * 50) we first reduce the problem space by subtracting as many of the largest packs as possible while still leaving enough headroom to permutate a best fit
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

### Remarks

These are my thoughts on how the implementation can be improved or a different approach could be taken.

#### Pack size variations

In the "prime stress test" case of 500,000 items and pack sizes of `[23, 31, 53, 151, 757]`, the accepted result is `4x 23, 1x 31, 2x 53, 1x 151, 660x 757`, however there's another solution: `2x 31, 6x 53, 660x 757`. Although both solutions fit the desired quantity and use as few packs as possible (668), the latter is arugably better since fewer pack size variations are used (which would be easier to pack in a warehouse).

The issue here is that the BFS implementation only accounts for shortest path and does not score paths of equal length by the number of variations used.

#### Permutations vs combinations

The algorithm is generating permutations whereas the end result we're interested in is the combination (with repetition) of pack sizes where order of subtraction no longer matters. As evidenced in the example graph below, the final traversal will be over a number of paths which all result in the combination of `23, 23, 53, 53`.

Even with optimisations such as lowering the quantity where permutation starts and pruning the graph we're essentially still brute-forcing a solution, so perhaps a different approach could be to instead calculate the best combination of pack sizes for each integer between "1" and "quantity", using dynamic programming to store previous solutions.

#### Resource usage for large quantities & packs

Consider the following:

* `php cli.php 5000001 250 500 1000 2000 5000 -v` finishes in 0.11 seconds with 14.22 MB peak usage
* `php cli.php 5000001 250 500 1000 2000 5000 10000 20000 50000` finishes in 2.85 seconds with 166.21 MB peak usage

Increasing the number of pack size variations will only further increase the required resources to generate and traverse the graph at large quantities. Within a microservice deployment this is a legitimate concern in terms of runtime and memory constraints.

Some potential solutions:

* Limit the quantity the service can handle to a reasonable value and farm out larger quantities across multiple processes/requests
* Stop permutation once a certain memory budget is exceeded and begin traversal to the current best candidate (because larger packs go first it's more likely we will overshoot rather than undershoot the quantity)
* Reduce the range of available pack sizes so only a lower and upper bound are used for permutation (although this narrows the possibility of finding an exact fit)

## Example graph

Generated with the following command:

```
php cli.php 152 23 31 53 151 757 --viz
```

### Initial graph
![1_initial_graph.png](https://raw.githubusercontent.com/lushc/pack-calc-php/main/example/1_initial_graph.png?sanitize=true)

### Smaller vertices prune
![2_smaller_prune.png](https://raw.githubusercontent.com/lushc/pack-calc-php/main/example/2_smaller_prune.png?sanitize=true)

### Dead end vertices prune
![3_deadend_prune.png](https://raw.githubusercontent.com/lushc/pack-calc-php/main/example/3_deadend_prune.png?sanitize=true)
