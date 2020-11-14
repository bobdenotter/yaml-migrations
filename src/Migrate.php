<?php

declare(strict_types=1);

namespace YamlMigrate;

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

    public function __construct(string $configFilename)
    {
        $this->initialize($configFilename);
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

    public function list(): void
    {
        $list = $this->getListToProcess();

        echo 'Files to process: ', PHP_EOL;
        foreach ($list as $filename) {
            $filename = str_replace(\dirname(__DIR__), '…', $filename);
            echo $filename, PHP_EOL;
        }
    }

    public function diff(): void
    {
    }

    public function process(): void
    {
        $list = $this->getListToProcess();

        foreach ($list as $filename) {
            $this->processFile($filename);
        }
    }

    private function processFile(string $filename): void
    {
        $migration = Yaml::parseFile($filename, Yaml::PARSE_CUSTOM_TAGS);

        $inputFilename = sprintf('%s/%s', $this->config['source'], $migration['file']);
        $outputFilename = sprintf('%s/%s', $this->config['target'], $migration['file']);

        $data = Yaml::parseFile($inputFilename, Yaml::PARSE_CUSTOM_TAGS);

        $data = $this->doMigration($inputFilename, $data, $migration);

        $output = Yaml::dump($data, 4, 4, Yaml::DUMP_NULL_AS_TILDE);

        FileWriter::writeFile($outputFilename, $output);
    }

    private function doMigration(string $filename, array $data, array $migration): array
    {
        $displayname = sprintf('%s/%s', basename(\dirname($filename)), basename($filename));
        echo 'Migrating '.$displayname.': ';

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
                $list[] = $file->getRealPath();
            }
        }

        return $list;
    }
}
