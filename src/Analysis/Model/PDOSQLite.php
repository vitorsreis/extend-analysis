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
use VSR\Extend\Analysis\Contract\ModelInterface;

class PDOSQLite extends AbstractModel implements ModelInterface
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
            $x = $this->instance->query('select sqlite_version()')->fetch()[0],
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
                $statement->rowCount() && $this->affected[] = isset($fields['id']) ? $fields['id'] : $this->lastId;
                $this->rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
            return !$this->instance->inTransaction() || $this->instance->commit();
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
            CREATE TABLE IF NOT EXISTS `server` (
                `id` varchar(255) NOT NULL,
                `ref` varchar(255) DEFAULT NULL,
                `count` bigint DEFAULT NULL,
                `value` float DEFAULT NULL,
                `create_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `update_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`,`ref`)
            )
        ");
        $this->query("
            CREATE TABLE IF NOT EXISTS `routes` (
                `id` integer PRIMARY KEY,
                `route_key` varchar(255) NOT NULL,
                `avg` float DEFAULT NULL,
                `min` float DEFAULT NULL,
                `max` float DEFAULT NULL,
                `last` float DEFAULT NULL,
                `hits` bigint DEFAULT NULL,
                `create_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `update_at` datetime DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (`route_key`)
            )
        ");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_routes-hits` ON `routes` (`hits`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_routes-avg` ON `routes` (`avg`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_routes-min` ON `routes` (`min`)");
        $this->query("CREATE INDEX IF NOT EXISTS `idx_routes-max` ON `routes` (`max`)");
        $this->query("
            CREATE TABLE IF NOT EXISTS `requests` (
                `id` integer PRIMARY KEY AUTOINCREMENT,
                `route_key_id` integer NOT NULL,
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
        $this->query("CREATE INDEX IF NOT EXISTS `idx_requests-route_key_id` ON `requests` (`route_key_id`)");
        $this->query("
            CREATE TABLE IF NOT EXISTS `profiles` (
                `request_id` integer,
                `id` integer,
                `parent_id` integer,
                `title` varchar(255) NOT NULL,
                `start` float NOT NULL,
                `end` float NOT NULL,
                `duration` float NOT NULL,
                `memory` bigint DEFAULT NULL,
                `memory_peak` bigint DEFAULT NULL,
                `error` json DEFAULT NULL,
                `extra` json DEFAULT NULL,
                PRIMARY KEY (`request_id`,`id`,`parent_id`)
            )
        ");
    }

    public function processRequest($request)
    {
        $this->processAvg($this->group(
            static::HIT_NO_GROUP,
            ['id' => 'current-req', 'value' => $request['duration']]
        ));
        unset($group, $values);

        $request['route_key_id'] = $this->processRouteKey($request['route_key'], $request['duration']);
        $request['has_error'] = $request['error'] ? 1 : 0;
        $profile = $request['profile'];
        unset($request['route_key'], $request['profile']);

        foreach ($request as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $request[$key] = $value
                    ? (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null)
                    : null;
            }
        }

        $columns = $values = [];
        foreach (array_keys($request) as $i) {
            $columns[] = "`$i`";
            $values[] = ":$i";
        }

        $this->query("
            INSERT INTO `requests` (" . implode(",", $columns) . ")
            VALUES (" . implode(",", $values) . ")
        ", $request);
        unset($request);

        $request_id = $this->getLastId();

        foreach ($profile as $i => $item) {
            $profile[$i]['id'] = $i;
            $profile[$i]['request_id'] = $request_id;
            $profile[$i]['extra'] = $profile[$i]['extra']
                ? (json_encode($profile[$i]['extra'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null)
                : null;
            $profile[$i]['error'] = $profile[$i]['error']
                ? (json_encode($profile[$i]['error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null)
                : null;
        }
        unset($i, $item);

        $columns = $values = [];
        foreach (array_keys(current($profile)) as $i) {
            $columns[] = "`$i`";
            $values[] = ":$i";
        }

        $this->query("
            INSERT INTO `profiles` (" . implode(",", $columns) . ")
            VALUES (" . implode(",", $values) . ")
        ", ...$profile);
        unset($profile, $columns, $values);

        return $request_id;
    }

    private function processRouteKey($route_key, $duration)
    {
        if ($this->onConflictSupport) {
            $this->query("
                INSERT INTO `routes`
                    (`route_key`, `hits`, `avg`, `min`, `max`, `last`)
                VALUES
                    (:route_key, 1, :duration, :duration, :duration, :duration)
                ON CONFLICT (`route_key`) DO UPDATE SET
                    `hits` = `hits` + 1,
                    `avg` = ((`avg` * `hits`) + :duration) / (`hits` + 1),
                    `min` = CASE WHEN `min` > :duration THEN :duration ELSE `min` END,
                    `max` = CASE WHEN `max` < :duration THEN :duration ELSE `max` END,
                    `last` = :duration,
                    `update_at` = CURRENT_TIMESTAMP
            ", [
                ':route_key' => $route_key,
                ':duration' => $duration
            ]);
        } else {
            $this->query("
                UPDATE `routes` SET
                    `hits` = `hits` + 1,
                    `avg` = ((`avg` * `hits`) + :duration) / (`hits` + 1),
                    `min` = CASE WHEN `min` > :duration THEN :duration ELSE `min` END,
                    `max` = CASE WHEN `max` < :duration THEN :duration ELSE `max` END,
                    `last` = :duration,
                    `update_at` = CURRENT_TIMESTAMP
                WHERE
                    `route_key` = :route_key
            ", [
                ':route_key' => $route_key,
                ':duration' => $duration
            ]);

            if (!$this->getAffectedCount()) {
                $this->query("
                    INSERT INTO `routes`
                        (`route_key`, `hits`, `avg`, `min`, `max`, `last`)
                    VALUES
                        (:route_key, 1, :duration, :duration, :duration, :duration)
                ", [
                    ':route_key' => $route_key,
                    ':duration' => $duration
                ]);

                return $this->getLastId();
            }
        }

        $this->query("
            SELECT
                `id`
            FROM
                `routes`
            WHERE
                `route_key` = :route_key
        ", [
            ':route_key' => $route_key
        ]);

        return $this->rows[0]['id'];
    }

    private function processAvg($hits)
    {
        if ($this->onConflictSupport) {
            $this->query("
                INSERT INTO `server`
                    (`id`, `ref`, `count`, `value`)
                VALUES
                    (:id, :ref, 1, :value)
                ON CONFLICT (`id`, `ref`) DO UPDATE SET
                    `count` = `count` + 1,
                    `value` = ((`value` * `count`) + :value) / (`count` + 1),
                    `update_at` = CURRENT_TIMESTAMP
            ", ...$hits);
        } else {
            $this->query("
                UPDATE `server` SET
                    `count` = `count` + 1,
                    `value` = ((`value` * `count`) + :value) / (`count` + 1),
                    `update_at` = CURRENT_TIMESTAMP
                WHERE
                    `id` = :id
                    AND `ref` = :ref
            ", ...$hits);

            $hits = array_filter($hits, function ($i) {
                return !in_array($i['id'], $this->affected, true);
            });

            if (!$this->getAffectedCount()) {
                $this->query("
                    INSERT INTO `server`
                        (`id`, `ref`, `count`, `value`)
                    VALUES
                        (:id, :ref, 1, :value)
                ", ...$hits);
            }
        }
        unset($hits);
    }

    public function processServer($data)
    {
        // TODO: Implement processServer() method.
    }


    public function put($collection, ...$data)
    {
        if (!$data) {
            return false;
        }

        $columns = $values = [];
        foreach (current($data) as $k => $i) {
            $columns[] = "`$k`";
            $values[] = ":$k";
        }

        return $this->query("
            REPLACE INTO `$collection` (
                " . implode(',' . PHP_EOL, $columns) . "
            ) VALUES (
                " . implode(',' . PHP_EOL, $values) . "
            )
        ", $data);
    }

    public function avg($collection, ...$data)
    {
        if (!$data) {
            return false;
        }

        $this->query("
            UPDATE `$collection` SET
                `value` = ((`value` * `count`) + :value) / (`count` + 1),
                `count` = `count` + 1
            WHERE
                `id` = :id
        ", $data);

        $data = array_filter($data, function ($i) {
            return !in_array($i['id'], $this->affected, true);
        });

        $data && $this->query("
            INSERT INTO `$collection`
                (`id`, `count`, `value`)
            VALUES
                (:id, 1, :value)
        ", $data);

        return !$data;
    }

    public function getLastId()
    {
        return $this->lastId;
    }

    public function getAffectedCount()
    {
        return $this->affected === false ? false : count($this->affected);
    }
}
