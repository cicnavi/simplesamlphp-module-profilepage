<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory
 */
class StoreTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $moduleConfigurationStub;
    protected \PHPUnit\Framework\MockObject\Stub $loggerStub;
    protected Migrator $migrator;
    protected \PHPUnit\Framework\MockObject\Stub $factoryStub;
    protected Connection $connection;

    protected function setUp(): void
    {
        $connectionParams = ['driver' => 'pdo_sqlite', 'memory' => true,];
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn($connectionParams);

        $this->connection = new Connection($connectionParams);

        $this->loggerStub = $this->createStub(LoggerInterface::class);

        /** @psalm-suppress InvalidArgument */
        $this->migrator = new Migrator($this->connection, $this->loggerStub);

        $this->factoryStub = $this->createStub(Factory::class);
        $this->factoryStub->method('buildConnection')->willReturn($this->connection);
        $this->factoryStub->method('buildMigrator')->willReturn($this->migrator);
    }

    public function testCanConstructInstance(): void
    {
        /** @psalm-suppress InvalidArgument */
        $this->assertInstanceOf(
            Store::class,
            new Store($this->moduleConfigurationStub, $this->loggerStub, $this->factoryStub)
        );
    }

    public function testCanBuildInstance(): void
    {
        /** @psalm-suppress InvalidArgument */
        $this->assertInstanceOf(
            Store::class,
            Store::build($this->moduleConfigurationStub, $this->loggerStub)
        );
    }
}
