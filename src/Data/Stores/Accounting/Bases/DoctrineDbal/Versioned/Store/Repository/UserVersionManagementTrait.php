<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use Throwable;

trait UserVersionManagementTrait
{
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

        $createdAt ??= new DateTimeImmutable();

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
                    TableConstants::TABLE_USER_COLUMN_NAME_CREATED_AT => $createdAt->getTimestamp(),
                ],
                [
                    TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER => Types::TEXT,
                    TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_USER_COLUMN_NAME_CREATED_AT => Types::BIGINT
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
     * @throws \Exception
     */
    public function insertUserVersion(
        int $userId,
        string $attributes,
        string $attributesHashSha256,
        DateTimeImmutable $createdAt = null
    ): void {
        $queryBuilder = $this->connection->dbal()->createQueryBuilder();

        $createdAt ??= new DateTimeImmutable();

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
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT => $createdAt->getTimestamp(),
                ],
                [
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID => Types::BIGINT,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES => Types::TEXT,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES_HASH_SHA256 => Types::STRING,
                    TableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT => Types::BIGINT,
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
