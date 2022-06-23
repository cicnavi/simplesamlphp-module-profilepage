<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal;

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Jobs\Interfaces\JobsStoreInterface;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;

class JobsStore implements JobsStoreInterface
{
    public const DEFAULT_TABLE_NAME = 'jobs';

    protected ModuleConfiguration $moduleConfiguration;
    protected Connection $connection;
    protected string $tableName;
    protected Migrator $migrator;

    public function __construct(ModuleConfiguration $moduleConfiguration, Factory $factory)
    {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->connection = $factory->buildConnection($moduleConfiguration->getStoreConnection(self::class));
        $this->migrator = $factory->buildMigrator($this->connection);

        $this->setTableName(self::DEFAULT_TABLE_NAME);
    }

    public function needsSetUp(): bool
    {
        // TODO mivanci dovrši
        return true;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $this->connection->getTablePrefix() . $tableName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }
}
