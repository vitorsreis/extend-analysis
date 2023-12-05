<?php // phpcs:disable

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

$stdin = file_get_contents('php://stdin') ?: '';
$pattern = "
~
    top\s+-\s+(?<hour>[\d:]+)\s+up\s+(?:(?<up_days>\d+)\s+days?,\s+)?(?:(?<up_hour>[\d:]+),\s+)?(?:(?<up_min>\d+)\s+min,\s+)?(?<users>\d+)\s+user(?:s)?(?:,\s+load\s+average:\s+(?<la1>[\d.]+),\s+(?<la2>[\d.]+),\s+(?<la3>[\d.]+))?.*
    (?:Threads|Tasks):\s+(?<thr_total>\d+)\s+total,\s+(?<thr_running>\d+)\s+running,\s+(?<thr_sleeping>\d+)\s+sleeping,\s+(?<thr_stopped>\d+)\s+stopped,\s+(?<thr_zombie>\d+)\s+zombie\s+
    %Cpu\(s\):\s+(?<cpu>[\d.]+)\s+us(?:,\s+(?<cpu_sy>[\d.]+)\s+sy,\s+(?<cpu_ni>[\d.]+)\s+ni,\s+(?<cpu_id>[\d.]+)\s+id,\s+(?<cpu_wa>[\d.]+)\s+wa,\s+(?<cpu_hi>[\d.]+)\s+hi,\s+(?<cpu_si>[\d.]+)\s+si,\s+(?<cpu_st>[\d.]+)\s+st)?.*
    MiB\s+Mem\s+:\s+(?<mem_total>[\d.]+)\s+total,\s+(?<mem_free>[\d.]+)\s+free,\s+(?<mem_used>[\d.]+)\s+used(?:,\s+(?<mem_cache>[\d.]+)\s+buff\s+)?.*
    MiB\s+Swap:\s+(?<swa_total>[\d.]+)\s+total,\s+(?<swa_free>[\d.]+)\s+free,\s+(?<swa_used>[\d.]+)\s+used(?:\.\s+(?<swa_cache>[\d.]+)\s+avail)?.*
~xs
";

require_once __DIR__ . '/../vendor/autoload.php';

if (!preg_match($pattern, $stdin, $matches)) {
    error_log('Extends\Analysis::Top: Failed to match the pattern:' . PHP_EOL . $stdin . PHP_EOL);
    return;
}

$uptime = 0;
isset($matches['up_days']) && $uptime += $matches['up_days'] * 24 * 60;
isset($matches['up_hour']) && $uptime += $matches['up_hour'] * 60;
isset($matches['up_min']) && $uptime += $matches['up_min'] * 1;

(new VSR\Extend\Analysis\Server())->save(
    $uptime,
    (float)$matches['cpu'],
    (int)$matches['thr_total'],
    (int)$matches['thr_running'],
    (int)$matches['thr_sleeping'],
    (int)$matches['thr_stopped'],
    (int)$matches['thr_zombie'],
    (float)$matches['mem_total'],
    (float)$matches['mem_free'],
    (float)$matches['mem_used'],
    (float)$matches['mem_cache'],
    (float)$matches['swa_total'],
    (float)$matches['swa_free'],
    (float)$matches['swa_used'],
    (float)$matches['swa_cache']
);
