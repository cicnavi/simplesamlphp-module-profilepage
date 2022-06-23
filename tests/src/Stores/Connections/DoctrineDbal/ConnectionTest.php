<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Connections\DoctrineDbal;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 */
class ConnectionTest extends TestCase
{
    protected array $parameters = [
        'driver' => 'pdo_sqlite',
        'memory' => true,
    ];

    public function testCanInstantiateDbalConnection(): void
    {
        $connection = new Connection($this->parameters);

        $this->assertInstanceOf(\Doctrine\DBAL\Connection::class, $connection->dbal());
    }

    public function testCanSetTablePrefix(): void
    {
        $prefix = 'test_';
        $parameters = $this->parameters;
        $parameters['table_prefix'] = $prefix;

        $connection = new Connection($parameters);

        $this->assertEquals($prefix, $connection->getTablePrefix());
    }
}
