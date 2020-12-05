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
    ->describedAs('Use this configuration file for migrations');

$command->option('v')
    ->aka('verbose')
    ->describedAs('When set, produce more verbose output')
    ->boolean();

$command->option('s')
    ->aka('silent')
    ->describedAs('When set, silence output')
    ->boolean();

$migrate = new Migrate($command['config']);

$migrate->setVerbose($command['v']);
$migrate->setSilent($command['s']);

/** @var \Commando\Option $argument */
$argument = $command->getArguments()[0];

if ($argument->getValue() == 'list') {
    $migrate->list();
} else if ($argument->getValue() == 'process') {
    $migrate->process();
}

echo '', PHP_EOL;;
