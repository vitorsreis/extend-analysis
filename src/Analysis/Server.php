<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend\Analysis;

use VSR\Extend\Analysis;

class Server
{
    /**
     * @param array{
     *      uptime:int,
     *      cpu:float,
     *      thr_total:int,
     *      thr_running:int,
     *      thr_sleeping:int,
     *      thr_stopped:int,
     *      thr_zombie:int,
     *      mem_total:float,
     *      mem_free:float,
     *      mem_used:float,
     *      mem_cache:float,
     *      swa_total:float,
     *      swa_free:float,
     *      swa_used:float,
     *      swa_cache:float,
     *      disk_total:float,
     *      disk_free:float,
     *      disk_used:float
     *  }|false $normalized
     * uptime: in minutes
     * cpu: in percent
     * mem: in GB
     * swa: in GB
     * disk: in GB
     * @return bool
     */
    public static function save($normalized)
    {
        if (!$normalized) {
            return false;
        }

        $valid = false;
        $data = [];

        $keys = [
            'uptime',
            'cpu',
            'thr_total',
            'thr_running',
            'thr_sleeping',
            'thr_stopped',
            'thr_zombie',
            'mem_total',
            'mem_free',
            'mem_used',
            'mem_cache',
            'swa_total',
            'swa_free',
            'swa_used',
            'swa_cache',
            'disk_total',
            'disk_free',
            'disk_used'
        ];
        foreach ($keys as $key) {
            $data[$key] = !empty($normalized[$key]) ? $normalized[$key] : null;
            $valid = $valid || null !== $data[$key];
        }
        unset($keys, $normalized);

        if (!$valid) {
            return false;
        }

        return Analysis::getModel()->processServer($data);
    }
}
