<?php


namespace YamlMigrate\Tests;


use PHPUnit\Framework\TestCase;
use YamlMigrate\Migrate;

class listingTest extends TestCase
{
    public function testGetListingAmount()
    {
        $migrate = new Migrate('config.yaml');
        $migrate->setSilent(true);

        $res = $migrate->list();

        $this->assertEquals('5', count($res));
    }
}