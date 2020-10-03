<?php

use Graphp\Graph\Edge;
use Graphp\Graph\Graph;
use Graphp\Graph\Vertex;

class PackCalc
{
    public $graph;
    private $quantity;
    private $packSizes;
    private $vertexCache;
    private $candidates;

    public function __construct(int $quantity, array $packSizes)
    {
        $this->quantity = $quantity;
        $this->packSizes = $packSizes;
    }

    public function calculate(): array
    {
        $this->generateGraph();

        $packs = array_fill_keys($this->packSizes, 0);

        // find the shortest path to the quantity closest to zero, counting pack sizes
        foreach ($this->getShortestPathToSmallestCandidate() as $edge) {
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
        for ($i = count($this->packSizes); $i >= 1; $i--) {
            $this->subtractPacks($vertex, array_slice($this->packSizes, 0, $i));
        }
    }

    private function subtractPacks(Vertex $vertex, array $packSizes): void
    {
        foreach ($packSizes as $size) {
            // find or create a vertex by the subtracted quantity
            $quantity = $vertex->getAttribute('id') - $size;
            $nextVertex = $this->vertexCache[$quantity] ?? $this->graph->createVertex(['id' => $quantity]);
            $this->vertexCache[$quantity] = $nextVertex;

            // maintain one weight per edge between two quantities to avoid unnecessary recalculations
            if ($this->hasWeightedEdge($vertex, $nextVertex, $size)) {
                continue;
            }

            // link the verties by the pack size
            $this->graph->createEdgeDirected($vertex, $nextVertex, ['weight' => $size]);

            // track vertices below zero and stop at this level
            if ($quantity <= 0) {
                $this->candidates[$quantity] = $nextVertex;
                continue;
            }

            // subtract from the next quantity
            $this->subtractPacks($nextVertex, $packSizes);
        }
    }

    private function hasWeightedEdge(Vertex $source, Vertex $target, int $weight)
    {
        return $source->getEdges()->hasEdgeMatch(function (Edge $edge) use ($source, $target, $weight) {
            return $edge->getAttribute('weight') === $weight && $edge->isConnection($source, $target);
        });
    }

    private function getShortestPathToSmallestCandidate()
    {
        $vertices = $this->candidates;
        $search = new BreadthFirst($this->vertexCache[$this->quantity]);

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

        return $search->getEdgesTo(array_shift($vertices));
    }
}
