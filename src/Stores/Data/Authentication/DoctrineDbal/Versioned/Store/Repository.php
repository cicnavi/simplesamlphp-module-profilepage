<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

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

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;

        $this->tableNameIdp = $this->preparePrefixedTableName(TableConstants::TABLE_NAME_IDP);
    }

    /**
     * @throws StoreException
     */
    public function getIdpByEntityIdHashSha256(string $entityIdHashSha256): Result
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
                $queryBuilder->createNamedParameter($entityIdHashSha256)
            )->setMaxResults(1);

        try {
            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error getting IdP by entity ID hash SHA256 \'%s\'. Error was: %s.',
                $entityIdHashSha256,
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
            $message = sprintf('Could not insert job (%s)', $exception->getMessage());
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
