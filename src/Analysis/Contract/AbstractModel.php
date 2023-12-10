<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend\Analysis\Contract;

abstract class AbstractModel
{
    /**
     * @var int group every second
     */
    const HIT_NO_GROUP = 1;

    /**
     * @var int group every 10 seconds
     */
    const HIT_SECOND_10 = 2;

    /**
     * @var int group every minute
     */
    const HIT_MINUTE = 4;

    /**
     * @var int group every 10 minutes
     */
    const HIT_MINUTE_10 = 8;

    /**
     * @var int group every hour
     */
    const HIT_HOUR = 16;

    /**
     * @var int group every day
     */
    const HIT_DAY = 32;

    /**
     * @var int group with every mode
     */
    const HIT_ALL = self::HIT_SECOND_10 | self::HIT_MINUTE | self::HIT_MINUTE_10 | self::HIT_HOUR | self::HIT_DAY;

    abstract public function install();

    abstract public function processRequest($request);

    abstract public function processServer($data);

    abstract public function getDatabaseSize();

    abstract public function getViewerData($options);

    abstract public function getRequestData($id);

    /**
     * @param int $group
     * @param array{id:string, value:mixed} ...$data
     * @return array
     */
    protected static function hitGroup($group, ...$data)
    {
        $hits = [];
        $now = date('Y-m-d H:i:s');

        foreach ($data as $i) {
            if (!$group || $group & static::HIT_NO_GROUP) {
                $hits[] = [
                    'key' => $i['key'],
                    'type' => isset($i['type']) ? $i['type'] : '',
                    'ref' => isset($i['ref']) ? $i['ref'] : '',
                    'value' => $i['value']
                ];
            }
            if ($group & static::HIT_SECOND_10) {
                $hits[] = [
                    'key' => $i['key'],
                    'type' => 's10',
                    'ref' => strtotime(substr($now, 0, -1) . '0'),
                    'value' => $i['value']
                ];
            }
            if ($group & static::HIT_MINUTE) {
                $hits[] = [
                    'key' => $i['key'],
                    'type' => 'i',
                    'ref' => strtotime(substr($now, 0, -2) . '00'),
                    'value' => $i['value']
                ];
            }
            if ($group & static::HIT_MINUTE_10) {
                $hits[] = [
                    'key' => $i['key'],
                    'type' => 'i10',
                    'ref' => strtotime(substr($now, 0, -4) . '0:00'),
                    'value' => $i['value']
                ];
            }
            if ($group & static::HIT_HOUR) {
                $hits[] = [
                    'key' => $i['key'],
                    'type' => 'h',
                    'ref' => strtotime(substr($now, 0, -5) . ':00:00'),
                    'value' => $i['value']
                ];
            }
            if ($group & static::HIT_DAY) {
                $hits[] = [
                    'key' => $i['key'],
                    'type' => 'd',
                    'ref' => strtotime(substr($now, 0, -8) . '00:00:00'),
                    'value' => $i['value']
                ];
            }
        }

        unset($now, $i, $group, $type, $data);
        return $hits;
    }
}
