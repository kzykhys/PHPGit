<?php

namespace PHPGit\Command;

use PHPGit\Command;

/**
 * Show line details about the author - `git blame`
 *
 * @author Marco Buttini <marco.buttini@gmail.com>
 */
class BlameCommand extends Command
{
    /**
     * Statuses of the parser
     * 
     * These statuses are used by the parser to detect in which section we are 
     * of the git-blame output.
     */
    /**
     * Start to parse.
     * @var int
     */
    const IS_START = 0;
    
    /**
     * Has collected the hash.
     * @var int
     */
    const HAS_HASH = 1;
    
    /**
     * Has collected the name.
     * @var int
     */
    const HAS_NAME = 2;
    
    /**
     * Has collected the date.
     * @var int
     */
    const HAS_DATE = 3;

    /**
     * Define the current file line number that we are trying to blame.
     * @var int
     */
    protected $fileLineNumber = 0;

    /**
     * Define the current position in the git blame output.
     * @var int
     */
    protected $outputIndex = -1;

    /**
     * Counter used to understand where we are in the porcelain block.
     * @var int
     */
    protected $blockCounter = 0;

    /**
     * Contain the git blame output.
     * @var array
     */
    protected $gitOutput = [];

    /**
     * Define the current file line number we are parsing.
     * @var int
     */
    protected $status = self::IS_START;

    /**
     * Blame lines
     *
     * ##### Output Example
     *
     * ``` php
     * [
     *     0 => [
     *         'index' => '1',
     *         'hash'  => '1a821f3f8483747fd045eb1f5a31c3cc3063b02b',
     *         'name'  => 'John Doe',
     *         'date'  => 'Fri Jan 17 16:32:49 2014 +0900',
     *         'line'  => '<?php'
     *     ],
     *     1 => [
     *         //...
     *     ]
     * ]
     * ```
     */
    protected $blameLines = []; 

    /**
     * Returns the commit logs
     *
     * ``` php
     * $git = new PHPGit\Git();
     * $git->setRepository('/path/to/repo');
     * $lines = $git->blame('/file_to_blame');
     * $lines = $git->blame('/file_to_blame', '1a821f3f8483747fd045eb1f5a31c3cc3063b02b');
     * ```
     *
     * ##### Output Example
     *
     * ``` php
     * [
     *     0 => [
     *         'index' => '1',
     *         'hash'  => '1a821f3f8483747fd045eb1f5a31c3cc3063b02b',
     *         'name'  => 'John Doe',
     *         'date'  => 'Fri Jan 17 16:32:49 2014 +0900',
     *         'line'  => '<?php'
     *     ],
     *     1 => [
     *         //...
     *     ]
     * ]
     * ```
     *
     *
     * @param string $file     The file that we want to blame.
     * @param string $hash     [optional] The hash of the version of the file that we want to blame.
     *
     * @return array
     */
    public function __invoke($file, $hash = null)
    {
        $blameLines = array();

        $builder = $this->git->getProcessBuilder()
            ->add('blame')
            ->add('--line-porcelain')
            ->add($file);

        if ($hash) {
            $builder->add($hash);
        }

        $output = $this->git->run($builder->getProcess());
        $lines  = $this->split($output);
        $this->parse($lines);

        return $this->blameLines;
    }


    public function parse($lines)
    {
        $this->gitOutput = $lines;
        
        $blameLine = $this->dispachBlameArray();
        while ($this->outputIndex <= count($lines)) {
            $this->loadLine();

            if (self::IS_START === $this->status) {
                $blameLine['hash'] = $this->extractHash($this->currentLine);
                $this->status = self::HAS_HASH;
                continue;
            }

            if (self::HAS_HASH === $this->status) {
                $blameLine['author'] = $this->extractAuthor($this->currentLine);
                $this->status = self::HAS_NAME;
                continue;
            }

            if (self::HAS_NAME === $this->status && $this->blockCounter === 4) {
                $blameLine['date'] = $this->extractDate($this->currentLine, $this->nextLine);
                $this->status = self::HAS_DATE;
                continue;
            }

            if (self::HAS_DATE === $this->status && $this->blockCounter === 12) {
                $blameLine['line_content'] = $this->currentLine;

                $this->blameLines[] = $blameLine;
                $blameLine = $this->dispachBlameArray();
                continue;
            }
        }
    }

    /**
     * It inits a new array to contain the information for the next putput line.
     * It updates status and counter as single atomic operation.
     * 
     * @return array
     */
    public function dispachBlameArray()
    {
        $this->fileLineNumber++;
        $this->blockCounter = 0;
        $this->status = self::IS_START;
        return [
            'line_number'   => $this->fileLineNumber,
            'hash'          => '',
            'author'        => '',
            'date'          => '',
            'line_content'  => ''
        ];
    }

    /**
     * It return a specific output line after it checks the line exists.
     * 
     * @return string|null
     */
    public function getLine($number)
    {
        $line = null;
        
        if (isset($this->gitOutput[$number])) {
            $line = $this->gitOutput[$number];
        }

        return $line;
    }

    /**
     * Load the current line, the next line and
     * It does update the counters time as single atomic operation.
     * 
     * @return void
     */
    public function loadLine()
    {
        $this->outputIndex++;
        $this->blockCounter++;
        $this->currentLine = $this->getLine($this->outputIndex);
        $this->nextLine = $this->getLine($this->outputIndex + 1);
    }

    /**
     * Extract commit hash from output line.
     *
     * @param string $line The hash raw line
     *
     * @return string
     */
    static public function extractHash($line)
    {
        $line  = trim($line);
        $parts = explode(" ", $line);

        return $parts[0];
    }

    /**
     * Extract author name line from output.
     *
     * @param string $author The author line
     *
     * @return string
     */
    static public function extractAuthor($author)
    {
        $author = str_replace("author", "", $author);

        return trim($author);
    }

    /**
     * Extract date line from output.
     *
     * @param string $timestamp The author-time
     * @param string $timezone The author-tz
     *
     * @return string
     */
    static public function extractDate($timestamp, $timezone)
    {
        $timestamp = trim(str_replace("author-time", "", $timestamp));
        $timezone  = trim(str_replace("author-tz", "", $timezone));

        // eg: +1000 is 10 hours 
        // 10h * 60 min * 60 sec
        $timezoneSecsDelta = (int) $timezone / 100 * 60 * 60;
        $timestamp += $timezoneSecsDelta;

        return gmdate("Y-m-d H:i:s " . $timezone, $timestamp);
    }
}
 