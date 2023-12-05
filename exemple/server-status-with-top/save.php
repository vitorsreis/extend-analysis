<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

use VSR\Extend\Analysis;

$stdin = file_get_contents('php://stdin') ?: '';

if (!$stdin) {
    exit;
}

require_once __DIR__ . '/../utils/config.php';

Analysis\Server::save(
    Analysis\Server\Normalize\Top::normalize($stdin)
);
