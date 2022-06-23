<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Jobs\Pdo\MySql;

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\Pdo\Factories\PdoConnectionFactory;
use SimpleSAML\Module\accounting\Stores\Connections\Pdo\MySql\MySqlPdoMigrator;
use SimpleSAML\Module\accounting\Stores\Connections\Pdo\PdoConnection;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;

class MySqlPdoJobsStore implements JobsStoreInterface
{
    public const DEFAULT_TABLE_NAME = 'jobs';

    protected PdoConnection $pdoConnection;
    protected string $tableName;

    public function __construct(ModuleConfiguration $moduleConfiguration, PdoConnectionFactory $pdoConnectionFactory)
    {
        $this->pdoConnection = $pdoConnectionFactory
            ->build($moduleConfiguration->getStoreConnection(self::class));

        $this->setTableName(self::DEFAULT_TABLE_NAME);
    }

    public function needsSetUp(): bool
    {
//        $migrator = new MySqlPdoMigrator($this->pdoConnection);

//        $implementedMigrations = $migrator->getImplementedMigrations(self::class);
//        var_dump($implementedMigrations);

        // TODO mivanci prebaci sve na doctrine/dbal
        //https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/introduction.html#introduction

        return false;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $this->pdoConnection->getTablePrefix() . $tableName;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }
}
