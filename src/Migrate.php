<?php

declare(strict_types=1);

namespace YamlMigrate;

use Colors\Color;
use Composer\Semver\Comparator;
use Symfony\Component\Filesystem\Filesystem;
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

    /** @var array */
    private $statistics = [];

    public function __construct(string $configFilename)
    {
        $this->initialize($configFilename);
        $this->initColor();
        $this->initStatistics();
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
            $this->output(' - '.$filename);
        }

        return $list;
    }

    public function process(?string $onlyFilename = null): void
    {
        $list = $this->getListToProcess();

        $success = $this->processIterator($list, $onlyFilename);

        if ($success) {
            $output = sprintf('Processed %s files. Updated: %s, skipped: %s', \count($list), $this->statistics['updated'], $this->statistics['skipped']);
            $this->output($output, true, 'success');

            // We only update the checkpoint if we process the list, not a single file
            if (! $onlyFilename && $this->statistics['updated'] > 0) {
                $this->output('Updating checkpoint to '.$this->checkpoint, true);
                FileWriter::writeFile($this->config['migrations'].'/checkpoint.txt', $this->checkpoint);
            }
        }
    }

    public function processIterator(array $list, ?string $onlyFilename = null): bool
    {
        if ($onlyFilename) {
            if (! \array_key_exists($onlyFilename, $list)) {
                throw new \Exception("File '".$onlyFilename."' is not available in configured input folder.");
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

        if (is_readable($inputFilename)) {
            $data = Yaml::parseFile($inputFilename, Yaml::PARSE_CUSTOM_TAGS);
        } else {
            $data = [];
            dump($data);
        }

        $migratedData = $this->doMigration($inputFilename, $data, $migration);

        if ($migratedData) {
            $output = Yaml::dump($migratedData, 4, 4, Yaml::DUMP_NULL_AS_TILDE);

            $filesystem = new Filesystem();
            $filesystem->mkdir(\dirname($outputFilename));

            FileWriter::writeFile($outputFilename, $output);
            // FileWriter::writeFile($outputFilename . '.bak',  Yaml::dump($data, 4, 4, Yaml::DUMP_NULL_AS_TILDE));
            $this->statistics['updated']++;
        }

        $this->setMaxCheckpoint($migration['since']);

        return true;
    }

    private function doMigration(string $filename, array $data, array $migration): ?array
    {
        $displayname = sprintf('%s/%s', basename(\dirname($filename)), basename($filename));

        $this->verboseOutput('Migrating '.$displayname.': ');

        $result = null;

        if (\array_key_exists('add', $migration)) {
            $result = $this->doMigrationAdd($data, $migration);
        }

        return $result;
    }

    private function doMigrationAdd(array $data, array $migration): ?array
    {
        $migratedData = ArrayMerge::merge($data, $migration['add']);

        if ($data === $migratedData) {
            $this->verboseOutput(" - File '".$migration['file']."' does not need updating");
            $this->statistics['skipped']++;

            return null;
        }

        $this->verboseOutput(' - Adding '.\count($migration['add']).' keys.');

        return $migratedData;
    }

    private function getListToProcess()
    {
        $finder = new Finder();

        $files = $finder->files()->in($this->config['migrations'])->name('m-*.yaml');

        $list = [];

        foreach ($files as $file) {
            $yaml = Yaml::parseFile($file->getRealPath(), Yaml::PARSE_CUSTOM_TAGS);

            if (Comparator::greaterThan($yaml['since'], $this->checkpoint)) {
                $list[$file->getFilename()] = $file->getRealPath();
            }
        }

        return $list;
    }

    private function initColor(): void
    {
        $this->color = new Color();
        $this->color->setUserStyles(
            [
                'success' => ['white', 'bg_green', 'bold'],
                'warning' => ['white', 'bg_red', 'bold'],
                'important' => 'bold',
            ]);
    }

    private function output(string $str, bool $newLine = true, ?string $style = null): void
    {
        if ($this->silent) {
            return;
        }

        $output = ($this->color)($str);

        if ($style) {
            $output->apply($style);
        }

        echo $output.($newLine ? "\n" : '');
    }

    private function verboseOutput(string $str, bool $newLine = true, ?string $style = null): void
    {
        if ($this->verbose) {
            $this->output($str, $newLine, $style);
        }
    }

    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    public function isSilent(): bool
    {
        return $this->silent;
    }

    public function setSilent(bool $silent): void
    {
        $this->silent = $silent;
    }

    private function initStatistics(): void
    {
        $this->statistics = [
            'updated' => 0,
            'skipped' => 0,
        ];
    }

    private function setMaxCheckpoint(string $version): void
    {
        if (Comparator::greaterThan($version, $this->checkpoint)) {
            $this->checkpoint = $version;
        }
    }
}
