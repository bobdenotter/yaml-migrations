<?php

use YamlMigrate\Migrate;
use Commando\Command;

require_once "vendor/autoload.php";


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

$migrate = new YamlMigrate\Migrate($command['config']);


echo "done";
