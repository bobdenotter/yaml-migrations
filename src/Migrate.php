<?php

declare(strict_types=1);

namespace YamlMigrate;

use Composer\Semver\Comparator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class Migrate
{
    /** @var array */
    private $config;

    /**
     * @var string
     */
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

        if (file_exists($this->config['migrations'] . '/checkpoint.txt')) {
            $this->checkpoint = trim(file_get_contents($this->config['migrations'] . '/checkpoint.txt'));
        }
    }

    public function list(): void
    {
        $list = $this->getListToProcess();
        
        dump($list);
    }

    public function diff(): void
    {
    }

    public function run(): void
    {
    }

    private function getListToProcess()
    {
        $finder = new Finder();

        $files = $finder->files()->in($this->config['migrations'])->name('*.yaml');

        $list = [];

        foreach ($finder as $file) {
            $yaml = Yaml::parseFile($file->getRealPath());

            if (Comparator::greaterThan($yaml['since'], $this->checkpoint)) {
                $list[] = $file->getRealPath();
            }
        }

        return $list;

    }
}
