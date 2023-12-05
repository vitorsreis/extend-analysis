<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

require_once __DIR__ . '/../vendor/autoload.php';

use VSR\Extend\Analysis;
use VSR\Extend\Analysis\Driver\PDOSQLite;

# disable xdebug
#ini_set('xdebug.max_nesting_level', 512);

Analysis::setDriver(new PDOSQLite(
    __DIR__ . '/test.sqlite'
));
//Analysis::setDriver(new Analysis\Driver\PDOMySQL(
//    '127.0.9.9',
//    'aproveadv',
//    'aproveadv',
//    'aproveadv',
//    3306
//));

for ($i = 0; $i < 10000; $i++) {
    $request = new Analysis\Request(false);

    $request->start('controller:aaa');
    $request->stop();

    for ($j = 0; $j < 500; $j++) {
        $request->start('aaa-' . $j);
    }
    $start = microtime(true);
    $request->save();

    echo $i . ', ' . (microtime(true) - $start) . PHP_EOL;
}

echo 'Memory: ' . (memory_get_peak_usage() / 1024 / 1024) . PHP_EOL;

# 0.18s save
# 16MB memory
