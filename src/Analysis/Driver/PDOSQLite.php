<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend\Analysis\Driver;

use Exception;
use PDO;
use RuntimeException;
use VSR\Extend\Analysis\Contract\DriverInterface;

class PDOSQLite implements DriverInterface
{
    /**
     * @var PDO
     */
    protected $instance;

    protected $lastId = false;

    protected $affected = [];

    /**
     * @param string $database File path
     */
    public function __construct($database)
    {
        if (extension_loaded('pdo_sqlite') === false) {
            throw new RuntimeException('PDO SQLite extension is not loaded');
        }

        $this->instance = new PDO("sqlite:$database");
    }

    private function query($command, $values = [])
    {
        $this->lastId = false;
        $this->affected = [];

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

            $this->instance->beginTransaction();
            foreach ($values as $fields) {
                if (!$statement->execute($fields)) {
                    throw new Exception($statement->errorInfo()[2], $statement->errorInfo()[1]);
                }
                $this->lastId = $this->instance->lastInsertId();
                $statement->rowCount() && $this->affected[] = isset($fields['id']) ? $fields['id'] : $this->lastId;
            }

            return $this->instance->commit();
        } catch (Exception $th) {
            $this->lastId = false;
            $this->affected = false;
            $this->instance->inTransaction() && $this->instance->rollBack();
            throw $th;
        } finally {
            unset($command, $values, $statement);
        }
    }

    public function install($collections)
    {
        foreach ($collections as $collection => $fields) {
            $primary = [];

            $sql = "CREATE TABLE IF NOT EXISTS `$collection` (" . PHP_EOL;
            $sql .= implode(',' . PHP_EOL, array_map(static function ($field, $config) use (&$primary) {
                $sql = "`$field` $config[type]";
                isset($config['length']) && $sql .= "({$config['length']})";
                !empty($config['primary']) && ($sql .= " PRIMARY KEY") && $primary[] = "`$field`";
                !empty($config['ai']) && $sql .= " AUTOINCREMENT";
                empty($config['null']) && empty($config['primary']) && $sql .= " NOT NULL";
                !empty($config['null']) && $sql .= " DEFAULT NULL";
                return $sql;
            }, array_keys($fields), $fields));
            if (count($primary) > 1) {
                $sql = str_replace(' PRIMARY KEY', '', $sql);
                !empty($primary) && $sql .= PHP_EOL . ", PRIMARY KEY (" . implode(',', $primary) . ")";
            }
            $sql .= PHP_EOL . ");";
            $this->query($sql);
        }
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

        $this->query($command = "
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
