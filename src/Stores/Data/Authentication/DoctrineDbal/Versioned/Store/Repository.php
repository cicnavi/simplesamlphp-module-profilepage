<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

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
    }

    /**
     * @throws StoreException
     */
    public function getIdpByEntityIdHashSha256(string $idpEntityIdHashSha256): Result
    {
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
                TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 .  ' = ' .
                $queryBuilder->createNamedParameter($idpEntityIdHashSha256)
            )->setMaxResults(1);

        try {
            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error getting IdP by entity ID hash SHA256 \'%s\'. Error was: %s.',
                $idpEntityIdHashSha256,
                $exception->getMessage()
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getIdpVersion(int $idpId, string $payloadHashSha256): Result
    {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        /** @psalm-suppress TooManyArguments */
        $queryBuilder->select(
            TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_ID,
            TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID,
            TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD,
            TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256,
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
                        TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256,
                        $queryBuilder->createNamedParameter($payloadHashSha256)
                    )
                )
            )->setMaxResults(1);


        try {
            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error getting IdP Version for IdP %s and metadata array hash %s. Error was: %s.',
                $idpId,
                $payloadHashSha256,
                $exception->getMessage()
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    protected function preparePrefixedTableName(string $tableName): string
    {
        return $this->connection->preparePrefixedTableName(TableConstants::TABLE_PREFIX . $tableName);
    }

    /**
     * @throws StoreException
     */
    public function insertIdp(
        string $idpEntityId,
        string $idpEntityIdHashSha256,
        \DateTimeImmutable $createdAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt = $createdAt ?? new \DateTimeImmutable();

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
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID => $idpEntityId,
                    TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => $idpEntityIdHashSha256,
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
            $message = sprintf('Could not insert IdP. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function insertIdpVersion(
        int $idpId,
        string $payload,
        string $payloadHashSha256,
        \DateTimeImmutable $createdAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt = $createdAt ?? new \DateTimeImmutable();

        $queryBuilder->insert($this->tableNameIdpVersion)
            ->values(
                [
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID => ':' .
                        TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD => ':' .
                        TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256 => ':' .
                        TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT => ':' .
                        TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT,
                ]
            )
            ->setParameters(
                [
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID => $idpId,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD => $payload,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256 => $payloadHashSha256,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT => $createdAt,
                ],
                [
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_IDP_ID => Types::BIGINT,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD => Types::TEXT,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE,
                ]
            );

        try {
            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Could not insert IdP Version. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getSpByEntityIdHashSha256(string $spEntityIdHashSha256): Result
    {
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
                TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 .  ' = ' .
                $queryBuilder->createNamedParameter($spEntityIdHashSha256)
            )->setMaxResults(1);

        try {
            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error getting SP by entity ID hash SHA256 \'%s\'. Error was: %s.',
                $spEntityIdHashSha256,
                $exception->getMessage()
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    public function insertSp(
        string $spEntityId,
        string $spEntityIdHashSha256,
        \DateTimeImmutable $createdAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt = $createdAt ?? new \DateTimeImmutable();

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
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID => $spEntityId,
                    TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256 => $spEntityIdHashSha256,
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
            $message = sprintf('Could not insert SP. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getSpVersion(int $spId, string $spMetadataArrayHashSha256): Result
    {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        /** @psalm-suppress TooManyArguments */
        $queryBuilder->select(
            TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID,
            TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID,
            TableConstants::TABLE_SP_VERSION_COLUMN_NAME_PAYLOAD,
            TableConstants::TABLE_SP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256,
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
                        TableConstants::TABLE_SP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256,
                        $queryBuilder->createNamedParameter($spMetadataArrayHashSha256)
                    )
                )
            )->setMaxResults(1);


        try {
            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error getting SP Version for SP %s and metadata array hash %s. Error was: %s.',
                $spId,
                $spMetadataArrayHashSha256,
                $exception->getMessage()
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function insertSpVersion(
        int $spId,
        string $payload,
        string $payloadHashSha256,
        \DateTimeImmutable $createdAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt = $createdAt ?? new \DateTimeImmutable();

        $queryBuilder->insert($this->tableNameSpVersion)
            ->values(
                [
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID => ':' .
                        TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_PAYLOAD => ':' .
                        TableConstants::TABLE_SP_VERSION_COLUMN_NAME_PAYLOAD,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256 => ':' .
                        TableConstants::TABLE_SP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT => ':' .
                        TableConstants::TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT,
                ]
            )
            ->setParameters(
                [
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID => $spId,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_PAYLOAD => $payload,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256 => $payloadHashSha256,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT => $createdAt,
                ],
                [
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID => Types::BIGINT,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_PAYLOAD => Types::TEXT,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE,
                ]
            );

        try {
            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Could not insert SP Version. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getUserByIdentifierHashSha256(string $identifierHashSha256): Result
    {
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
                TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 .  ' = ' .
                $queryBuilder->createNamedParameter($identifierHashSha256)
            )->setMaxResults(1);

        try {
            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error getting user by identifier hash SHA256 \'%s\'. Error was: %s.',
                $identifierHashSha256,
                $exception->getMessage()
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function insertUser(
        string $identifier,
        string $identifierHashSha256,
        \DateTimeImmutable $createdAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt = $createdAt ?? new \DateTimeImmutable();

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
            $message = sprintf('Could not insert user. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getUserVersion(int $userId, string $payloadHashSha256): Result
    {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        /** @psalm-suppress TooManyArguments */
        $queryBuilder->select(
            TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID,
            TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID,
            TableConstants::TABLE_USER_VERSION_COLUMN_NAME_PAYLOAD,
            TableConstants::TABLE_USER_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256,
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
                        TableConstants::TABLE_USER_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256,
                        $queryBuilder->createNamedParameter($payloadHashSha256)
                    )
                )
            )->setMaxResults(1);


        try {
            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error getting user version for user ID %s and attribute array hash %s. Error was: %s.',
                $userId,
                $payloadHashSha256,
                $exception->getMessage()
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function insertUserVersion(
        int $userId,
        string $payload,
        string $payloadHashSha256,
        \DateTimeImmutable $createdAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt = $createdAt ?? new \DateTimeImmutable();

        $queryBuilder->insert($this->tableNameUserVersion)
            ->values(
                [
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID => ':' .
                        TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_PAYLOAD => ':' .
                        TableConstants::TABLE_USER_VERSION_COLUMN_NAME_PAYLOAD,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256 => ':' .
                        TableConstants::TABLE_USER_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT => ':' .
                        TableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT,
                ]
            )
            ->setParameters(
                [
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID => $userId,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_PAYLOAD => $payload,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256 => $payloadHashSha256,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT => $createdAt,
                ],
                [
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID => Types::BIGINT,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_PAYLOAD => Types::TEXT,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_PAYLOAD_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT => Types::DATETIMETZ_IMMUTABLE,
                ]
            );

        try {
            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf('Could not insert user version. Error was: %s.', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
