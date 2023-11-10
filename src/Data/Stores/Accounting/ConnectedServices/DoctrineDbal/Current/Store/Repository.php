<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store;

use DateTimeImmutable;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Repository as BaseRepository;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\TableConstants as BaseTableConstants;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants as VersionedBaseTableConstants;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Traits\Repository\DeletableConnectedServicesTrait;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use Throwable;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\EntityTableConstants;

class Repository extends BaseRepository
{
    use DeletableConnectedServicesTrait;

    protected string $tableNameConnectedService;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        parent::__construct($connection, $logger);

        $this->tableNameConnectedService = $this->preparePrefixedTableName(
            TableConstants::TABLE_NAME_CONNECTED_SERVICE
        );
    }

    /**
     * @throws StoreException
     */
    public function getConnectedService(int $spId, int $userId): Result
    {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $queryBuilder->select(
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_ID,
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_SP_ID,
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_ID,
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_VERSION_ID,
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT,
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT,
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_COUNT,
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_CREATED_AT,
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT,
            )
            ->from($this->tableNameConnectedService)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_SP_ID,
                        $queryBuilder->createNamedParameter($spId, ParameterType::INTEGER)
                    ),
                    $queryBuilder->expr()->eq(
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_ID,
                        $queryBuilder->createNamedParameter($userId, ParameterType::INTEGER)
                    )
                )
            )->setMaxResults(1);

            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to get connected service. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message, compact('spId', 'userId'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function insertConnectedService(
        int $spId,
        int $userId,
        int $userVersionId,
        DateTimeImmutable $firstAuthenticationAt = null,
        DateTimeImmutable $lastAuthenticationAt = null,
        int $count = 1,
        DateTimeImmutable $createdUpdatedAt = null
    ): void {

        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            $firstAuthenticationAt ??= new DateTimeImmutable();
            $lastAuthenticationAt ??= $firstAuthenticationAt;
            $count = max($count, 1);
            $createdUpdatedAt ??= new DateTimeImmutable();

            $queryBuilder->insert($this->tableNameConnectedService)
                ->values(
                    [
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_SP_ID => ':' .
                            TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_SP_ID,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_ID => ':' .
                            TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_ID,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_VERSION_ID => ':' .
                            TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_VERSION_ID,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT => ':' .
                            TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT => ':' .
                            TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_COUNT => ':' .
                            TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_COUNT,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_CREATED_AT => ':' .
                            TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_CREATED_AT,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT => ':' .
                            TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT,
                    ]
                )
                ->setParameters(
                    [
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_SP_ID => $spId,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_ID => $userId,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_VERSION_ID => $userVersionId,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT =>
                            $firstAuthenticationAt->getTimestamp(),
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT =>
                            $lastAuthenticationAt->getTimestamp(),
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_COUNT => $count,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_CREATED_AT =>
                            $createdUpdatedAt->getTimestamp(),
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT =>
                            $createdUpdatedAt->getTimestamp(),
                    ],
                    [
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_SP_ID => Types::BIGINT,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_ID => Types::BIGINT,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_VERSION_ID => Types::BIGINT,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT =>
                            Types::BIGINT,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT =>
                            Types::BIGINT,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_COUNT => Types::BIGINT,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_CREATED_AT => Types::BIGINT,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT => Types::BIGINT,
                    ]
                );

            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to insert connected service. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error(
                $message,
                compact('spId', 'userId', 'count', 'firstAuthenticationAt', 'lastAuthenticationAt')
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function updateConnectedServiceVersionCount(
        int $connectedServiceId,
        int $userVersionId,
        DateTimeImmutable $happenedAt,
        int $incrementCountBy = 1
    ): void {
        $incrementCountBy = max($incrementCountBy, 1);

        try {
            $updateCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();

            $updateCountQueryBuilder->update($this->tableNameConnectedService)
                ->set(
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_COUNT,
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_COUNT . ' + ' . $incrementCountBy
                )
                ->set(
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_VERSION_ID,
                    ':' . TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_VERSION_ID
                )
                ->set(
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT,
                    ':' . TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT
                )
                ->set(
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT,
                    ':' . TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT
                )
                ->setParameter(
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_VERSION_ID,
                    $userVersionId,
                    Types::INTEGER
                )
                ->setParameter(
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT,
                    $happenedAt->getTimestamp(),
                    Types::BIGINT
                )
                ->setParameter(
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT,
                    (new DateTimeImmutable())->getTimestamp(),
                    Types::BIGINT
                )
                ->where(
                    $updateCountQueryBuilder->expr()->and(
                        $updateCountQueryBuilder->expr()->eq(
                            TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_ID,
                            $updateCountQueryBuilder->createNamedParameter($connectedServiceId, Types::INTEGER)
                        )
                    )
                );

            $updateCountQueryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing update count for connected service. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error(
                $message,
                compact('connectedServiceId', 'userVersionId', 'happenedAt', 'incrementCountBy')
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getConnectedServices(string $userIdentifierHashSha256): array
    {
        try {
            $connectedServicesQueryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $connectedServicesQueryBuilder->select(
                BaseTableConstants::TABLE_ALIAS_SP . '.' .
                BaseTableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID . ' AS ' .
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_ENTITY_ID,
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '.' .
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_COUNT . ' AS ' .
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS,
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '.' .
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT . ' AS ' .
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT,
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '.' .
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT . ' AS ' .
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT,
                BaseTableConstants::TABLE_ALIAS_SP . '.' .
                BaseTableConstants::TABLE_SP_COLUMN_NAME_METADATA . ' AS ' .
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA,
                VersionedBaseTableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                VersionedBaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES . ' AS ' .
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES,
            )->from($this->tableNameConnectedService, TableConstants::TABLE_ALIAS_CONNECTED_SERVICE)
            ->innerJoin(
                //'ccs',
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE,
                //'cds_sp',
                $this->tableNameSp,
                //'cs',
                BaseTableConstants::TABLE_ALIAS_SP,
                //'ccs.sp_id = cs.id'
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '.' .
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_SP_ID .
                ' = ' . BaseTableConstants::TABLE_ALIAS_SP . '.' .
                BaseTableConstants::TABLE_SP_COLUMN_NAME_ID
            )
            ->innerJoin(
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE,
                $this->tableNameUser,
                VersionedBaseTableConstants::TABLE_ALIAS_USER,
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '.' .
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_ID . ' = ' .
                VersionedBaseTableConstants::TABLE_ALIAS_USER . '.' .
                VersionedBaseTableConstants::TABLE_USER_COLUMN_NAME_ID
            )
            ->innerJoin(
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE,
                $this->tableNameUserVersion,
                VersionedBaseTableConstants::TABLE_ALIAS_USER_VERSION,
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '.' .
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_USER_VERSION_ID . ' = ' .
                VersionedBaseTableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                VersionedBaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID
            )
            ->where(
                VersionedBaseTableConstants::TABLE_ALIAS_USER . '.' .
                VersionedBaseTableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 . ' = ' .
                $connectedServicesQueryBuilder->createNamedParameter($userIdentifierHashSha256)
            )->orderBy(
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS,
                'DESC'
            );

            return $connectedServicesQueryBuilder->executeQuery()->fetchAllAssociative();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to get connected services. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message, compact('userIdentifierHashSha256'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }
}
