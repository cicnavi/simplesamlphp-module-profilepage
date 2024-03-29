<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;

abstract class AbstractStore extends \SimpleSAML\Module\profilepage\Data\Stores\Bases\AbstractStore
{
    protected Connection $connection;
    protected Migrator $migrator;
    protected Factory $connectionFactory;

    /**
     * @throws StoreException
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null,
        string $connectionType = ModuleConfiguration\ConnectionType::MASTER,
        Factory $connectionFactory = null,
        SerializerInterface $serializer = null,
    ) {
        parent::__construct($moduleConfiguration, $logger, $connectionKey, $connectionType, $serializer);

        $this->connectionFactory = $connectionFactory ?? new Factory($this->moduleConfiguration, $this->logger);

        $this->connection = $this->connectionFactory->buildConnection($this->connectionKey);
        $this->migrator = $this->connectionFactory->buildMigrator($this->connection);
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

    /**
     * Build store instance. Must be implemented in child classes for proper return store type.
     * @param ModuleConfiguration $moduleConfiguration
     * @param LoggerInterface $logger
     * @param string|null $connectionKey
     * @return self
     */
    abstract public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $connectionKey = null
    ): self;
}
