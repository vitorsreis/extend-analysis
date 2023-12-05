<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend;

use RuntimeException;
use VSR\Extend\Analysis\Contract\DriverInterface;

class Analysis
{
    /**
     * @var DriverInterface
     */
    private static $driver;

    /**
     * @return DriverInterface
     */
    public static function getDriver()
    {
        if (!isset(static::$driver)) {
            throw new RuntimeException('Driver not defined');
        }
        return static::$driver;
    }

    public static function setDriver(DriverInterface $driver)
    {
        static::$driver = $driver;
        static::install();
    }

    public static function install()
    {
        static::getDriver()->install([
            'chart' => [
                'id' => ['type' => 'varchar', 'primary' => true, 'length' => 100],
                'count' => ['type' => 'bigint', 'null' => true],
                'value' => ['type' => 'float', 'null' => true],
            ],
            'request' => [
                'id' => ['type' => 'integer', 'primary' => true, 'ai' => true],

                'start' => ['type' => 'float', 'index' => true],
                'end' => ['type' => 'float', 'index' => true],
                'duration' => ['type' => 'float', 'index' => true],

                'http_code' => ['type' => 'int', 'length' => 3, 'null' => true, 'index' => true],
                'method' => ['type' => 'varchar', 'length' => 10, 'null' => true, 'index' => true],
                'url' => ['type' => 'varchar', 'length' => 255, 'null' => true, 'index' => true],
                'ip' => ['type' => 'varchar', 'length' => 100, 'null' => true, 'index' => true],
                'referer' => ['type' => 'varchar', 'length' => 255, 'null' => true],
                'useragent' => ['type' => 'varchar', 'length' => 255, 'null' => true, 'index' => true],

                'get' => ['type' => 'json', 'null' => true],
                'post' => ['type' => 'json', 'null' => true],
                'raw_post' => ['type' => 'longtext', 'null' => true],
                'files' => ['type' => 'json', 'null' => true],
                'cookies' => ['type' => 'json', 'null' => true],
                'server' => ['type' => 'json', 'null' => true],
                'headers' => ['type' => 'json', 'null' => true],
                'inc_files' => ['type' => 'json', 'null' => true],

                'memory' => ['type' => 'bigint', 'null' => true, 'index' => true],
                'memory_peak' => ['type' => 'bigint', 'null' => true, 'index' => true],
                'error' => ['type' => 'json', 'null' => true],
                'extra' => ['type' => 'json', 'null' => true],

                'profile_count' => ['type' => 'int', 'index' => true],
            ],
            'profile' => [
                'request_id' => ['type' => 'integer', 'primary' => true],
                'id' => ['type' => 'integer', 'primary' => true],
                'parent_id' => ['type' => 'integer', 'primary' => true],

                'title' => ['type' => 'varchar', 'length' => 255],
                'start' => ['type' => 'float'],
                'end' => ['type' => 'float'],
                'duration' => ['type' => 'float'],

                'memory' => ['type' => 'bigint', 'null' => true],
                'memory_peak' => ['type' => 'bigint', 'null' => true],
                'error' => ['type' => 'json', 'null' => true],
                'extra' => ['type' => 'json', 'null' => true]
            ]
        ]);
    }
}
