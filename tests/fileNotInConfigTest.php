<?php


namespace YamlMigrate\Tests;


use PHPUnit\Framework\TestCase;
use YamlMigrate\Migrate;

class fileNotInConfigTest extends TestCase
{
    public function testFileNotInConfig()
    {
        $migrate = new Migrate('config.yaml');
        $migrate->setSilent(true);

        $this->expectException(\Exception::class);

        $migrate->process('foo.yaml');
    }
}