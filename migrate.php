<?php

declare(strict_types=1);

use Commando\Command;
use YamlMigrate\Migrate;

require_once 'vendor/autoload.php';

$command = new Commando\Command();

// Define the command
$command->option()
    ->require()
    ->describedAs('The command to run');

// Define a flag "-c" a.k.a. "--config"
$command->option('c')
    ->aka('config')
    ->require()
    ->describedAs('When set, use this configuration file for migrations');

$migrate = new Migrate($command['config']);

$migrate->process();

echo '', PHP_EOL;;
