<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store;

use DateTimeImmutable;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use Throwable;

class Repository
{
    protected Connection $connection;
    protected LoggerInterface $logger;
    protected string $tableNameIdp;
    protected string $tableNameSp;
    protected string $tableNameUser;
    protected string $tableNameUserVersion;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;

        $this->tableNameIdp = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_IDP);
        $this->tableNameSp = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_SP);
        $this->tableNameUser = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_USER);
        $this->tableNameUserVersion = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_USER_VERSION);
    }

    protected function preparePrefixedTableName(string $tableName): string
    {
        return $this->connection->preparePrefixedTableName(TableConstants::TABLE_PREFIX . $tableName);
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

        $createdAt = $createdAt ?? new DateTimeImmutable();

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
                    TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT => $createdAt,
                    TableConstants::TABLE_IDP_COLUMN_NAME_UPDATED_AT => $createdAt,
                ],
                [
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID => Types::STRING,
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_IDP_COLUMN_NAME_METADATA => Types::STRING,
                    TableConstants::TABLE_IDP_COLUMN_NAME_METADATA_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE,
                    TableConstants::TABLE_IDP_COLUMN_NAME_UPDATED_AT => Types::DATETIMETZ_IMMUTABLE,
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
        string $idpId,
        string $metadata,
        string $metadataHashSha256,
        DateTimeImmutable $updatedAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $updatedAt = $updatedAt ?? new DateTimeImmutable();

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
                $updatedAt,
                Types::DATETIMETZ_IMMUTABLE
            )
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        TableConstants::TABLE_IDP_COLUMN_NAME_ID,
                        $queryBuilder->createNamedParameter($idpId, Types::INTEGER)
                    )
                )
            );

        try {
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

        $createdAt = $createdAt ?? new DateTimeImmutable();

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
                    TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT => $createdAt,
                    TableConstants::TABLE_SP_COLUMN_NAME_UPDATED_AT => $createdAt,
                ],
                [
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID => Types::STRING,
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_SP_COLUMN_NAME_METADATA => Types::STRING,
                    TableConstants::TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE,
                    TableConstants::TABLE_SP_COLUMN_NAME_UPDATED_AT => Types::DATETIMETZ_IMMUTABLE,
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
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $updatedAt = $updatedAt ?? new DateTimeImmutable();

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
                $updatedAt,
                Types::DATETIMETZ_IMMUTABLE
            )
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        TableConstants::TABLE_SP_COLUMN_NAME_ID,
                        $queryBuilder->createNamedParameter($spId, Types::INTEGER)
                    )
                )
            );

        try {
            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Error executing query to update SP. Error was: %s.', $exception->getMessage());
            $this->logger->error($message, compact('spId', 'metadata', 'metadataHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getUser(string $identifierHashSha256): Result
    {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $queryBuilder->select(
                TableConstants::TABLE_USER_COLUMN_NAME_ID,
                TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER,
                TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256,
                TableConstants::TABLE_USER_COLUMN_NAME_CREATED_AT,
            )
                ->from($this->tableNameUser)
                ->where(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256,
                            $queryBuilder->createNamedParameter($identifierHashSha256)
                        )
                    )
                )->setMaxResults(1);

            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to get user. Error was: %s.',
                $identifierHashSha256,
                $exception->getMessage()
            );
            $this->logger->error($message, compact('identifierHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function insertUser(
        string $identifier,
        string $identifierHashSha256,
        DateTimeImmutable $createdAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt = $createdAt ?? new DateTimeImmutable();

        $queryBuilder->insert($this->tableNameUser)
            ->values(
                [
                    TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER => ':' .
                        TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER,
                    TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 => ':' .
                        TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256,
                    TableConstants::TABLE_USER_COLUMN_NAME_CREATED_AT => ':' .
                        TableConstants::TABLE_USER_COLUMN_NAME_CREATED_AT,
                ]
            )
            ->setParameters(
                [
                    TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER => $identifier,
                    TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 => $identifierHashSha256,
                    TableConstants::TABLE_USER_COLUMN_NAME_CREATED_AT => $createdAt,
                ],
                [
                    TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER => Types::TEXT,
                    TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_USER_COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE
                ]
            );

        try {
            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Error executing query to insert user. Error was: %s.', $exception->getMessage());
            $this->logger->error(
                $message,
                compact('identifier', 'identifierHashSha256')
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }


    /**
     * @throws StoreException
     */
    public function getUserVersion(int $userId, string $attributesHashSha256): Result
    {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $queryBuilder->select(
                TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID,
                TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID,
                TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES,
                TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES_HASH_SHA256,
                TableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT,
            )
                ->from($this->tableNameUserVersion)
                ->where(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID,
                            $queryBuilder->createNamedParameter($userId, ParameterType::INTEGER)
                        ),
                        $queryBuilder->expr()->eq(
                            TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES_HASH_SHA256,
                            $queryBuilder->createNamedParameter($attributesHashSha256)
                        )
                    )
                )->setMaxResults(1);

            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to get user version for user ID %s and attribute array hash %s. Error was: %s.',
                $userId,
                $attributesHashSha256,
                $exception->getMessage()
            );
            $this->logger->error($message, compact('userId', 'attributesHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function insertUserVersion(
        int $userId,
        string $attributes,
        string $attributesHashSha256,
        DateTimeImmutable $createdAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt = $createdAt ?? new DateTimeImmutable();

        $queryBuilder->insert($this->tableNameUserVersion)
            ->values(
                [
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID => ':' .
                        TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES => ':' .
                        TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES_HASH_SHA256 => ':' .
                        TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES_HASH_SHA256,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT => ':' .
                        TableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT,
                ]
            )
            ->setParameters(
                [
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID => $userId,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES => $attributes,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES_HASH_SHA256 => $attributesHashSha256,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT => $createdAt,
                ],
                [
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID => Types::BIGINT,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES => Types::TEXT,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE,
                ]
            );

        try {
            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to insert user version. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message, compact('userId', 'attributesHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
