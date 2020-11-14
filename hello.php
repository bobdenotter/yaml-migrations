<?php

require_once 'vendor/autoload.php';

$hello_cmd = new Commando\Command();

// Define first option
$hello_cmd->option()
    ->require()
    ->describedAs('A person\'s name');

// Define a flag "-t" a.k.a. "--title"
$hello_cmd->option('t')
    ->aka('title')
    ->describedAs('When set, use this title to address the person')
    ->must(function($title) {
        $titles = array('Mister', 'Mr', 'Misses', 'Mrs', 'Miss', 'Ms');
        return in_array($title, $titles);
    })
    ->map(function($title) {
        $titles = array('Mister' => 'Mr', 'Misses' => 'Mrs', 'Miss' => 'Ms');
        if (array_key_exists($title, $titles))
            $title = $titles[$title];
        return "$title. ";
    });

// Define a boolean flag "-c" aka "--capitalize"
$hello_cmd->option('c')
    ->aka('capitalize')
    ->aka('cap')
    ->describedAs('Always capitalize the words in a name')
    ->boolean();

// Define an incremental flag "-e" aka "--educate"
$hello_cmd->option('e')
    ->aka('educate')
    ->map(function($value) {
        $postfix = array('', 'Jr', 'esq', 'PhD');
        return $postfix[$value] === '' ? '' : " {$postfix[$value]}";
    })
    ->count(4);

$name = $hello_cmd['capitalize'] ? ucwords($hello_cmd[0]) : $hello_cmd[0];

echo "Hello {$hello_cmd['title']}$name{$hello_cmd['educate']}!", PHP_EOL;

