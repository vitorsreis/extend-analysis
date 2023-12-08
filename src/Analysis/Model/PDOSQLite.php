<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend\Analysis\Model;

use Exception;
use PDO;
use RuntimeException;
use VSR\Extend\Analysis\Contract\AbstractModel;

class PDOSQLite extends AbstractModel
{
    /**
     * @var PDO
     */
    protected $instance;

    protected $lastId = false;

    protected $affected = [];

    protected $rows = [];

    protected $onConflictSupport = false;

    /**
     * @param string $database File path
     */
    public function __construct($database)
    {
        if (extension_loaded('pdo_sqlite') === false) {
            throw new RuntimeException('PDO SQLite extension is not loaded');
        }

        $this->instance = new PDO("sqlite:$database");
        $this->onConflictSupport = version_compare(
            $this->instance->query('select sqlite_version()')->fetch()[0],
            '3.24.0',
            '>='
        );
    }

    public function __destruct()
    {
        $this->instance = null;
        unset(
            $this->instance,
            $this->lastId,
            $this->affected
        );
    }

    private function query($command, ...$values)
    {
        $this->lastId = false;
        $this->affected = [];
        $this->rows = [];

        try {
            $statement = $this->instance->prepare($command);
            if (!$statement) {
                throw new Exception($this->instance->errorInfo()[2], $this->instance->errorInfo()[1]);
            }

            if (!$values) {
                $values = [[]];
            }

            # Escape values
            foreach ($values as $i => $fields) {
                foreach ($fields as $j => $value) {
                    if (is_array($value) || is_object($value)) {
                        $values[$i][$j] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
                    }
                }
            }

            !$this->instance->inTransaction() && $this->instance->beginTransaction();
            foreach ($values as $fields) {
                if (!$statement->execute($fields)) {
                    throw new Exception($statement->errorInfo()[2], $statement->errorInfo()[1]);
                }
                $this->lastId = $this->instance->lastInsertId();
                if ($statement->rowCount()) {
                    if (isset($fields['key']) && isset($fields['type']) && isset($fields['ref'])) {
                        $id = [$fields['key'], $fields['type'], $fields['ref']];
                    } elseif (isset($fields['key'])) {
                        $id = $fields['key'];
                    } else {
                        $id = $this->lastId;
                    }
                    $this->affected[] = $id;
                    unset($id);
                }
                $this->rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
            !$this->instance->inTransaction() || $this->instance->commit();
        } catch (Exception $th) {
            $this->instance->inTransaction() && $this->instance->rollBack();
            $this->lastId = false;
            $this->affected = false;
            $this->rows = false;
            throw $th;
        }
    }

    public function install()
    {
        $this->query("
            CREATE TABLE IF NOT EXISTS `hits` (
                `key` varchar(255) NOT NULL,
                `type` varchar(255) NOT NULL,
                `ref` varchar(255) DEFAULT NULL,
                `count` bigint DEFAULT NULL,
                `value` float DEFAULT NULL,
                `create_at` integer DEFAULT CURRENT_TIMESTAMP,
                `update_at` integer DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`key`,`type`,`ref`)
            )
        ");
        $this->query("
            CREATE TABLE IF NOT EXISTS `requests` (
                `id` integer PRIMARY KEY AUTOINCREMENT,
                `key` varchar(255) NOT NULL,
                `start` float NOT NULL,
                `end` float NOT NULL,
                `duration` float NOT NULL,
                `http_code` int(3) DEFAULT NULL,
                `method` varchar(10) DEFAULT NULL,
                `url` varchar(255) DEFAULT NULL,
                `ip` varchar(100) DEFAULT NULL,
                `referer` varchar(255) DEFAULT NULL,
                `useragent` varchar(255) DEFAULT NULL,
                `get` json DEFAULT NULL,
                `post` json DEFAULT NULL,
                `raw_post` longtext DEFAULT NULL,
                `files` json DEFAULT NULL,
                `cookies` json DEFAULT NULL,
                `server` json DEFAULT NULL,
                `headers` json DEFAULT NULL,
                `inc_files` json DEFAULT NULL,
                `memory` bigint DEFAULT NULL,
                `memory_peak` bigint DEFAULT NULL,
                `error` json DEFAULT NULL,
                `error_count` integer DEFAULT false,
                `extra` json DEFAULT NULL,
                `profile_count` integer NOT NULL
            )
        ");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-key` ON `requests` (`key`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-start` ON `requests` (`start`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-end` ON `requests` (`end`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-duration` ON `requests` (`duration`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-http_code` ON `requests` (`http_code`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-method` ON `requests` (`method`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-url` ON `requests` (`url`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-referer` ON `requests` (`referer`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-ip` ON `requests` (`ip`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-useragent` ON `requests` (`useragent`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-memory` ON `requests` (`memory`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-memory_peak` ON `requests` (`memory_peak`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-profile_count` ON `requests` (`profile_count`)");

        $this->query("
            CREATE TABLE IF NOT EXISTS `requests_avg` (
                `key` varchar(255) PRIMARY KEY,
                `avg` float DEFAULT NULL,
                `min` float DEFAULT NULL,
                `max` float DEFAULT NULL,
                `last` float DEFAULT NULL,
                `hits` bigint DEFAULT NULL,
                `create_at` integer DEFAULT CURRENT_TIMESTAMP,
                `update_at` integer DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests_avg-avg` ON `requests_avg` (`avg`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests_avg-min` ON `requests_avg` (`min`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests_avg-max` ON `requests_avg` (`max`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests_avg-hits` ON `requests_avg` (`hits`)");

        $this->query("
            CREATE TABLE IF NOT EXISTS `profiles` (
                `key` varchar(255) NOT NULL,
                `request_id` integer,
                `index` integer,
                `parent_id` integer,
                `start` float NOT NULL,
                `end` float NOT NULL,
                `duration` float NOT NULL,
                `memory` bigint DEFAULT NULL,
                `memory_peak` bigint DEFAULT NULL,
                `error` json DEFAULT NULL,
                `extra` json DEFAULT NULL,
                PRIMARY KEY (`request_id`,`index`,`parent_id`)
            )
        ");
        // $this->query("
        //     CREATE TABLE IF NOT EXISTS `profiles_avg` (
        //         `key` varchar(255) PRIMARY KEY,
        //         `avg` float DEFAULT NULL,
        //         `min` float DEFAULT NULL,
        //         `max` float DEFAULT NULL,
        //         `last` float DEFAULT NULL,
        //         `hits` bigint DEFAULT NULL,
        //         `create_at` integer DEFAULT CURRENT_TIMESTAMP,
        //         `update_at` integer DEFAULT CURRENT_TIMESTAMP
        //     )
        // ");
        // $this->query("CREATE INDEX IF NOT EXISTS `idx_profiles_avg-avg` ON `profiles_avg` (`avg`)");
        // $this->query("CREATE INDEX IF NOT EXISTS `idx_profiles_avg-min` ON `profiles_avg` (`min`)");
        // $this->query("CREATE INDEX IF NOT EXISTS `idx_profiles_avg-max` ON `profiles_avg` (`max`)");
        // $this->query("CREATE INDEX IF NOT EXISTS `idx_profiles_avg-hits` ON `profiles_avg` (`hits`)");
    }

    private function queryHit(...$values)
    {
        if ($this->onConflictSupport) {
            $this->query("
                INSERT INTO `hits`
                    (`key`, `type`, `ref`, `count`, `value`)
                VALUES
                    (:key, :type, :ref, 1, :value)
                ON CONFLICT (`key`, `type`, `ref`) DO UPDATE SET
                    `count` = `count` + 1,
                    `value` = CASE WHEN `type` = '' AND `ref` = '' THEN :value ELSE ((`value` * `count`) + :value) / (`count` + 1) END,
                    `update_at` = CURRENT_TIMESTAMP
            ", ...$values);
        } else {
            $this->query("
                UPDATE `hits` SET
                    `count` = `count` + 1,
                    `value` = CASE WHEN `type` = '' AND `ref` = '' THEN :value ELSE ((`value` * `count`) + :value) / (`count` + 1) END,
                    `update_at` = CURRENT_TIMESTAMP
                WHERE
                    `key` = :key
                    AND `type` = :type
                    AND `ref` = :ref
            ", ...$values);

            $values = array_filter($values, function ($i) {
                return !in_array([$i['key'], $i['type'], $i['ref']], $this->affected, true);
            });

            if ($values) {
                $this->query("
                    INSERT OR IGNORE INTO `hits`
                        (`key`, `type`, `ref`, `count`, `value`)
                    VALUES
                        (:key, :type, :ref, 1, :value)
                ", ...$values);
            }
        }
        unset($values);
    }

    private function queryAvg($collection, ...$values)
    {
        if ($this->onConflictSupport) {
            $this->query("
                INSERT INTO `$collection`
                    (`key`, `hits`, `avg`, `min`, `max`, `last`)
                VALUES
                    (:key, 1, :duration, :duration, :duration, :duration)
                ON CONFLICT (`key`) DO UPDATE SET
                    `hits` = `hits` + 1,
                    `avg` = ((`avg` * `hits`) + :duration) / (`hits` + 1),
                    `min` = CASE WHEN `min` > :duration THEN :duration ELSE `min` END,
                    `max` = CASE WHEN `max` < :duration THEN :duration ELSE `max` END,
                    `last` = :duration,
                    `update_at` = CURRENT_TIMESTAMP
            ", ...$values);
        } else {
            $this->query("
                UPDATE `$collection` SET
                    `hits` = `hits` + 1,
                    `avg` = ((`avg` * `hits`) + :duration) / (`hits` + 1),
                    `min` = CASE WHEN `min` > :duration THEN :duration ELSE `min` END,
                    `max` = CASE WHEN `max` < :duration THEN :duration ELSE `max` END,
                    `last` = :duration,
                    `update_at` = CURRENT_TIMESTAMP
                WHERE
                    `key` = :key
            ", ...$values);

            $values = array_filter($values, function ($i) {
                return !in_array($i['key'], $this->affected, true);
            });

            if ($values) {
                $this->query("
                    INSERT OR IGNORE INTO `$collection`
                        (`key`, `hits`, `avg`, `min`, `max`, `last`)
                    VALUES
                        (:key, 1, :duration, :duration, :duration, :duration)
                ", ...$values);
            }
        }
    }

    private function queryInsert($collection, ...$values)
    {
        $c = $v = [];
        foreach (current($values) as $k => $i) {
            $c[] = "`$k`";
            $v[] = ":$k";
        }

        $this->query("
            INSERT INTO `$collection` (
                " . implode(',' . PHP_EOL, $c) . "
            ) VALUES (
                " . implode(',' . PHP_EOL, $v) . "
            )
        ", ...$values);

        unset($c, $v, $collection, $values);

        return $this->lastId;
    }

    public function processRequest($request)
    {
        // Requests duration avg and hits
        $this->queryHit(...$this->hitGroup(
            self::HIT_NO_GROUP | self::HIT_ALL,
            ['key' => 'req', 'value' => $request['duration']]
        ));

        // Request key duration avg and hits
        $this->queryAvg(
            'requests_avg',
            ['key' => $request['key'], 'duration' => $request['duration']]
        );

        $request['error_count'] = $request['error'] ? count($request['error']) : 0;
        $profile = $request['profile'];
        unset($request['profile']);

        $request_id = $this->queryInsert('requests', $request);
        unset($request);

        foreach ($profile as $index => $item) {
            $profile[$index]['index'] = $index;
            $profile[$index]['request_id'] = $request_id;
            $profile[$index]['extra'] = $profile[$index]['extra']
                ? (json_encode($profile[$index]['extra'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null)
                : null;
            $profile[$index]['error'] = $profile[$index]['error']
                ? (json_encode($profile[$index]['error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null)
                : null;
        }
        unset($index, $item);

        $this->queryInsert('profiles', ...$profile);

        // $this->queryAvg('profiles_avg', ...array_map(function ($i) {
        //     return ['key' => $i['key'], 'duration' => $i['duration']];
        // }, $profile));

        unset($profile);
    }

    public function processServer($data)
    {
        $this->queryHit(...$this->hitGroup(
            self::HIT_NO_GROUP,
            ['key' => 'uptime', 'value' => $data['uptime']],
            ['key' => 'cpu', 'value' => $data['cpu']],
            ['key' => 'thr_total', 'value' => $data['thr_total']],
            ['key' => 'thr_running', 'value' => $data['thr_running']],
            ['key' => 'thr_sleeping', 'value' => $data['thr_sleeping']],
            ['key' => 'thr_stopped', 'value' => $data['thr_stopped']],
            ['key' => 'thr_zombie', 'value' => $data['thr_zombie']],
            ['key' => 'mem_total', 'value' => $data['mem_total']],
            ['key' => 'mem_free', 'value' => $data['mem_free']],
            ['key' => 'mem_used', 'value' => $data['mem_used']],
            ['key' => 'mem_cache', 'value' => $data['mem_cache']],
            ['key' => 'swa_total', 'value' => $data['swa_total']],
            ['key' => 'swa_free', 'value' => $data['swa_free']],
            ['key' => 'swa_used', 'value' => $data['swa_used']],
            ['key' => 'swa_cache', 'value' => $data['swa_cache']],
            ['key' => 'disk_total', 'value' => $data['disk_total']],
            ['key' => 'disk_free', 'value' => $data['disk_free']],
            ['key' => 'disk_used', 'value' => $data['disk_used']]
        ));

        $this->queryHit(...$this->hitGroup(
            self::HIT_ALL,
            ['key' => 'cpu', 'value' => $data['cpu']],
            ['key' => 'mem_used', 'value' => $data['mem_used']],
            ['key' => 'disk_used', 'value' => $data['disk_used']]
        ));

        unset($data);
    }

    public function getViewerData($options)
    {
        $json = [
            'server' => [
                'current' => [
                    'uptime' => 0,
                    'cpu' => 0.0,
                    'thr_total' => 0,
                    'thr_running' => 0,
                    'thr_sleeping' => 0,
                    'thr_stopped' => 0,
                    'thr_zombie' => 0,
                    'mem_total' => 0.0,
                    'mem_free' => 0.0,
                    'mem_used' => 0.0,
                    'mem_cache' => 0.0,
                    'swa_total' => 0.0,
                    'swa_free' => 0.0,
                    'swa_used' => 0.0,
                    'swa_cache' => 0.0,
                    'disk_total' => 0.0,
                    'disk_free' => 0.0,
                    'disk_used' => 0.0,
                ],
                'chart' => [
                    # '00:00' => ['cpu' => 0.0, 'mem' => 0.0, 'swa' => 0.0, 'disk' => 0.0]
                ]
            ],
            'request' => [
                'current' => [
                    'avg' => 0.0,
                    'total' => 0,
                    'per' => [
                        'second' => 0,
                        'minute' => 0,
                        'hour' => 0,
                        'day' => 0,
                    ]
                ],
                'chart' => [
                    # '00:00' => 0
                ],
                'dt' => [
                    'reqs' => [
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [
                            # ['id' => 0, 'key' => '', 'start' => 0.0, 'end' => 0.0, 'duration' => 0.0, 'memory' => 0.0, 'profile_count' => 0, 'http_code' => 0, 'method' => '', 'uri' => '', 'error_count' => false]
                        ]
                    ],
                    'keys' => [
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [
                            # ['key' => '', 'count' => 0, 'avg' => 0.0, 'min' => 0.0, 'max' => 0.0, 'last' => 0.0]
                        ]
                    ],
                ]
            ]
        ];

        $chartGroupBy = !empty($options['chartGroupBy']) ? $options['chartGroupBy'] : null;
        $chartLength = !empty($options['chartLength']) ? $options['chartLength'] : 100;

        $dtReqsStart = !empty($options['dtReqsStart']) ? $options['dtReqsStart'] : 0;
        $dtReqsLength = !empty($options['dtReqsLength']) ? $options['dtReqsLength'] : 10;
        $dtReqsOrder = !empty($options['dtReqsOrder']) ? $options['dtReqsOrder'] : 'start';
        $dtKeysStart = !empty($options['dtKeysStart']) ? $options['dtKeysStart'] : 0;
        $dtKeysLength = !empty($options['dtKeysLength']) ? $options['dtKeysLength'] : 10;
        $dtKeysOrder = !empty($options['dtKeysOrder']) ? $options['dtKeysOrder'] : 'avg';

        # Get server.current.*, request.current.*
        $this->query("
            SELECT
                *
            FROM
                `hits`
            WHERE
                `type` = '' AND `ref` = ''
            ORDER BY
                `ref` DESC
        ");
        foreach ($this->rows as $i) {
            if ($i['key'] === 'req') {
                $time = strtotime($i['update_at']) - strtotime($i['create_at']);
                $json['request']['current']['avg'] = round($i['value'], 3);
                $json['request']['current']['total'] = $i['count'];
                $json['request']['current']['per']['second'] = ceil($i['count'] / $time);
                $json['request']['current']['per']['minute'] = ceil($i['count'] / ($time / 60));
                $json['request']['current']['per']['hour'] = ceil($i['count'] / ($time / 3600));
                $json['request']['current']['per']['day'] = ceil($i['count'] / ($time / 86400));
                continue;
            }

            if (isset($json['server']['current'][$i['key']])) {
                $json['server']['current'][$i['key']] = round($i['value'] ?: 0, 1);
            }
        }

        # Get server.chart.*, request.chart.*
        if ($chartGroupBy) {
            $this->query("
                SELECT
                    `ref` AS `ref`,
                    GROUP_CONCAT(json_object('key', `key`,'count', `count`,'value', `value`)) AS `json`
                FROM
                    `hits`
                WHERE
                    `type` = 's10'
                GROUP BY
                    `ref`
                ORDER BY
                    `ref` DESC
                LIMIT
                    :length
            ", [
                'length' => $chartLength
            ]);
            foreach ($this->rows as $i) {
                foreach (json_decode('[' . $i['json'] . ']', true) as $j) {
                    !isset($json['server']['chart'][$i['ref']]) && $json['server']['chart'][$i['ref'] ] = [ 'cpu' => 0.0, 'mem_used' => 0.0, 'disk_used' => 0.0 ];
                    !isset($json['request']['chart'][$i['ref']]) && $json['request']['chart'][$i['ref']] = 0;

                    if ($j['key'] === 'req') {
                        $json['request']['chart'][$i['ref']] = round($j['count'] ?: 0);
                        continue;
                    }

                    $json['server']['chart'][$i['ref']][$j['key']] = round($j['value'] ?: 0, 1);
                }
            }
        }

        # Get request.dt.reqs.*
        $this->query("
            SELECT
                `key`,
                `start`,
                `end`,
                `duration`,
                `memory`,
                `profile_count`,
                `http_code`,
                `method`,
                `url`,
                `error_count`
            FROM
                `requests`
            ORDER BY
                `$dtReqsOrder` DESC
            LIMIT
                :start, :length
        ", [
            'start' => $dtReqsStart,
            'length' => $dtReqsLength
        ]);
        if ($this->rows) {
            foreach ($this->rows as $i) {
                $i['duration'] = round($i['duration'], 3);
                $i['memory'] = round($i['memory'], 3);
                $i['profile_count'] = round($i['profile_count']);
                $i['http_code'] = intval($i['http_code']);
                $i['error_count'] = round($i['error_count']);
                $json['request']['dt']['reqs']['data'][] = $i;
            }

            $this->query("
                SELECT
                    COUNT(*) AS `count`
                FROM
                    `requests`
            ");
            $json['request']['dt']['reqs']['recordsTotal'] = $this->rows[0]['count'];
            $json['request']['dt']['reqs']['recordsFiltered'] = $this->rows[0]['count'];
        }

        # Get request.dt.keys.*
        $this->query("
            SELECT
                `key`,
                `hits`,
                `avg`,
                `min`,
                `max`,
                `last`
            FROM
                `requests_avg`
            ORDER BY
                `$dtKeysOrder` DESC
            LIMIT
                :start, :length
        ", [
            'start' => $dtKeysStart,
            'length' => $dtKeysLength
        ]);
        if ($this->rows) {
            foreach ($this->rows as $i) {
                $i['avg'] = round($i['avg'], 3);
                $i['min'] = round($i['min'], 3);
                $i['max'] = round($i['max'], 3);
                $i['last'] = round($i['last'], 3);
                $json['request']['dt']['keys']['data'][] = $i;
            }

            $this->query("
                SELECT
                    COUNT(*) AS `count`
                FROM
                    `requests_avg`
            ");
            $json['request']['dt']['keys']['recordsTotal'] = $this->rows[0]['count'];
            $json['request']['dt']['keys']['recordsFiltered'] = $this->rows[0]['count'];
        }
        return $json;
    }
}
