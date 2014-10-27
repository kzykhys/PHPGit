<?php

use PHPGit\Git;

require_once __DIR__ . '/BaseTestCase.php';

class GitConfigTest extends BaseTestCase
{

    public function testConfigSetAndList()
    {
        $config = array(
            'user.name' => 'JohnDoe',
            'user.email' => 'john.doe@example.com'
        );
        $gitConfig = new \PHPGit\Config($config);

        $processBuilder = new \Symfony\Component\Process\ProcessBuilder();
        $processBuilder->add('--version');
        $gitConfig->configureProcess($processBuilder);
        $process = $processBuilder->getProcess();

        $commandLine = $process->getCommandLine();
        foreach($config as $option => $value) {
            $this->assertTrue(strpos($commandLine, $option) !== false, 'Option '.$option.' not in command');
            $this->assertTrue(strpos($commandLine, $value) !== false, 'Option '.$option.' value '.$value.' not in command');
        }

    }

} 