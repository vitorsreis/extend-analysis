<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend\Analysis\Contract;

use VSR\Extend\Analysis;

abstract class AbstractModel
{
    /**
     * @var int group every second
     */
    const HIT_NO_GROUP = 1;

    /**
     * @var int group every second
     */
    const HIT_SECOND = 2;

    /**
     * @var int group every 10 seconds
     */
    const HIT_SECOND_10 = 4;

    /**
     * @var int group every minute
     */
    const HIT_MINUTE = 8;

    /**
     * @var int group every 10 minutes
     */
    const HIT_MINUTE_10 = 16;

    /**
     * @var int group every hour
     */
    const HIT_HOUR = 32;

    /**
     * @var int group every day
     */
    const HIT_DAY = 64;

    /**
     * @var int group every month
     */
    const HIT_MONTH = 128;

    /**
     * @var int group every year
     */
    const HIT_YEAR = 256;

    /**
     * @param string $type "avg" or "put"
     * @param int $group
     * @param array{id:string, value:mixed} ...$data
     * @return array|false
     */
    protected static function group($group, ...$data)
    {
        $hits = [];
        $now = date('YmdHis');

        foreach ($data as $i) {
            if (!$group || $group & static::HIT_NO_GROUP) {
                $hits[] = ['id' => $i['id'], 'ref' => '', 'value' => $i['value']];
            }
            if ($group & static::HIT_SECOND) {
                $hits[] = ['id' => "s|$i[id]", 'ref' => $now, 'value' => $i['value']];
            }
            if ($group & static::HIT_SECOND_10) {
                $hits[] = ['id' => "s10|$i[id]", 'ref' => substr($now, 0, -1), 'value' => $i['value']];
            }
            if ($group & static::HIT_MINUTE) {
                $hits[] = ['id' => "i|$i[id]", 'ref' => substr($now, 0, -2), 'value' => $i['value']];
            }
            if ($group & static::HIT_MINUTE_10) {
                $hits[] = ['id' => "i10|$i[id]", 'ref' => substr($now, 0, -3), 'value' => $i['value']];
            }
            if ($group & static::HIT_HOUR) {
                $hits[] = ['id' => "h|$i[id]", 'ref' => substr($now, 0, -4), 'value' => $i['value']];
            }
            if ($group & static::HIT_DAY) {
                $hits[] = ['id' => "d|$i[id]", 'ref' => substr($now, 0, -6), 'value' => $i['value']];
            }
            if ($group & static::HIT_MONTH) {
                $hits[] = ['id' => "m|$i[id]", 'ref' => substr($now, 0, -8), 'value' => $i['value']];
            }
            if ($group & static::HIT_YEAR) {
                $hits[] = ['id' => "y|$i[id]", 'ref' => substr($now, 0, -10), 'value' => $i['value']];
            }
        }

        unset($now, $i, $group, $type, $data);
        return $hits;
    }
}
