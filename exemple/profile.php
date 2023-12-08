<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

require_once __DIR__ . '/config.php';

use VSR\Extend\Analysis;

echo "Start add 1000 requests:" . PHP_EOL;

$sstart = microtime(true);
$avgCount = 0;
$avgTime = 0;
for ($i = 0; $i < 1000; $i++) {
    $request = new Analysis\Request("test-" . ($i % 10), false);

    # Example of use
    $request->start('aaa');
    $request->stop();

    $request->start('bbb');
    $request->stop();

    # Example of use multi level
    $request->start("bbb-lvl-1");
    $request->start("bbb-lvl-2");
    $request->start("bbb-lvl-3");
    $request->start("bbb-lvl-4");
    $request->start("bbb-lvl-5");
    $request->stop(); // bbb-lvl-5
    $request->stop(); // bbb-lvl-4
    $request->stop(); // bbb-lvl-3
    $request->stop(); // bbb-lvl-2
    $request->stop(); // bbb-lvl-1

    $request->start("ccc-lvl-1");
    $request->start("ccc-lvl-2");
    $request->start("ccc-lvl-3");
    $request->start("ccc-lvl-4");
    $request->start("ccc-lvl-5");
    $request->stop(); // ccc-lvl-5
    $request->stop(); // ccc-lvl-4
    $request->stop(); // ccc-lvl-3
    $request->stop(); // ccc-lvl-2
    $request->stop(); // ccc-lvl-1

    for ($j = 1; $j <= 500; $j++) {
        $request->start("ddd-lvl-$j");
    }

    $start = microtime(true);
    $request->save();
    $time = microtime(true) - $start;

    $avgTime = (($avgTime * $avgCount) + $time) / ($avgCount + 1);
    $avgCount++;

    unset($request, $drive);
    echo "- " . $i . ', time: ' . (microtime(true) - $start) . PHP_EOL;
}

echo 'End' . PHP_EOL;
echo '- Memory: ' . (memory_get_peak_usage() / 1024 / 1024) . PHP_EOL;
echo '- Avg time: ' . $avgTime . PHP_EOL;
echo '- Total time: ' . (microtime(true) - $sstart) . PHP_EOL;
