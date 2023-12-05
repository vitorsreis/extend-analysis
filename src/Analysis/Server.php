<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend\Analysis;

use VSR\Extend\Analysis\Contract\AbstractChart;

class Server extends AbstractChart
{
    /**
     * @param int $uptime
     * @param float $cpu
     * @param int $thread_total
     * @param int $thread_running
     * @param int $thread_sleeping
     * @param int $thread_stopped
     * @param int $thread_zombie
     * @param float $mem_total
     * @param float $mem_free
     * @param float $mem_used
     * @param float $mem_cache
     * @param float $swa_total
     * @param float $swa_free
     * @param float $swa_used
     * @param float $swa_cache
     * @return bool
     */
    public function save(
        $uptime,
        $cpu,
        $thread_total,
        $thread_running,
        $thread_sleeping,
        $thread_stopped,
        $thread_zombie,
        $mem_total,
        $mem_free,
        $mem_used,
        $mem_cache,
        $swa_total,
        $swa_free,
        $swa_used,
        $swa_cache
    ) {
        $disk_total = number_format(disk_total_space('/') / 1024 / 1024 / 1024, 3, '.', '');
        $disk_free = number_format(disk_free_space('/') / 1024 / 1024 / 1024, 3, '.', '');
        $disk_used = number_format($disk_total - $disk_free, 3, '.', '');

        return $this->chart(
            ['c', 's10', 'i', 'i10', 'h', 'd'],
            ['id' => 'uptime', 'value' => $uptime],
            ['id' => 'cpu', 'value' => $cpu],
            ['id' => 'thread-total', 'value' => $thread_total],
            ['id' => 'thread-running', 'value' => $thread_running],
            ['id' => 'thread-sleeping', 'value' => $thread_sleeping],
            ['id' => 'thread-stopped', 'value' => $thread_stopped],
            ['id' => 'thread-zombie', 'value' => $thread_zombie],
            ['id' => 'mem-total', 'value' => $mem_total],
            ['id' => 'mem-free', 'value' => $mem_free],
            ['id' => 'mem-used', 'value' => $mem_used],
            ['id' => 'mem-cache', 'value' => $mem_cache],
            ['id' => 'swa-total', 'value' => $swa_total],
            ['id' => 'swa-free', 'value' => $swa_free],
            ['id' => 'swa-used', 'value' => $swa_used],
            ['id' => 'swa-cache', 'value' => $swa_cache],
            ['id' => 'disk-total', 'value' => $disk_total],
            ['id' => 'disk-free', 'value' => $disk_free],
            ['id' => 'disk-used', 'value' => $disk_used]
        );
    }
}
