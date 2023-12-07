<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend\Analysis\Contract;

interface ModelInterface
{
    public function install();

    public function processRequest($request);

    public function processServer($data);
}
