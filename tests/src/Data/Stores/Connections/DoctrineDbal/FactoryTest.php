<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Connections\DoctrineDbal;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\Logger;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\profilepage\ModuleConfiguration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 */
class FactoryTest extends TestCase
{
    protected ModuleConfiguration $moduleConfiguration;

    protected MockObject $loggerServiceMock;

    protected function setUp(): void
    {
        // Configuration directory is set by phpunit using php ENV setting feature (check phpunit.xml).
        $this->moduleConfiguration = new ModuleConfiguration('module_profilepage.php');
        $this->loggerServiceMock = $this->createMock(Logger::class);
    }

    public function testCanBuildConnection(): void
    {
        $factory = new Factory($this->moduleConfiguration, $this->loggerServiceMock);

        $this->assertInstanceOf(Connection::class, $factory->buildConnection('doctrine_dbal_pdo_sqlite'));
    }

    /**
     * @throws StoreException
     */
    public function testCanBuildMigrator(): void
    {
        $factory = new Factory($this->moduleConfiguration, $this->loggerServiceMock);

        $connection = new Connection(ConnectionParameters::DBAL_SQLITE_MEMORY);

        $this->assertInstanceOf(Migrator::class, $factory->buildMigrator($connection));
    }
}
