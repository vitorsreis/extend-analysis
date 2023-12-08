<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

use VSR\Extend\Analysis;

//$stdin = file_get_contents('php://stdin') ?: '';
$stdin = "top - 22:12:56 up 14 days, 23:29,  1 user,  load average: 0.00, 0.02, 0.07
Threads: 591 total,   1 running, 589 sleeping,   0 stopped,   1 zombie
%Cpu(s):  2.9 us,  2.9 sy,  0.0 ni, 94.1 id,  0.0 wa,  0.0 hi,  0.0 si,  0.0 st
MiB Mem :   3653.5 total,    186.6 free,   1797.5 used,   1669.5 buff/cache
MiB Swap:      0.0 total,      0.0 free,      0.0 used.   1361.2 avail Mem 

    PID USER      PR  NI    VIRT    RES    SHR S  %CPU  %MEM     TIME+ COMMAND                                                                                                                                                      
4102021 dev5      20   0   52952   4640   3484 R   6.2   0.1   0:00.02 top                                                                                                                                                          
      1 root      20   0  241276   8448   4276 S   0.0   0.2  25:57.62 systemd                                                                                                                                                      
      2 root      20   0       0      0      0 S   0.0   0.0   0:00.24 kthreadd                                                                                                                                                     
      3 root       0 -20       0      0      0 I   0.0   0.0   0:00.00 rcu_gp                                                                                                                                                       
      4 root       0 -20       0      0      0 I   0.0   0.0   0:00.00 rcu_par_gp                                                                                                                                                   
      5 root       0 -20       0      0      0 I   0.0   0.0   0:00.00 slub_flushwq                                                                                                                                                 
      7 root       0 -20       0      0      0 I   0.0   0.0   0:00.00 kworker/0:0H-events_highpri                                                                                                                                  
     10 root       0 -20       0      0      0 I   0.0   0.0   0:00.00 mm_percpu_wq                                                                                                                                                 
     11 root      20   0       0      0      0 S   0.0   0.0   0:00.00 rcu_tasks_rude_                                                                                                                                              
     12 root      20   0       0      0      0 S   0.0   0.0   0:00.00 rcu_tasks_trace                                                                                                                                              
     13 root      20   0       0      0      0 S   0.0   0.0   1:03.01 ksoftirqd/0                                                                                                                                                  
     14 root      20   0       0      0      0 I   0.0   0.0   7:12.09 rcu_sched                                                                                                                                                    
     15 root      rt   0       0      0      0 S   0.0   0.0   0:01.92 migration/0                                                                                                                                                  
     16 root      rt   0       0      0      0 S   0.0   0.0   0:03.80 watchdog/0                                                                                                                                                   
     17 root      20   0       0      0      0 S   0.0   0.0   0:00.00 cpuhp/0                                                                                                                                                      
     18 root      20   0       0      0      0 S   0.0   0.0   0:00.00 cpuhp/1                                                                                                                                                      
     19 root      rt   0       0      0      0 S   0.0   0.0   0:03.94 watchdog/1                                                                                                                                                   
     20 root      rt   0       0      0      0 S   0.0   0.0   0:01.99 migration/1                                                                                                                                                  
     21 root      20   0       0      0      0 S   0.0   0.0   1:03.59 ksoftirqd/1                                                                                                                                                  
     23 root       0 -20       0      0      0 I   0.0   0.0   0:00.00 kworker/1:0H-events_highpri";

if (!$stdin) {
    exit;
}

require_once __DIR__ . '/../config.php';

Analysis\Server::save(
    Analysis\Server\Normalize\Top::normalize($stdin)
);
