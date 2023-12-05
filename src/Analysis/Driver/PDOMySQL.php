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

class PDOMySQL implements DriverInterface
{
    /**
     * @var PDO
     */
    protected $instance;

    protected $lastId = false;

    protected $affected = [];

    public function __construct($hostname, $username, $password, $database, $port = 3306)
    {
        if (extension_loaded('pdo_mysql') === false) {
            throw new RuntimeException('PDO MySQL extension is not loaded');
        }

        $this->instance = new PDO("mysql:host=$hostname;dbname=$database;port=$port", $username, $password);
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
                $this->affected[] = isset($fields['id']) ? $fields['id'] : $this->lastId;
            }
            return $this->instance->commit();
        } catch (Exception $th) {
            $this->lastId = false;
            $this->affected = false;
            $this->instance->inTransaction() && $this->instance->rollBack();
            throw $th;
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
                !empty($config['ai']) && $sql .= " AUTO_INCREMENT";
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

        $columns = [];
        foreach (current($data) as $k => $i) {
            $columns[] = "`$k`=:$k";
        }

        return $this->query("
            REPLACE INTO
                `$collection`
            SET
                " . implode("," . PHP_EOL, $columns) . "
        ", $data);
    }

    public function avg($collection, ...$data)
    {
        if (!$data) {
            return false;
        }

        return $this->query("
            INSERT INTO
                `$collection`
            SET
                `id` = :id,
                `count` = 1,
                `value` = :value
            ON DUPLICATE KEY UPDATE
                `value` = ((`value` * `count`) + :value) / (`count` + 1),
                `count` = `count` + 1
        ", $data);
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
