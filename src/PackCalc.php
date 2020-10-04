<?php

use Graphp\Graph\Edge;
use Graphp\Graph\Graph;
use Graphp\Graph\Vertex;

class PackCalc
{
    /** @var Graph $graph */
    public $graph;
    /** @var int */
    private $quantity;
    /** @var int[] */
    private $packSizes;
    /** @var Vertex[] */
    private $vertexCache;
    /** @var Vertex[] */
    private $candidates;

    public function __construct(int $quantity, array $packSizes)
    {
        $this->quantity = $quantity;
        $this->packSizes = $packSizes;
        $this->packSizeCount = count($packSizes);
    }

    public function calculate(): array
    {
        if ($this->quantity === 0) {
            return [];
        }

        $this->generateGraph();

        $packs = array_fill_keys($this->packSizes, 0);

        // find the shortest path to the quantity closest to zero, counting pack sizes
        foreach ($this->getShortestPathToClosestCandidate() as $edge) {
            $packs[$edge->getAttribute('weight')]++;
        }

        return array_filter($packs, function (int $count) {
            return $count > 0;
        });
    }

    private function generateGraph(): void
    {
        $this->graph = new Graph();
        $this->vertexCache = [];
        $this->candidates = [];

        // create the root
        $vertex = $this->graph->createVertex(['id' => $this->quantity]);
        $this->vertexCache[$this->quantity] = $vertex;

        // build a graph of permutations by subtracting packs from quantities
        for ($i = $this->packSizeCount; $i >= 1; $i--) {
            $this->subtractPacks($vertex, array_reverse(array_slice($this->packSizes, 0, $i)));
        }
    }

    private function subtractPacks(Vertex $vertex, array $packSizes): void
    {
        foreach ($packSizes as $size) {
            // stop generating permutations if we've found more paths to 0 than available pack sizes
            if (isset($this->candidates[0]) && $this->candidates[0]->getEdgesIn()->count() >= $this->packSizeCount) {
                break;
            }

            // find or create a vertex by the subtracted quantity
            $quantity = $vertex->getAttribute('id') - $size;
            $nextVertex = $this->vertexCache[$quantity] ?? $this->graph->createVertex(['id' => $quantity]);
            $this->vertexCache[$quantity] = $nextVertex;

            // maintain one weight per edge between two quantities to avoid unnecessary recalculations
            if ($this->hasWeightedEdge($vertex, $nextVertex, $size)) {
                continue;
            }

            // link the vertices by the pack size
            $this->graph->createEdgeDirected($vertex, $nextVertex, ['weight' => $size]);

            // track vertices which satisfy the required quantity, stopping at this depth
            if ($quantity <= 0) {
                $this->candidates[$quantity] = $nextVertex;
                continue;
            }

            // subtract from the next quantity, increasing depth
            $this->subtractPacks($nextVertex, $packSizes);
        }
    }

    private function hasWeightedEdge(Vertex $source, Vertex $target, int $weight)
    {
        return $source->getEdges()->hasEdgeMatch(function (Edge $edge) use ($source, $target, $weight) {
            return $edge->getAttribute('weight') === $weight && $edge->isConnection($source, $target);
        });
    }

    private function getShortestPathToClosestCandidate()
    {
        $vertices = $this->candidates;

        if (count($vertices) > 1) {
            // sort by quantity descending
            usort($vertices, function (Vertex $a, Vertex $b) {
                $a = $a->getAttribute('id');
                $b = $b->getAttribute('id');

                if ($a === $b) {
                    return 0;
                }

                return $a < $b ? 1 : -1;
            });
        }

        $candidate = array_shift($vertices);

        // aid traversal by removing vertices & edges which don't lead to the candidate
        $this->pruneSmallerVerticesFromGraph($candidate, $vertices);
        $this->pruneDeadEndsFromGraph($candidate);

        return (new BreadthFirst($this->vertexCache[$this->quantity]))->getEdgesTo($candidate);
    }

    private function pruneSmallerVerticesFromGraph(Vertex $candidate, array $vertices)
    {
        /** @var Vertex $vertex */
        foreach ($vertices as $vertex) {
            // don't remove a vertex if it's directly linked to the candidate
            if ($candidate->hasEdgeFrom($vertex)) {
                continue;
            }

            // find vertices that have an edge to this one and are still lower than the candidate
            $nextVertices = $vertex
                ->getVerticesEdgeFrom()
                ->getVerticesDistinct()
                ->getVerticesMatch(function (Vertex $vertex) use ($candidate) {
                    return $vertex->getAttribute('id') < $candidate->getAttribute('id');
                })
                ->getVector();

            // remove this vertex and its edges before continuing up the graph
            $vertex->destroy();
            $this->pruneSmallerVerticesFromGraph($candidate, $nextVertices);
        }
    }

    private function pruneDeadEndsFromGraph(Vertex $candidate)
    {
        do {
            $retraverse = false;
            /** @var Vertex $vertex */
            foreach ($this->graph->getVertices() as $vertex) {
                if ($vertex !== $candidate && $vertex->getEdgesOut()->count() === 0) {
                    $vertex->destroy();
                    $retraverse = true;
                }
            }
        } while ($retraverse);
    }
}
