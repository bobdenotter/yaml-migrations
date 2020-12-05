<?php

declare(strict_types=1);

namespace YamlMigrate;

use Colors\Color;
use Composer\Semver\Comparator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Webimpress\SafeWriter\FileWriter;

class Migrate
{
    /** @var array */
    private $config;

    /** @var string */
    private $checkpoint;

    /** @var Color */
    private $color;

    /** @var bool */
    private $verbose = false;

    /** @var bool */
    private $silent = false;



    public function __construct(string $configFilename)
    {
        $this->initialize($configFilename);
        $this->initColor();
    }

    private function initialize(string $configFilename): void
    {
        if (file_exists($configFilename)) {
            $this->config = Yaml::parseFile($configFilename);
        } elseif (file_exists(\dirname(__DIR__).'/'.$configFilename)) {
            $this->config = Yaml::parseFile(\dirname(__DIR__).'/'.$configFilename);
        } else {
            die("Config file ${configFilename} not found.");
        }

        if (file_exists($this->config['migrations'].'/checkpoint.txt')) {
            $this->checkpoint = trim(file_get_contents($this->config['migrations'].'/checkpoint.txt'));
        }
    }

    public function list(): array
    {
        $list = $this->getListToProcess();

        $this->output('Files to process: ', true, 'important');
        foreach ($list as $filename) {
            $filename = str_replace(\dirname(__DIR__), '…', $filename);
            $this->output(' - ' . $filename);
        }

        return $list;
    }

    public function diff(): void
    {
    }

    public function process(string $onlyFilename = null): void
    {
        $list = $this->getListToProcess();

        $success = $this->processIterator($list, $onlyFilename);

        if ($success) {

        }
    }

    public function processIterator(array $list, string $onlyFilename = null): bool
    {
        if ($onlyFilename) {
            if (! array_key_exists($onlyFilename, $list)) {
                throw new \Exception("File '" . $onlyFilename. "' is not available in configured input folder.");
            }
            return $this->processFile($list[$onlyFilename]);

        }

        $success = true;

        foreach ($list as $filename) {
            $success = $success && $this->processFile($filename);
        }

        return $success;
    }

    private function processFile(string $filename): bool
    {
        $migration = Yaml::parseFile($filename, Yaml::PARSE_CUSTOM_TAGS);

        $inputFilename = sprintf('%s/%s', $this->config['source'], $migration['file']);
        $outputFilename = sprintf('%s/%s', $this->config['target'], $migration['file']);

        $data = Yaml::parseFile($inputFilename, Yaml::PARSE_CUSTOM_TAGS);

        $data = $this->doMigration($inputFilename, $data, $migration);

        $output = Yaml::dump($data, 4, 4, Yaml::DUMP_NULL_AS_TILDE);

        FileWriter::writeFile($outputFilename, $output);

        return true;
    }

    private function doMigration(string $filename, array $data, array $migration): array
    {
        $displayname = sprintf('%s/%s', basename(\dirname($filename)), basename($filename));

        $this->verboseOutput('Migrating ' . $displayname . ': ');

        if (\array_key_exists('add', $migration)) {
            echo 'Adding keys…';
            $data = $this->doMigrationAdd($data, $migration['add']);
        }

        echo PHP_EOL;

        return $data;
    }

    private function doMigrationAdd(array $data, array $add): array
    {
        return array_replace_recursive($data, $add);
    }

    private function getListToProcess()
    {
        $finder = new Finder();

        $files = $finder->files()->in($this->config['migrations'])->name('*.yaml');

        $list = [];

        foreach ($files as $file) {
            $yaml = Yaml::parseFile($file->getRealPath(), Yaml::PARSE_CUSTOM_TAGS);

            if (Comparator::greaterThan($yaml['since'], $this->checkpoint)) {
                $list[$file->getFilename()] = $file->getRealPath();
            }
        }

        return $list;
    }

    private function initColor()
    {
        $this->color = new Color();
        $this->color->setUserStyles(
            array(
                'success' => array('white', 'bg_green', 'bold'),
                'warning' => array('white', 'bg_red', 'bold'),
                'important' => 'bold',
            )
        );
    }

    private function output(string $str, bool $newLine = true, string $style = null): void
    {
        if ($this->silent) {
            return;
        }

        $output = ($this->color)($str);

        if ($style) {
            $output->apply($style);
        }

        echo $output . ($newLine ? "\n" : '');
    }

    private function verboseOutput(string $str, bool $newLine = true, string $style = null): void
    {
        if ($this->verbose) {
            $this->output($str, $newLine, $style);
        }
    }

    /**
     * @return bool
     */
    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    /**
     * @param bool $verbose
     */
    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    /**
     * @return bool
     */
    public function isSilent(): bool
    {
        return $this->silent;
    }

    /**
     * @param bool $silent
     */
    public function setSilent(bool $silent): void
    {
        $this->silent = $silent;
    }
}
