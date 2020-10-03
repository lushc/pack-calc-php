<?php

use Graphp\GraphViz\GraphViz;

require_once __DIR__ . '/vendor/autoload.php';

$quantity = $argv[1];
$packSizes = array_map('intval', explode(',', $argv[2]));

$packCalc = new PackCalc($quantity, $packSizes);
$packsRequired = $packCalc->calculate();

foreach ($packsRequired as $size => $count) {
    print "{$count}x $size" . PHP_EOL;
}

(new GraphViz())->display($packCalc->graph);
