<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal;

use DateTimeImmutable;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration;
use SimpleSAML\Module\profilepage\Data\Stores\Interfaces\MigrationInterface;
use SimpleSAML\Module\profilepage\Exceptions\InvalidValueException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use Throwable;

class Migrator extends AbstractMigrator
{
    final public const TABLE_NAME = 'migrations';

    final public const COLUMN_NAME_ID = 'id';
    final public const COLUMN_NAME_VERSION = 'version';
    final public const COLUMN_NAME_CREATED_AT = 'created_at';

    protected AbstractSchemaManager $schemaManager;
    protected string $prefixedTableName;

    /**
     * @throws StoreException
     */
    public function __construct(
        protected Connection $connection,
        protected LoggerInterface $logger,
        HelpersManager $helpersManager = null
    ) {
        parent::__construct($helpersManager);

        try {
            $this->schemaManager = ($this->connection->dbal())->createSchemaManager();
        } catch (Throwable $exception) {
            $message = sprintf('Could not create DBAL schema manager. Error was: %s', $exception->getMessage());
            throw new StoreException($message, (int) $exception->getCode(), $exception);
        }
        $this->prefixedTableName = $this->connection->preparePrefixedTableName(self::TABLE_NAME);
    }

    /**
     * @throws StoreException
     */
    public function needsSetup(): bool
    {
        try {
            return ! $this->schemaManager->tablesExist([$this->prefixedTableName]);
        } catch (Throwable $exception) {
            $message = sprintf(
                'Could not check table \'%s\' existence using schema manager. Error was:%s',
                $this->prefixedTableName,
                $exception->getMessage()
            );
            throw new StoreException($message, (int) $exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function runSetup(): void
    {
        if (! $this->needsSetup()) {
            $this->logger->warning('Migrator setup has been called, however setup is not needed.');
            return;
        }

        $this->createMigrationsTable();
    }

    /**
     * @throws StoreException
     */
    protected function createMigrationsTable(): void
    {
        try {
            $table = new Table($this->prefixedTableName);

            $table->addColumn(self::COLUMN_NAME_ID, Types::BIGINT)
                ->setAutoincrement(true)
                ->setUnsigned(true);
            $table->addColumn(self::COLUMN_NAME_VERSION, Types::STRING);
            $table->addColumn(self::COLUMN_NAME_CREATED_AT, Types::BIGINT);

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex([self::COLUMN_NAME_VERSION]);

            $this->schemaManager->createTable($table);
        } catch (Throwable $exception) {
            $message = sprintf('Error creating migrations table %s.', $this->prefixedTableName);
            throw new StoreException($message, (int) $exception->getCode(), $exception);
        }
    }

    /**
     * @throws ReflectionException
     */
    protected function buildMigrationClassInstance(string $migrationClass): MigrationInterface
    {
        $this->validateDoctrineDbalMigrationClass($migrationClass);

        /** @var MigrationInterface $migration */
        $migration = (new ReflectionClass($migrationClass))->newInstance($this->connection);

        return $migration;
    }

    protected function validateDoctrineDbalMigrationClass(string $migrationClass): void
    {
        if (! is_subclass_of($migrationClass, AbstractMigration::class)) {
            throw new InvalidValueException('Migration class is not Doctrine DBAL migration.');
        }
    }

    /**
     * @throws StoreException
     */
    protected function markImplementedMigrationClass(string $migrationClass): void
    {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        try {
            $queryBuilder->insert($this->prefixedTableName)
                ->values(
                    [
                        self::COLUMN_NAME_VERSION => ':' . self::COLUMN_NAME_VERSION,
                        self::COLUMN_NAME_CREATED_AT => ':' . self::COLUMN_NAME_CREATED_AT,
                    ]
                )
                ->setParameters(
                    [
                        self::COLUMN_NAME_VERSION => $migrationClass,
                        self::COLUMN_NAME_CREATED_AT => (new DateTimeImmutable())->getTimestamp(),
                    ],
                    [
                        self::COLUMN_NAME_VERSION => Types::STRING,
                        self::COLUMN_NAME_CREATED_AT => Types::BIGINT
                    ]
                );

            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Error marking implemented migrations class %s.', $migrationClass);
            throw new StoreException($message, (int) $exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getImplementedMigrationClasses(): array
    {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            $queryBuilder->select(self::COLUMN_NAME_VERSION)
                ->from($this->prefixedTableName);

            /** @var class-string[] $migrationClasses */
            $migrationClasses = $queryBuilder->executeQuery()->fetchFirstColumn();
        } catch (Throwable $exception) {
            $message = 'Error getting implemented migration classes.';
            throw new StoreException($message, (int) $exception->getCode(), $exception);
        }

        return $migrationClasses;
    }
}
