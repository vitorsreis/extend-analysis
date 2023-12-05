<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend\Analysis\Contract;

interface DriverInterface
{
    /**
     * @param array $collections
     * @return bool
     */
    public function install($collections);

    /**
     * @param string $collection
     * @param array ...$data
     * @return bool
     */
    public function put($collection, ...$data);

    /**
     * @param string $collection
     * @param array{id:string, value:mixed} ...$data
     * @return bool
     */
    public function avg($collection, ...$data);

    /**
     * @return int|string|false
     */
    public function getLastId();

    /**
     * @return int|false
     */
    public function getAffectedCount();
}
