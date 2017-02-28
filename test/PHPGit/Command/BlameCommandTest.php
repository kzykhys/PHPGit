<?php

use PHPGit\Git;
use PHPGit\Command\BlameCommand;
use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__ . '/../BaseTestCase.php';

class BlameCommandTest extends BaseTestCase
{

    public function testBlame()
    {
        $git = new Git();
        $git->init($this->directory);
        $git->setRepository($this->directory);

        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->directory . '/test.txt', 'test');
        $git->add('test.txt');
        $git->commit('Initial commit');
        $blameLines = $git->blame('test.txt');

        $this->assertCount(1, $blameLines);
    }

    public function testExtractHash()
    {
        $test_cases = [
            [
                'hash' => "fb1b3998b17d610ab8ee401a7d4ed06cf50168a6 1 1",
                'equal'  => "fb1b3998b17d610ab8ee401a7d4ed06cf50168a6",
            ], [
                'hash' => " 35df62c82934fe82f988944b339bc1195a35d94f 1 2 3",
                'equal'  => "35df62c82934fe82f988944b339bc1195a35d94f",
            ],
        ];

        foreach ($test_cases as $set) {
            $hash = BlameCommand::extractHash($set['hash']);
            $this->assertEquals($set['equal'], $hash);
        }
    }

    public function testExtractAuthor()
    {
        $test_cases = [
            [
                'author' => "author Jon Doe",
                'equal'  => "Jon Doe",
            ], [
                'author' => "author Jon Doe Black",
                'equal'  => "Jon Doe Black",
            ],
        ];

        foreach ($test_cases as $set) {
            $author = BlameCommand::extractAuthor($set['author']);
            $this->assertEquals($set['equal'], $author);
        }
    }

    public function testExtractDate()
    {
        $test_cases = [
            [
                'timestamp' => "author-time 1435738804",
                'timezone'  => "author-tz +1000",
                'equal'     => "2015-07-01 18:20:04 +1000",
            ], [
                'timestamp' => "author-time 1435738804",
                'timezone'  => "author-tz -1000",
                'equal'     => "2015-06-30 22:20:04 -1000",
            ],
        ];

        foreach ($test_cases as $set) {
            $date = BlameCommand::extractDate($set['timestamp'], $set['timezone']);
            $this->assertEquals($set['equal'], $date);
        }
    }

} 