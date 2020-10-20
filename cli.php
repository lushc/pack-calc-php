<?php

require __DIR__ . '/vendor/autoload.php';

use Graphp\GraphViz\GraphViz;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;

(new SingleCommandApplication())
    ->addUsage('Example: cli.php 12001 250 500 1000 2000 5000')
    ->addArgument('quantity', InputArgument::REQUIRED, 'How many items need to be sent')
    ->addArgument('pack_sizes', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The available pack sizes (each separated by a space)')
    ->addOption('viz', null, InputOption::VALUE_NONE, 'Visualise the generated graph with GraphViz')
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $startTime = microtime(true);
        $quantity = $input->getArgument('quantity');
        $packSizes = array_map('intval', $input->getArgument('pack_sizes'));

        try {
            $packCalc = new PackCalc($quantity, $packSizes);
        } catch (InvalidArgumentException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return;
        }

        foreach ($packCalc->calculate() as $size => $count) {
            $output->writeln("{$count}x $size");
        }

        if ($output->isVerbose()) {
            $time = number_format(microtime(true) - $startTime, 3);
            $peakMemory = number_format(memory_get_peak_usage() / (1024 ** 2), 2);
            $output->writeln("<info>Finished in {$time} seconds with {$peakMemory} MB peak usage</info>");
        }

        if ($packCalc->graph && $input->getOption('viz')) {
            $output->writeln('<comment>Now visualising the graph... this could take a very long time!</comment>');
            try {
                $graphviz = new GraphViz();
                $image = $graphviz->createImageData($packCalc->graph);
                $filename = sprintf('%s/%s.png', __DIR__, bin2hex(random_bytes(8)));
                file_put_contents($filename, $image);
                $output->writeln("<info>Wrote graph image to {$filename}</info>");
            } catch (Exception $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
            }
        }
    })
    ->run();
