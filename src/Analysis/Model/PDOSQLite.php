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
                `has_error` tinyint(1) DEFAULT false,
                `extra` json DEFAULT NULL,
                `profile_count` int NOT NULL
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
                    `value` = ((`value` * `count`) + :value) / (`count` + 1),
                    `update_at` = CURRENT_TIMESTAMP
            ", ...$values);
        } else {
            $this->query("
                UPDATE `hits` SET
                    `count` = `count` + 1,
                    `value` = ((`value` * `count`) + :value) / (`count` + 1),
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
                    INSERT INTO `hits`
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
                    INSERT INTO `$collection`
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

        $request['has_error'] = $request['error'] ? 1 : 0;
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
}
