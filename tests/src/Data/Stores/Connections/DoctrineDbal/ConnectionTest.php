<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Connections\DoctrineDbal;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\profilepage\Exceptions\InvalidConfigurationException;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
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

    public function testInvalidConnectionParametersThrow(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        (new Connection(['invalid' => 'parameter']));
    }

    public function testCanSetTablePrefix(): void
    {
        $prefix = 'test_';
        $parameters = $this->parameters;
        $parameters['table_prefix'] = $prefix;

        $connection = new Connection($parameters);

        $this->assertEquals($prefix, $connection->getTablePrefix());

        $this->assertSame('test_test', $connection->preparePrefixedTableName('test'));
    }

    public function testTablePrefixParameterThrowsIfNotString(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $parameters = $this->parameters;
        $parameters['table_prefix'] = new class () {
        };

        (new Connection($parameters));
    }
}
