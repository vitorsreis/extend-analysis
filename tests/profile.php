<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

require_once __DIR__ . '/../vendor/autoload.php';

use VSR\Extend\Analysis;
use VSR\Extend\Analysis\Driver\PDOSQLite;

//Analysis::setDriver(new Analysis\Driver\PDOMySQL(
//    '127.0.9.9',
//    'aproveadv',
//    'aproveadv',
//    'aproveadv',
//    3306
//));

$avgCount = 0;
$avgTime = 0;
for ($i = 0; $i < 10000; $i++) {
    $request = new Analysis\Request(false);
    $request->start('controller:aaa');
    $request->stop();
    for ($j = 0; $j < 500; $j++) {
        $request->start('aaa-' . $j);
    }

    $start = microtime(true);

    $drive = new PDOSQLite(__DIR__ . '/test.sqlite');
    Analysis::setDriver($drive);

    $request->save();

    $time = microtime(true) - $start;

    $avgTime = (($avgTime * $avgCount) + $time) / ($avgCount + 1);
    $avgCount++;

    unset($request, $drive);
    echo $i . ', ' . (microtime(true) - $start) . PHP_EOL;
}

echo 'Memory: ' . (memory_get_peak_usage() / 1024 / 1024) . PHP_EOL;
echo 'Avg time: ' . $avgTime . PHP_EOL;
