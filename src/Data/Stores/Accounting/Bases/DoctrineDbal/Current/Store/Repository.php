<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store;

use DateTimeImmutable;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
// phpcs:ignore
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository as VersionedRepository;
// phpcs:ignore
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants as VersionedTableConstantsAlias;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use Throwable;

class Repository
{
    // We user versioned user data, so let's reuse versioned user tables.
    use VersionedRepository\UserVersionManagementTrait;

    protected string $tableNameIdp;
    protected string $tableNameSp;
    protected string $tableNameUser;
    protected string $tableNameUserVersion;

    public function __construct(
        protected Connection $connection,
        protected LoggerInterface $logger
    ) {
        $this->tableNameIdp = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_IDP);
        $this->tableNameSp = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_SP);

        // For user management we use versioned data, so we will reuse tables from versioned data store.
        $versionedDataStoreTablePrefix = VersionedTableConstantsAlias::TABLE_PREFIX;
        $this->tableNameUser = $this->preparePrefixedTableName(
            VersionedTableConstantsAlias::TABLE_NAME_USER,
            $versionedDataStoreTablePrefix
        );
        $this->tableNameUserVersion = $this->preparePrefixedTableName(
            VersionedTableConstantsAlias::TABLE_NAME_USER_VERSION,
            $versionedDataStoreTablePrefix
        );
    }

    protected function preparePrefixedTableName(string $tableName, string $tablePrefixOverride = null): string
    {
        $tablePrefix = $tablePrefixOverride ?? TableConstants::TABLE_PREFIX;
        return $this->connection->preparePrefixedTableName($tablePrefix . $tableName);
    }

    /**
     * @throws StoreException
     */
    public function getIdp(string $entityIdHashSha256): Result
    {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $queryBuilder->select(
                TableConstants::TABLE_IDP_COLUMN_NAME_ID,
                TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID,
                TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256,
                TableConstants::TABLE_IDP_COLUMN_NAME_METADATA,
                TableConstants::TABLE_IDP_COLUMN_NAME_METADATA_HASH_SHA256,
                TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT,
                TableConstants::TABLE_IDP_COLUMN_NAME_UPDATED_AT,
            )
                ->from($this->tableNameIdp)
                ->where(
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 . ' = ' .
                    $queryBuilder->createNamedParameter($entityIdHashSha256)
                )->setMaxResults(1);

            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to get IdP by entity ID hash SHA256 \'%s\'. Error was: %s.',
                $entityIdHashSha256,
                $exception->getMessage()
            );
            $this->logger->error($message, compact('entityIdHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function insertIdp(
        string $entityId,
        string $entityIdHashSha256,
        string $metadata,
        string $metadataHashSha256,
        DateTimeImmutable $createdAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt ??= new DateTimeImmutable();

        $queryBuilder->insert($this->tableNameIdp)
            ->values(
                [
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID => ':' .
                        TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID,
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => ':' .
                        TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256,
                    TableConstants::TABLE_IDP_COLUMN_NAME_METADATA => ':' .
                        TableConstants::TABLE_IDP_COLUMN_NAME_METADATA,
                    TableConstants::TABLE_IDP_COLUMN_NAME_METADATA_HASH_SHA256 => ':' .
                        TableConstants::TABLE_IDP_COLUMN_NAME_METADATA_HASH_SHA256,
                    TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT => ':' .
                        TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT,
                    TableConstants::TABLE_IDP_COLUMN_NAME_UPDATED_AT => ':' .
                        TableConstants::TABLE_IDP_COLUMN_NAME_UPDATED_AT,
                ]
            )
            ->setParameters(
                [
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID => $entityId,
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => $entityIdHashSha256,
                    TableConstants::TABLE_IDP_COLUMN_NAME_METADATA => $metadata,
                    TableConstants::TABLE_IDP_COLUMN_NAME_METADATA_HASH_SHA256 => $metadataHashSha256,
                    TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT => $createdAt->getTimestamp(),
                    TableConstants::TABLE_IDP_COLUMN_NAME_UPDATED_AT => $createdAt->getTimestamp(),
                ],
                [
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID => Types::STRING,
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_IDP_COLUMN_NAME_METADATA => Types::STRING,
                    TableConstants::TABLE_IDP_COLUMN_NAME_METADATA_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT => Types::BIGINT,
                    TableConstants::TABLE_IDP_COLUMN_NAME_UPDATED_AT => Types::BIGINT,
                ]
            );

        try {
            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Error executing query to insert IdP. Error was: %s.', $exception->getMessage());
            $this->logger->error($message, compact('entityId', 'entityIdHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getSp(string $entityIdHashSha256): Result
    {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $queryBuilder->select(
                TableConstants::TABLE_SP_COLUMN_NAME_ID,
                TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID,
                TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256,
                TableConstants::TABLE_SP_COLUMN_NAME_METADATA,
                TableConstants::TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256,
                TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT,
                TableConstants::TABLE_SP_COLUMN_NAME_UPDATED_AT,
            )
                ->from($this->tableNameSp)
                ->where(
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 . ' = ' .
                    $queryBuilder->createNamedParameter($entityIdHashSha256)
                )->setMaxResults(1);

            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to get SP by entity ID hash SHA256 \'%s\'. Error was: %s.',
                $entityIdHashSha256,
                $exception->getMessage()
            );
            $this->logger->error($message, compact('entityIdHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function updateIdp(
        int $idpId,
        string $metadata,
        string $metadataHashSha256,
        DateTimeImmutable $updatedAt = null
    ): void {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            $updatedAt ??= new DateTimeImmutable();

            $queryBuilder->update($this->tableNameIdp)
                ->set(
                    TableConstants::TABLE_IDP_COLUMN_NAME_METADATA,
                    ':' . TableConstants::TABLE_IDP_COLUMN_NAME_METADATA
                )
                ->set(
                    TableConstants::TABLE_IDP_COLUMN_NAME_METADATA_HASH_SHA256,
                    ':' . TableConstants::TABLE_IDP_COLUMN_NAME_METADATA_HASH_SHA256
                )
                ->set(
                    TableConstants::TABLE_IDP_COLUMN_NAME_UPDATED_AT,
                    ':' . TableConstants::TABLE_IDP_COLUMN_NAME_UPDATED_AT
                )
                ->setParameter(
                    TableConstants::TABLE_IDP_COLUMN_NAME_METADATA,
                    $metadata,
                    Types::STRING
                )
                ->setParameter(
                    TableConstants::TABLE_IDP_COLUMN_NAME_METADATA_HASH_SHA256,
                    $metadataHashSha256,
                    Types::STRING
                )
                ->setParameter(
                    TableConstants::TABLE_IDP_COLUMN_NAME_UPDATED_AT,
                    $updatedAt->getTimestamp(),
                    Types::BIGINT
                )
                ->where(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            TableConstants::TABLE_IDP_COLUMN_NAME_ID,
                            $queryBuilder->createNamedParameter($idpId, Types::INTEGER)
                        )
                    )
                );

            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Error executing query to update IdP. Error was: %s.', $exception->getMessage());
            $this->logger->error($message, compact('idpId', 'metadata', 'metadataHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function insertSp(
        string $entityId,
        string $entityIdHashSha256,
        string $metadata,
        string $metadataHashSha256,
        DateTimeImmutable $createdAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt ??= new DateTimeImmutable();

        $queryBuilder->insert($this->tableNameSp)
            ->values(
                [
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID => ':' .
                        TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID,
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => ':' .
                        TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256,
                    TableConstants::TABLE_SP_COLUMN_NAME_METADATA => ':' .
                        TableConstants::TABLE_SP_COLUMN_NAME_METADATA,
                    TableConstants::TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256 => ':' .
                        TableConstants::TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256,
                    TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT => ':' .
                        TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT,
                    TableConstants::TABLE_SP_COLUMN_NAME_UPDATED_AT => ':' .
                        TableConstants::TABLE_SP_COLUMN_NAME_UPDATED_AT,
                ]
            )
            ->setParameters(
                [
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID => $entityId,
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => $entityIdHashSha256,
                    TableConstants::TABLE_SP_COLUMN_NAME_METADATA => $metadata,
                    TableConstants::TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256 => $metadataHashSha256,
                    TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT => $createdAt->getTimestamp(),
                    TableConstants::TABLE_SP_COLUMN_NAME_UPDATED_AT => $createdAt->getTimestamp(),
                ],
                [
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID => Types::STRING,
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_SP_COLUMN_NAME_METADATA => Types::STRING,
                    TableConstants::TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT => Types::BIGINT,
                    TableConstants::TABLE_SP_COLUMN_NAME_UPDATED_AT => Types::BIGINT,
                ]
            );

        try {
            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Error executing query to insert SP. Error was: %s.', $exception->getMessage());
            $this->logger->error($message, compact('entityId', 'entityIdHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function updateSp(
        int $spId,
        string $metadata,
        string $metadataHashSha256,
        DateTimeImmutable $updatedAt = null
    ): void {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            $updatedAt ??= new DateTimeImmutable();

            $queryBuilder->update($this->tableNameSp)
                ->set(
                    TableConstants::TABLE_SP_COLUMN_NAME_METADATA,
                    ':' . TableConstants::TABLE_SP_COLUMN_NAME_METADATA
                )
                ->set(
                    TableConstants::TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256,
                    ':' . TableConstants::TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256
                )
                ->set(
                    TableConstants::TABLE_SP_COLUMN_NAME_UPDATED_AT,
                    ':' . TableConstants::TABLE_SP_COLUMN_NAME_UPDATED_AT
                )
                ->setParameter(
                    TableConstants::TABLE_SP_COLUMN_NAME_METADATA,
                    $metadata,
                    Types::STRING
                )
                ->setParameter(
                    TableConstants::TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256,
                    $metadataHashSha256,
                    Types::STRING
                )
                ->setParameter(
                    TableConstants::TABLE_SP_COLUMN_NAME_UPDATED_AT,
                    $updatedAt->getTimestamp(),
                    Types::BIGINT
                )
                ->where(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            TableConstants::TABLE_SP_COLUMN_NAME_ID,
                            $queryBuilder->createNamedParameter($spId, Types::INTEGER)
                        )
                    )
                );

            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Error executing query to update SP. Error was: %s.', $exception->getMessage());
            $this->logger->error($message, compact('spId', 'metadata', 'metadataHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
