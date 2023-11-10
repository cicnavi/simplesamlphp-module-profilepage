<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000000CreateJobTable;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\Migrations\Version20220601000000CreateJobTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 */
class AbstractMigrationTest extends TestCase
{
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = new Connection(ConnectionParameters::DBAL_SQLITE_MEMORY);
    }

    /**
     * @throws StoreException
     */
    public function testCanInstantiateMigrationClass(): void
    {
        $this->assertInstanceOf(
            AbstractMigration::class,
            new Version20220601000000CreateJobTable($this->connection)
        );
    }

    public function testThrowsStoreException(): void
    {
        $dbalStub = $this->createStub(\Doctrine\DBAL\Connection::class);
        $dbalStub->method('createSchemaManager')
            ->willThrowException(new \Doctrine\DBAL\Exception('test'));
        $connectionStub = $this->createStub(Connection::class);
        $connectionStub->method('dbal')->willReturn($dbalStub);

        $this->expectException(StoreException::class);

        (new Version20220601000000CreateJobTable($connectionStub));
    }

    public function testCanThrowGenericMigrationExceptionOnRun(): void
    {
        $migration = new class ($this->connection) extends AbstractMigration {
            public function run(): void
            {
                throw $this->prepareGenericMigrationException('test', new Exception('test'));
            }

            public function revert(): void
            {
            }
        };

        $this->expectException(StoreException\MigrationException::class);

        $migration->run();
    }

    public function testCanUseTableNamePrefix(): void
    {
        $connectionStub = $this->createStub(Connection::class);
        $connectionStub->method('dbal')->willReturn($this->connection->dbal());
        $connectionStub->method('preparePrefixedTableName')->willReturn('prefix-connection');

        $migration = new class ($connectionStub) extends AbstractMigration {
            public function run(): void
            {
                throw new Exception($this->preparePrefixedTableName('table-name'));
            }
            public function revert(): void
            {
            }
            protected function getLocalTablePrefix(): string
            {
                return 'prefix-local';
            }
        };

        try {
            $migration->run();
        } catch (Exception $exception) {
            $this->assertStringContainsString('prefix-connection', $exception->getMessage());
        }
    }

    public function testCanUseLocalTableNamePrefix(): void
    {
        $connectionStub = $this->createStub(Connection::class);
        $connectionStub->method('dbal')->willReturn($this->connection->dbal());
        $connectionStub->method('preparePrefixedTableName')->willReturn('prefix-connection');

        $migration = new class ($connectionStub) extends AbstractMigration {
            public function run(): void
            {
                throw new Exception($this->getLocalTablePrefix());
            }
            public function revert(): void
            {
            }
        };

        try {
            $migration->run();
        } catch (Exception $exception) {
            $this->assertEmpty($exception->getMessage());
        }
    }
}
