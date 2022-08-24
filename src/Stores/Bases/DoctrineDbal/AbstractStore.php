<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal;

use Psr\Log\LoggerInterface;
use ReflectionClass;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Interfaces\BuildableUsingModuleConfigurationInterface;
use SimpleSAML\Module\accounting\Interfaces\SetupableInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;

abstract class AbstractStore implements BuildableUsingModuleConfigurationInterface, SetupableInterface
{
    protected ModuleConfiguration $moduleConfiguration;
    protected Connection $connection;
    protected Migrator $migrator;
    protected LoggerInterface $logger;

    /**
     * @throws StoreException
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        Factory $factory,
        LoggerInterface $logger
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->connection = $factory->buildConnection(
            $moduleConfiguration->getClassConnectionParameters($this->getSelfClass())
        );
        $this->migrator = $factory->buildMigrator($this->connection);
        $this->logger = $logger;
    }

    /**
     * Get ReflectionClass of current store instance.
     * @return ReflectionClass
     */
    protected function getReflection(): ReflectionClass
    {
        return new ReflectionClass($this);
    }

    /**
     * Get class of the current store instance.
     * @return string
     */
    protected function getSelfClass(): string
    {
        return $this->getReflection()->getName();
    }

    protected function getMigrationsNamespace(): string
    {
        return $this->getSelfClass() . '\\' . AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
    }

    protected function areAllMigrationsImplemented(): bool
    {
        return !$this->migrator->hasNonImplementedMigrationClasses(
            $this->getMigrationsDirectory(),
            $this->getMigrationsNamespace()
        );
    }

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    public function runSetup(): void
    {
        if ($this->migrator->needsSetup()) {
            $this->migrator->runSetup();
        }

        if (!$this->areAllMigrationsImplemented()) {
            $this->migrator->runNonImplementedMigrationClasses(
                $this->getMigrationsDirectory(),
                $this->getMigrationsNamespace()
            );
        }
    }

    /**
     * @throws StoreException
     */
    public function needsSetup(): bool
    {
        // ... if the migrator itself needs setup.
        if ($this->migrator->needsSetup()) {
            return true;
        }

        // ... if Store migrations need to run
        if (!$this->areAllMigrationsImplemented()) {
            return true;
        }

        return false;
    }

    /**
     * Get migrations directory.
     * By default, it will return {...}/Store/Migrations directory.
     * @return string
     */
    protected function getMigrationsDirectory(): string
    {
        $reflection = $this->getReflection();
        $storeDirName = dirname($reflection->getFileName());
        $storeShortName = $reflection->getShortName();

        return $storeDirName . DIRECTORY_SEPARATOR .
            $storeShortName . DIRECTORY_SEPARATOR .
            AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
    }

    // From interfaces
    abstract public static function build(ModuleConfiguration $moduleConfiguration): self;
}
