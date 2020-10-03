<?php

use Graphp\GraphViz\GraphViz;

require_once __DIR__ . '/vendor/autoload.php';

$quantity = $argv[1];
$packSizes = [250, 500, 1000, 2000, 5000];
$packCalc = new PackCalc($quantity, $packSizes);

$packCalc->calculate();
(new GraphViz())->display($packCalc->graph);
