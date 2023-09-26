<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store;

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
    use Repository\UserVersionManagementTrait;

    protected Connection $connection;
    protected LoggerInterface $logger;
    protected string $tableNameIdp;
    protected string $tableNameIdpVersion;
    protected string $tableNameSp;
    protected string $tableNameSpVersion;
    protected string $tableNameUser;
    protected string $tableNameUserVersion;
    protected string $tableNameIdpSpUserVersion;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;

        $this->tableNameIdp = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_IDP);
        $this->tableNameIdpVersion = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_IDP_VERSION);
        $this->tableNameSp = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_SP);
        $this->tableNameSpVersion = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_SP_VERSION);
        $this->tableNameUser = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_USER);
        $this->tableNameUserVersion = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_USER_VERSION);
        $this->tableNameIdpSpUserVersion = $this->preparePrefixedTableName(
            TableConstants::TABLE_NAME_IDP_SP_USER_VERSION
        );
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
                TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT,
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
                    TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT => ':' .
                        TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT,
                ]
            )
            ->setParameters(
                [
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID => $entityId,
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => $entityIdHashSha256,
                    TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT => $createdAt,
                ],
                [
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID => Types::STRING,
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE
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
    public function getIdpVersion(int $idpId, string $metadataHashSha256): Result
    {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $queryBuilder->select(
                TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_ID,
                TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID,
                TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA,
                TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256,
                TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT,
            )
                ->from($this->tableNameIdpVersion)
                ->where(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID,
                            $queryBuilder->createNamedParameter($idpId, ParameterType::INTEGER)
                        ),
                        $queryBuilder->expr()->eq(
                            TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256,
                            $queryBuilder->createNamedParameter($metadataHashSha256)
                        )
                    )
                )->setMaxResults(1);

            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to get IdP Version for IdP %s and metadata array hash %s. Error was: %s.',
                $idpId,
                $metadataHashSha256,
                $exception->getMessage()
            );
            $this->logger->error($message, compact('idpId', 'metadataHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function insertIdpVersion(
        int $idpId,
        string $metadata,
        string $metadataHashSha256,
        DateTimeImmutable $createdAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt ??= new DateTimeImmutable();

        $queryBuilder->insert($this->tableNameIdpVersion)
            ->values(
                [
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID => ':' .
                        TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA => ':' .
                        TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256 => ':' .
                        TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT => ':' .
                        TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT,
                ]
            )
            ->setParameters(
                [
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID => $idpId,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA => $metadata,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256 => $metadataHashSha256,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT => $createdAt,
                ],
                [
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID => Types::BIGINT,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA => Types::TEXT,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE,
                ]
            );

        try {
            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Error executing query to insert IdP Version. Error was: %s.', $exception->getMessage());
            $this->logger->error($message, compact('idpId', 'metadataHashSha256'));
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
                TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT,
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
    public function insertSp(
        string $entityId,
        string $entityIdHashSha256,
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
                    TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT => ':' .
                        TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT,
                ]
            )
            ->setParameters(
                [
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID => $entityId,
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => $entityIdHashSha256,
                    TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT => $createdAt,
                ],
                [
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID => Types::STRING,
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE
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
    public function getSpVersion(int $spId, string $metadataHashSha256): Result
    {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $queryBuilder->select(
                TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID,
                TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID,
                TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA,
                TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256,
                TableConstants::TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT,
            )
                ->from($this->tableNameSpVersion)
                ->where(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID,
                            $queryBuilder->createNamedParameter($spId, ParameterType::INTEGER)
                        ),
                        $queryBuilder->expr()->eq(
                            TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256,
                            $queryBuilder->createNamedParameter($metadataHashSha256)
                        )
                    )
                )->setMaxResults(1);

            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to get SP Version for SP %s and metadata array hash %s. Error was: %s.',
                $spId,
                $metadataHashSha256,
                $exception->getMessage()
            );
            $this->logger->error($message, compact('spId', 'metadataHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function insertSpVersion(
        int $spId,
        string $metadata,
        string $metadataHashSha256,
        DateTimeImmutable $createdAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt ??= new DateTimeImmutable();

        $queryBuilder->insert($this->tableNameSpVersion)
            ->values(
                [
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID => ':' .
                        TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA => ':' .
                        TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256 => ':' .
                        TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT => ':' .
                        TableConstants::TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT,
                ]
            )
            ->setParameters(
                [
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID => $spId,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA => $metadata,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256 => $metadataHashSha256,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT => $createdAt,
                ],
                [
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID => Types::BIGINT,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA => Types::TEXT,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE,
                ]
            );

        try {
            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Error executing query to insert SP Version. Error was: %s.', $exception->getMessage());
            $this->logger->error($message, compact('spId', 'metadataHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getIdpSpUserVersion(int $idpVersionId, int $spVersionId, int $userVersionId): Result
    {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $queryBuilder->select(
                TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID,
                TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_IDP_VERSION_ID,
                TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID,
                TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_USER_VERSION_ID,
                TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_CREATED_AT,
            )
                ->from($this->tableNameIdpSpUserVersion)
                ->where(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_IDP_VERSION_ID,
                            $queryBuilder->createNamedParameter($idpVersionId, ParameterType::INTEGER)
                        ),
                        $queryBuilder->expr()->eq(
                            TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID,
                            $queryBuilder->createNamedParameter($spVersionId, ParameterType::INTEGER)
                        ),
                        $queryBuilder->expr()->eq(
                            TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_USER_VERSION_ID,
                            $queryBuilder->createNamedParameter($userVersionId, ParameterType::INTEGER)
                        )
                    )
                )->setMaxResults(1);

            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to get IdpSpUserVersion for IdpVersion %s, SpVersion %s and UserVersion %s.' .
                ' Error was: %s.',
                $idpVersionId,
                $spVersionId,
                $userVersionId,
                $exception->getMessage()
            );
            $this->logger->error($message, compact('idpVersionId', 'spVersionId', 'userVersionId'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function insertIdpSpUserVersion(
        int $idpVersionId,
        int $spVersionId,
        int $userVersionId,
        DateTimeImmutable $createdAt = null
    ): void {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            $createdAt ??= new DateTimeImmutable();

            $queryBuilder->insert($this->tableNameIdpSpUserVersion)
                ->values(
                    [
                        TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_IDP_VERSION_ID => ':' .
                            TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_IDP_VERSION_ID,
                        TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID => ':' .
                            TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID,
                        TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_USER_VERSION_ID => ':' .
                            TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_USER_VERSION_ID,
                        TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_CREATED_AT => ':' .
                            TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_CREATED_AT,
                    ]
                )
                ->setParameters(
                    [
                        TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_IDP_VERSION_ID => $idpVersionId,
                        TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID => $spVersionId,
                        TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_USER_VERSION_ID => $userVersionId,
                        TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_CREATED_AT => $createdAt,
                    ],
                    [
                        TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_IDP_VERSION_ID => Types::BIGINT,
                        TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID => Types::BIGINT,
                        TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_USER_VERSION_ID => Types::BIGINT,
                        TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE
                    ]
                );

            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to insert IdpSpUserVersion. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message, compact('idpVersionId', 'spVersionId', 'userVersionId'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
