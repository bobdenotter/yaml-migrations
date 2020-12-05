<?php

declare(strict_types=1);

namespace YamlMigrate\Tests;

use PHPUnit\Framework\TestCase;
use YamlMigrate\Migrate;

class fileNotInConfigTest extends TestCase
{
    public function testFileNotInConfig(): void
    {
        $migrate = new Migrate('config.yaml');
        $migrate->setSilent(true);

        $this->expectException(\Throwable::class);

        $migrate->process('foo.yaml');
    }
}
