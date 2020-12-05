<?php

declare(strict_types=1);

namespace YamlMigrate\Tests;

use PHPUnit\Framework\TestCase;
use YamlMigrate\Migrate;

class listingTest extends TestCase
{
    public function testGetListingAmount(): void
    {
        $migrate = new Migrate('config.yaml');
        $migrate->setSilent(true);

        $res = $migrate->list();

        $this->assertSame(5, \count($res));
    }
}
