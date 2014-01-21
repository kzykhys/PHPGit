<?php

use PHPGit\Exception\GitException;
use PHPGit\Git;

class GitExceptionTest extends PHPUnit_Framework_TestCase
{

    public function testException()
    {
        $git = new Git();
        try {
            $git->status();
            $this->fail('Previous operation should fail');
        } catch (GitException $e) {
            $command = $e->getCommandLine();
            $command = str_replace(array('"', "'"), '', $command);
            $this->assertStringEndsWith('status --porcelain -s -b --null', $command);
        }
    }

} 