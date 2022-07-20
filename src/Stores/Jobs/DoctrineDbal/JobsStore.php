<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal;

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator;
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

    public function needsSetup(): bool
    {
        // ... if the migrator itself needs setup.
        if ($this->migrator->needsSetup()) {
            return true;
        }

        // ... if JobsStore migrations need to run
        if (! $this->areAllMigrationsImplemented()) {
            return true;
        }

        return false;
    }

    public function runSetup(): void
    {
        if ($this->migrator->needsSetup()) {
            $this->migrator->runSetup();
        }

        if (! $this->areAllMigrationsImplemented()) {
            $this->migrator->runNonImplementedMigrationClasses(
                $this->getMigrationsDirectory(),
                $this->getMigrationsNamespace()
            );
        }
    }

    public function getPrefixedTableNameJobs(): string
    {
        return $this->prefixedTableNameJobs;
    }

    public function getPrefixedTableNameFailedJobs(): string
    {
        return $this->prefixedTableNameFailedJobs;
    }

    public function areAllMigrationsImplemented(): bool
    {
        return ! $this->migrator->hasNonImplementedMigrationClasses(
            $this->getMigrationsDirectory(),
            $this->getMigrationsNamespace()
        );
    }

    protected function getMigrationsDirectory(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR .
            (new \ReflectionClass($this))->getShortName() . DIRECTORY_SEPARATOR .
            AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
    }

    protected function getMigrationsNamespace(): string
    {
        return self::class . '\\' . AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
    }
}
