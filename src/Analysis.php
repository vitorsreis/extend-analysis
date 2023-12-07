<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend;

use RuntimeException;
use VSR\Extend\Analysis\Contract\ModelInterface;

class Analysis
{
    /**
     * @var ModelInterface
     */
    private static $model;

    /**
     * @return ModelInterface
     */
    public static function getModel()
    {
        if (!isset(static::$model)) {
            throw new RuntimeException('Driver not defined');
        }
        return static::$model;
    }

    public static function setModel(ModelInterface $model)
    {
        static::$model = $model;
    }
}
