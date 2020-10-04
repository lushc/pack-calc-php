<?php

use Graphp\GraphViz\GraphViz;

require_once __DIR__ . '/vendor/autoload.php';

$startTime = microtime(true);
$quantity = $argv[1];
$packSizes = array_map('intval', explode(',', $argv[2]));

$packCalc = new PackCalc($quantity, $packSizes);
$packsRequired = $packCalc->calculate();

foreach ($packsRequired as $size => $count) {
    print "{$count}x $size" . PHP_EOL;
}

$time = number_format(microtime(true) - $startTime, 2);
$peakMemory = number_format(memory_get_peak_usage() / (1024 ** 2), 2);

print PHP_EOL . "Generated in {$time} seconds with {$peakMemory} MB peak usage";

if ($packCalc->graph) {
    print PHP_EOL . 'Now visualising the graph...';
    (new GraphViz())->display($packCalc->graph);
}
