<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Stores\Connections\DoctrineDbal;

use PHPUnit\Framework\MockObject\MockObject;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\Logger;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 */
class FactoryTest extends TestCase
{
    protected ModuleConfiguration $moduleConfiguration;

    protected MockObject $loggerServiceMock;

    protected function setUp(): void
    {
        // Configuration directory is set by phpunit using php ENV setting feature (check phpunit.xml).
        $this->moduleConfiguration = new ModuleConfiguration('module_accounting.php');
        $this->loggerServiceMock = $this->createMock(Logger::class);
    }

    public function testCanBuildConnection(): void
    {
        /** @psalm-suppress InvalidArgument */
        $factory = new Factory($this->moduleConfiguration, $this->loggerServiceMock);

        $this->assertInstanceOf(Connection::class, $factory->buildConnection('doctrine_dbal_pdo_sqlite'));
    }

    /**
     * @throws StoreException
     */
    public function testCanBuildMigrator(): void
    {
        /** @psalm-suppress InvalidArgument */
        $factory = new Factory($this->moduleConfiguration, $this->loggerServiceMock);

        $connection = new Connection(ConnectionParameters::DBAL_SQLITE_MEMORY);

        $this->assertInstanceOf(Migrator::class, $factory->buildMigrator($connection));
    }
}
