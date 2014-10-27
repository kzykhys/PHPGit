<?php

namespace PHPGit;

use Symfony\Component\Process\Process;

/**
 * Process Runner
 *
 * @author Moritz Schwörer <mr.mosch@gmail.com>
 */
interface ProcessRunner
{
    /**
     * @param Process $process
     * @return string
     */
    public function run(Process $process);
}