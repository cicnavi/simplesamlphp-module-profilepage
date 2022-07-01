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
    public const TABLE_NAME_JOBS = 'jobs';
    public const TABLE_NAME_FAILED_JOBS = 'failed_jobs';

    protected ModuleConfiguration $moduleConfiguration;
    protected Connection $connection;
    protected string $prefixedTableNameJobs;
    protected string $prefixedTableNameFailedJobs;
    protected Migrator $migrator;

    public function __construct(ModuleConfiguration $moduleConfiguration, Factory $factory)
    {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->connection = $factory->buildConnection($moduleConfiguration->getStoreConnection(self::class));
        $this->migrator = $factory->buildMigrator($this->connection);

        $this->prefixedTableNameJobs = $this->connection->preparePrefixedTableName(self::TABLE_NAME_JOBS);
        $this->prefixedTableNameFailedJobs = $this->connection->preparePrefixedTableName(self::TABLE_NAME_FAILED_JOBS);
    }

    public function needsSetUp(): bool
    {
        // TODO mivanci dovrši
        return true;
    }

    public function getPrefixedTableNameJobs(): string
    {
        return $this->prefixedTableNameJobs;
    }

    public function getPrefixedTableNameFailedJobs(): string
    {
        return $this->prefixedTableNameFailedJobs;
    }
}
