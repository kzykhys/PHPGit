<?php

namespace PHPGit;

use Symfony\Component\Process\ProcessBuilder;

/**
 * Process Builder Provider
 *
 * @author Moritz Schwörer <mr.mosch@gmail.com>
 */
interface ProcessBuilderProvider
{
    /**
     * @return ProcessBuilder
     */
    public function getProcessBuilder();
}