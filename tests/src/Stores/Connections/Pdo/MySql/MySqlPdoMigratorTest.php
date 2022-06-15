<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Connections\Pdo\MySql;

use PDO;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Connections\Pdo\MySql\MySqlPdoMigrator;
use SimpleSAML\Module\accounting\Stores\Connections\Pdo\PdoConnection;
use SimpleSAML\Module\accounting\Stores\Jobs\Pdo\MySql\MySqlPdoJobsStore;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Connections\Pdo\MySql\MySqlPdoMigrator
 */
class MySqlPdoMigratorTest extends TestCase
{
    public function testInstance(): void
    {
        $pdoStatementStub = $this->createStub(\PDOStatement::class);
        $pdoStatementStub->method('fetchAll')->willReturn([]);

        $pdoStub = $this->createStub(PDO::class);
        $pdoStub->method('prepare')->willReturn($pdoStatementStub);

        $pdoConnectionStub = $this->createStub(PdoConnection::class);
        $pdoConnectionStub->method('getPdo')->willReturn($pdoStub);

        $migrator = new MySqlPdoMigrator($pdoConnectionStub);

        $this->assertIsArray($migrator->getImplementedMigrations(MySqlPdoJobsStore::class));
    }
}
