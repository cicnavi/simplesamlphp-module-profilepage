<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Connections\DoctrineDbal\Bases;

use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Migrations\Version20220601000000CreateJobsTable;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore\Migrations\Version20220601000000CreateJobsTable
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 */
class AbstractMigrationTest extends TestCase
{
    public function testCanInstantiateMigrationClass(): void
    {
        $connection = new Connection(['driver' => 'pdo_sqlite', 'memory' => true,]);

        $this->assertInstanceOf(
            AbstractMigration::class,
            new Version20220601000000CreateJobsTable($connection)
        );
    }
}
