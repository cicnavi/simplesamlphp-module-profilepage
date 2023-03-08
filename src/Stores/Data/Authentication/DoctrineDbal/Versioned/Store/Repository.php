<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

use DateTimeImmutable;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use Throwable;

class Repository
{
    protected Connection $connection;
    protected LoggerInterface $logger;
    protected string $tableNameIdp;
    protected string $tableNameIdpVersion;
    protected string $tableNameSp;
    protected string $tableNameSpVersion;
    protected string $tableNameUser;
    protected string $tableNameUserVersion;
    protected string $tableNameIdpSpUserVersion;
    protected string $tableNameAuthenticationEvent;

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
        $this->tableNameIdpSpUserVersion =
            $this->preparePrefixedTableName(TableConstants::TABLE_NAME_IDP_SP_USER_VERSION);
        $this->tableNameAuthenticationEvent =
            $this->preparePrefixedTableName(TableConstants::TABLE_NAME_AUTHENTICATION_EVENT);
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

        $createdAt = $createdAt ?? new DateTimeImmutable();

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

        $createdAt = $createdAt ?? new DateTimeImmutable();

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

        $createdAt = $createdAt ?? new DateTimeImmutable();

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

        $createdAt = $createdAt ?? new DateTimeImmutable();

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
                    TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 . ' = ' .
                    $queryBuilder->createNamedParameter($identifierHashSha256)
                )->setMaxResults(1);

            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to get user by identifier hash SHA256 \'%s\'. Error was: %s.',
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
            $this->logger->error($message, compact('identifier', 'identifierHashSha256'));
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
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt = $createdAt ?? new DateTimeImmutable();

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

        try {
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

    /**
     * @throws StoreException
     */
    public function insertAuthenticationEvent(
        int $idpSpUserVersionId,
        DateTimeImmutable $happenedAt,
        string $clientIpAddress = null,
        string $authenticationProtocolDesignation = null,
        DateTimeImmutable $createdAt = null
    ): void {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            $createdAt = $createdAt ?? new DateTimeImmutable();

            $queryBuilder->insert($this->tableNameAuthenticationEvent)
                ->values(
                    [
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_IDP_SP_USER_VERSION_ID => ':' .
                            TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_IDP_SP_USER_VERSION_ID,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT => ':' .
                            TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CLIENT_IP_ADDRESS => ':' .
                            TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CLIENT_IP_ADDRESS,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION =>
                            ':' .
                            TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CREATED_AT => ':' .
                            TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CREATED_AT,
                    ]
                )
                ->setParameters(
                    [
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_IDP_SP_USER_VERSION_ID =>
                            $idpSpUserVersionId,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT => $happenedAt,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CLIENT_IP_ADDRESS => $clientIpAddress,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION =>
                            $authenticationProtocolDesignation,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CREATED_AT => $createdAt,
                    ],
                    [
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_IDP_SP_USER_VERSION_ID =>
                            Types::BIGINT,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT =>
                            Types::DATETIMETZ_IMMUTABLE,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CLIENT_IP_ADDRESS =>
                            Types::STRING,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION =>
                            Types::STRING,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CREATED_AT =>
                            Types::DATETIMETZ_IMMUTABLE,
                    ]
                );

            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to insert AuthenticationEvent. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message, compact('idpSpUserVersionId'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getConnectedServiceProviders(string $userIdentifierHashSha256): array
    {
        try {
            $authenticationEventsQueryBuilder = $this->connection->dbal()->createQueryBuilder();
            $lastMetadataAndAttributesQueryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $authenticationEventsQueryBuilder->select(
                //'vs.entity_id AS sp_entity_id',
                TableConstants::TABLE_ALIAS_SP . '.' .
                TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID . ' AS ' .
                TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_ENTITY_ID,
                //'COUNT(vae.id) AS number_of_authentications',
                'COUNT(' .  TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_ID . ') AS ' .
                TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS,
                //'MAX(vae.happened_at) AS last_authentication_at',
                'MAX(' .  TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT . ') AS ' .
                TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_LAST_AUTHENTICATION_AT,
                //'MIN(vae.happened_at) AS first_authentication_at',
                'MIN(' .  TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT . ') AS ' .
                TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_FIRST_AUTHENTICATION_AT,
            )->from($this->tableNameAuthenticationEvent, TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT)
                ->leftJoin(
                    //'vae',
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT,
                    //'vds_idp_sp_user_version',
                    $this->tableNameIdpSpUserVersion,
                    //'visuv',
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                    //'vae.idp_sp_user_version_id = visuv.id'
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                    TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_IDP_SP_USER_VERSION_ID . ' = ' .
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                    TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'visuv',
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                    //'vds_sp_version',
                    $this->tableNameSpVersion,
                    //'vsv',
                    TableConstants::TABLE_ALIAS_SP_VERSION,
                    //'visuv.sp_version_id = vsv.id'
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                    TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID . ' = ' .
                    TableConstants::TABLE_ALIAS_SP_VERSION . '.' . TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'vsv',
                    TableConstants::TABLE_ALIAS_SP_VERSION,
                    //'vds_sp',
                    $this->tableNameSp,
                    //'vs',
                    TableConstants::TABLE_ALIAS_SP,
                    //'vsv.sp_id = vs.id'
                    TableConstants::TABLE_ALIAS_SP_VERSION . '.' .
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID . ' = ' .
                    TableConstants::TABLE_ALIAS_SP . '.' . TableConstants::TABLE_SP_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'visuv',
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                    //'vds_user_version',
                    $this->tableNameUserVersion,
                    //'vuv',
                    TableConstants::TABLE_ALIAS_USER_VERSION,
                    //'visuv.user_version_id = vuv.id'
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                    TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_USER_VERSION_ID . ' = ' .
                    TableConstants::TABLE_ALIAS_USER_VERSION . '.' . TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'vuv',
                    TableConstants::TABLE_ALIAS_USER_VERSION,
                    //'vds_user',
                    $this->tableNameUser,
                    //'vu',
                    TableConstants::TABLE_ALIAS_USER,
                    //'vuv.user_id = vu.id'
                    TableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID . ' = ' .
                    TableConstants::TABLE_ALIAS_USER . '.' . TableConstants::TABLE_USER_COLUMN_NAME_ID
                )
                ->where(
                    //'vu.identifier_hash_sha256 = ' .
                    TableConstants::TABLE_ALIAS_USER . '.' .
                    TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 . ' = ' .
                    $authenticationEventsQueryBuilder->createNamedParameter($userIdentifierHashSha256)
                )
                ->groupBy(
                    //'vs.id'
                    TableConstants::TABLE_ALIAS_SP . '.' . TableConstants::TABLE_SP_COLUMN_NAME_ID
                )
                ->orderBy(
                    //'number_of_authentications',
                    TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS,
                    'DESC'
                );


            /** @psalm-suppress TooManyArguments */
            $lastMetadataAndAttributesQueryBuilder->select(
                //'vs.entity_id AS sp_entity_id',
                TableConstants::TABLE_ALIAS_SP . '.' . TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID . ' AS ' .
                TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_ENTITY_ID,
                //'vsv.metadata AS sp_metadata',
                TableConstants::TABLE_ALIAS_SP_VERSION . '.' . TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA .
                ' AS ' . TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_METADATA,
                //'vuv.attributes AS user_attributes',
                TableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES . ' AS ' .
                TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES
                //            'vsv.id AS sp_version_id',
                //            'vuv.id AS user_version_id',
            )->from(
                //'vds_authentication_event',
                $this->tableNameAuthenticationEvent,
                //'vae'
                TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT
            )
                ->leftJoin(
                    //'vae',
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT,
                    //'vds_idp_sp_user_version',
                    $this->tableNameIdpSpUserVersion,
                    //'visuv',
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                    //'vae.idp_sp_user_version_id = visuv.id'
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                    TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_IDP_SP_USER_VERSION_ID . ' = ' .
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                    TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'visuv',
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                    //'vds_sp_version',
                    $this->tableNameSpVersion,
                    //'vsv',
                    TableConstants::TABLE_ALIAS_SP_VERSION,
                    //'visuv.sp_version_id = vsv.id'
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                    TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID . ' = ' .
                    TableConstants::TABLE_ALIAS_SP_VERSION . '.' . TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'vsv',
                    TableConstants::TABLE_ALIAS_SP_VERSION,
                    //'vds_sp',
                    $this->tableNameSp,
                    //'vs',
                    TableConstants::TABLE_ALIAS_SP,
                    //'vsv.sp_id = vs.id'
                    TableConstants::TABLE_ALIAS_SP_VERSION . '.' .
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID . ' = ' . TableConstants::TABLE_ALIAS_SP . '.' .
                    TableConstants::TABLE_SP_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'visuv',
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                    //'vds_user_version',
                    $this->tableNameUserVersion,
                    //'vuv',
                    TableConstants::TABLE_ALIAS_USER_VERSION,
                    //'visuv.user_version_id = vuv.id'
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                    TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_USER_VERSION_ID . ' = ' .
                    TableConstants::TABLE_ALIAS_USER_VERSION . '.' . TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'vuv',
                    TableConstants::TABLE_ALIAS_USER_VERSION,
                    //'vds_user',
                    $this->tableNameUser,
                    //'vu',
                    TableConstants::TABLE_ALIAS_USER,
                    //'vuv.user_id = vu.id'
                    TableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID . ' = ' . TableConstants::TABLE_ALIAS_USER .
                    '.' . TableConstants::TABLE_USER_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'vsv',
                    TableConstants::TABLE_ALIAS_SP_VERSION,
                    //'vds_sp_version',
                    $this->tableNameSpVersion,
                    //'vsv2',
                    TableConstants::TABLE_ALIAS_SP_VERSION_2, // Another alias for self joining...
                    //'vsv.id = vsv2.id AND vsv.id < vsv2.id' // To be able to get latest one...
                    TableConstants::TABLE_ALIAS_SP_VERSION . '.' .
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID . ' = ' . TableConstants::TABLE_ALIAS_SP_VERSION_2 .
                    '.' . TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID . ' AND ' .
                    TableConstants::TABLE_ALIAS_SP_VERSION . '.' . TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID .
                    ' < ' . TableConstants::TABLE_ALIAS_SP_VERSION_2 . '.' .
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'vuv',
                    TableConstants::TABLE_ALIAS_USER_VERSION,
                    //'vds_user_version',
                    $this->tableNameUserVersion,
                    //'vuv2',
                    TableConstants::TABLE_ALIAS_USER_VERSION_2, // Another alias for self joining...
                    //'vuv.id = vuv2.id AND vuv.id < vuv2.id' // To be able to get latest one...
                    TableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID . ' = ' .
                    TableConstants::TABLE_ALIAS_USER_VERSION_2
                    . '.' . TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID . ' AND ' .
                    TableConstants::TABLE_ALIAS_USER_VERSION . '.' . TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID .
                    ' < ' . TableConstants::TABLE_ALIAS_USER_VERSION_2 . '.' .
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID
                )
                ->where(
                    //'vu.identifier_hash_sha256 = ' .
                    TableConstants::TABLE_ALIAS_USER . '.' .
                    TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 . ' = ' .
                    $lastMetadataAndAttributesQueryBuilder->createNamedParameter($userIdentifierHashSha256)
                )
                ->andWhere(
                    //'vsv2.id IS NULL'
                    TableConstants::TABLE_ALIAS_SP_VERSION_2 . '.' . TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID
                    . ' IS NULL'
                )
                ->andWhere(
                    //'vuv2.id IS NULL'
                    TableConstants::TABLE_ALIAS_USER_VERSION_2 . '.' .
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID . ' IS NULL'
                );

            $numberOfAuthentications = $authenticationEventsQueryBuilder->executeQuery()->fetchAllAssociativeIndexed();
            $lastMetadataAndAttributes =
                $lastMetadataAndAttributesQueryBuilder->executeQuery()->fetchAllAssociativeIndexed();

            return array_merge_recursive($numberOfAuthentications, $lastMetadataAndAttributes);
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to get connected organizations. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message, compact('userIdentifierHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getActivity(string $userIdentifierHashSha256, int $maxResults, int $firstResult): array
    {
        try {
            $authenticationEventsQueryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $authenticationEventsQueryBuilder->select(
                //'vae.happened_at',
                TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT,
                TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CLIENT_IP_ADDRESS,
                TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION,
                //'vsv.metadata AS sp_metadata',
                TableConstants::TABLE_ALIAS_SP_VERSION . '.' . TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA .
                ' AS ' . TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA,
                //'vuv.attributes AS user_attributes'
                TableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES . ' AS ' .
                TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES
            )->from(
                //'vds_authentication_event', 'vae'
                $this->tableNameAuthenticationEvent,
                //'vae'
                TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT
            )
                ->leftJoin(
                    //'vae',
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT,
                    //'vds_idp_sp_user_version',
                    $this->tableNameIdpSpUserVersion,
                    //'visuv',
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                    //'vae.idp_sp_user_version_id = visuv.id'
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                    TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_IDP_SP_USER_VERSION_ID . ' = ' .
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                    TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'visuv',
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                    //'vds_sp_version',
                    $this->tableNameSpVersion,
                    //'vsv',
                    TableConstants::TABLE_ALIAS_SP_VERSION,
                    //'visuv.sp_version_id = vsv.id'
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                    TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID . ' = ' .
                    TableConstants::TABLE_ALIAS_SP_VERSION . '.' . TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'vsv',
                    TableConstants::TABLE_ALIAS_SP_VERSION,
                    //'vds_sp',
                    $this->tableNameSp,
                    //'vs',
                    TableConstants::TABLE_ALIAS_SP,
                    //'vsv.sp_id = vs.id'
                    TableConstants::TABLE_ALIAS_SP_VERSION . '.' . TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID .
                    ' = ' . TableConstants::TABLE_ALIAS_SP . '.' . TableConstants::TABLE_SP_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'visuv',
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                    //'vds_user_version',
                    $this->tableNameUserVersion,
                    //'vuv',
                    TableConstants::TABLE_ALIAS_USER_VERSION,
                    //'visuv.user_version_id = vuv.id'
                    TableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                    TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_USER_VERSION_ID . ' = ' .
                    TableConstants::TABLE_ALIAS_USER_VERSION . '.' . TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'vuv',
                    TableConstants::TABLE_ALIAS_USER_VERSION,
                    //'vds_user',
                    $this->tableNameUser,
                    //'vu',
                    TableConstants::TABLE_ALIAS_USER,
                    //'vuv.user_id = vu.id'
                    TableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID . ' = ' . TableConstants::TABLE_ALIAS_USER .
                    '.' . TableConstants::TABLE_USER_COLUMN_NAME_ID
                )
                ->where(
                    //'vu.identifier_hash_sha256 = ' .
                    TableConstants::TABLE_ALIAS_USER . '.' .
                    TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 . ' = ' .
                    $authenticationEventsQueryBuilder->createNamedParameter($userIdentifierHashSha256)
                )
                ->orderBy(
                //'vae.id',
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                    TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_ID,
                    'DESC'
                )
            ->setMaxResults($maxResults)
            ->setFirstResult($firstResult);

            return $authenticationEventsQueryBuilder->executeQuery()->fetchAllAssociative();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to get connected organizations. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message, compact('userIdentifierHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function deleteAuthenticationEventsOlderThan(DateTimeImmutable $dateTime): void
    {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            $queryBuilder->delete($this->tableNameAuthenticationEvent)
                ->where(
                    $queryBuilder->expr()->lt(
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT,
                        $queryBuilder->createNamedParameter($dateTime, Types::DATETIME_IMMUTABLE)
                    )
                )->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to delete old authentication events. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message);
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
