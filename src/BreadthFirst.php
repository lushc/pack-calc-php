<?php

use Graphp\Graph\Vertex;
use Graphp\Graph\Exception\OutOfBoundsException;
use Graphp\Graph\Set\Edges;
use Graphp\Graph\Set\Vertices;

/**
 * Simple breadth-first shortest path algorithm
 *
 * This algorithm ignores edge weights and operates as a level-order algorithm
 * on the number of hops. As such, it considers the path with the least number
 * of hops to be shortest.
 *
 * This is particularly useful your Graph doesn't have Edge weights assigned to
 * begin with or if you're merely interested in knowing which Vertices can be
 * reached at all (path finding). This avoids running expensive operations to
 * determine the actual weight (distance) of a path.
 */
class BreadthFirst
{
    /**
     * Vertex to operate on
     *
     * @var Vertex
     */
    protected $vertex;

    /**
     * instantiate new algorithm
     *
     * @param Vertex $vertex Vertex to operate on
     */
    public function __construct(Vertex $vertex)
    {
        $this->vertex = $vertex;
    }

    /**
     * get distance between start vertex and given end vertex
     *
     * @param  Vertex               $endVertex
     * @throws OutOfBoundsException if there's no path to given end vertex
     * @return float
     * @uses self::getEdgesTo()
     */
    public function getDistance(Vertex $endVertex)
    {
        return (float)\count($this->getEdgesTo($endVertex));
    }

    /**
     * get array of edges on the walk for each vertex (vertex ID => array of walk edges)
     *
     * @return array[]
     */
    public function getEdgesMap()
    {
        $vertexQueue = array();
        $edges = array();

        // $edges[$this->vertex->getAttribute('id')] = array();

        $vertexCurrent = $this->vertex;
        $edgesCurrent = array();

        do {
            foreach ($vertexCurrent->getEdgesOut() as $edge) {
                $vertexTarget = $edge->getVertexToFrom($vertexCurrent);
                $vid = $vertexTarget->getAttribute('id');
                if (!isset($edges[$vid])) {
                    $vertexQueue[] = $vertexTarget;
                    $edges[$vid] = \array_merge($edgesCurrent, array($edge));
                }
            }

            // get next from queue
            $vertexCurrent = \array_shift($vertexQueue);
            if ($vertexCurrent) {
                $edgesCurrent = $edges[$vertexCurrent->getAttribute('id')];
            }
        // untill queue is empty
        } while ($vertexCurrent);

        return $edges;
    }

    public function getEdgesTo(Vertex $endVertex)
    {
        if ($endVertex->getGraph() === $this->vertex->getGraph()) {
            $map = $this->getEdgesMap();

            if (isset($map[$endVertex->getAttribute('id')])) {
                return new Edges($map[$endVertex->getAttribute('id')]);
            }
        }
        throw new OutOfBoundsException('Given target vertex can not be reached from start vertex');
    }

    /**
     * get map of vertex IDs to distance
     *
     * @return float[]
     * @uses Vertex::hasLoop()
     */
    public function getDistanceMap()
    {
        $ret = array();
        foreach ($this->getEdgesMap() as $vid => $edges) {
            $ret[$vid] = (float)\count($edges);
        }

        return $ret;
    }

    /**
     * get array of all target vertices this vertex has a path to
     *
     * @return Vertices
     * @uses self::getEdgesMap()
     */
    public function getVertices()
    {
        $ret = array();
        $graph = $this->vertex->getGraph();
        foreach (\array_keys($this->getEdgesMap()) as $vid) {
            foreach ($graph->getVertices() as $vertex) {
                if ($vertex->getAttribute('id') === $vid) {
                    $ret[$vid] = $vertex;
                    break;
                }
            }
        }

        return new Vertices($ret);
    }

    public function getEdges()
    {
        $ret = array();
        foreach ($this->getEdgesMap() as $edges) {
            foreach ($edges as $edge) {
                if (!\in_array($edge, $ret, true)) {
                    $ret[] = $edge;
                }
            }
        }

        return new Edges($ret);
    }
}