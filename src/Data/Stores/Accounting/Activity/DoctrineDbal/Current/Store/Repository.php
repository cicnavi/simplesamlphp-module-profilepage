<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Current\Store;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Traits\Repository\DeletableAuthenticationEventsTrait;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Repository as BaseRepository;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\TableConstants as BaseTableConstants;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants as VersionedBaseTableconstants;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use Throwable;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\EntityTableConstants;

class Repository extends BaseRepository
{
    use DeletableAuthenticationEventsTrait;

    protected string $tableNameAuthenticationEvent;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        parent::__construct($connection, $logger);

        $this->tableNameAuthenticationEvent =
            $this->preparePrefixedTableName(
                TableConstants::TABLE_NAME_AUTHENTICATION_EVENT
            );
    }

    /**
     * @throws StoreException
     */
    public function insertAuthenticationEvent(
        int $spId,
        int $userVersionId,
        DateTimeImmutable $happenedAt,
        string $clientIpAddress = null,
        string $authenticationProtocolDesignation = null,
        DateTimeImmutable $createdAt = null
    ): void {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            $createdAt ??= new DateTimeImmutable();

            $queryBuilder->insert($this->tableNameAuthenticationEvent)
                ->values(
                    [
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_SP_ID => ':' .
                            TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_SP_ID,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_USER_VERSION_ID => ':' .
                            TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_USER_VERSION_ID,
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
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_SP_ID =>
                            $spId,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_USER_VERSION_ID =>
                            $userVersionId,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT =>
                            $happenedAt->getTimestamp(),
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CLIENT_IP_ADDRESS => $clientIpAddress,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION =>
                            $authenticationProtocolDesignation,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CREATED_AT => $createdAt->getTimestamp(),
                    ],
                    [
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_SP_ID => Types::BIGINT,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_USER_VERSION_ID => Types::BIGINT,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT => Types::BIGINT,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CLIENT_IP_ADDRESS => Types::STRING,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION =>
                            Types::STRING,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CREATED_AT => Types::BIGINT,
                    ]
                );

            $queryBuilder->executeStatement();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to insert AuthenticationEvent. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message, compact('spId', 'userVersionId'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function getActivity(string $userIdentifierHashSha256, int $maxResults = null, int $firstResult = 0): array
    {
        try {
            $authenticationEventsQueryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $authenticationEventsQueryBuilder->select(
                //'cae.happened_at',
                TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT,
                TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CLIENT_IP_ADDRESS,
                TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION,
                //'cs.metadata AS sp_metadata',
                BaseTableConstants::TABLE_ALIAS_SP . '.' .
                BaseTableConstants::TABLE_SP_COLUMN_NAME_METADATA .
                ' AS ' . EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA,
                //'cuv.attributes AS user_attributes'
                VersionedBaseTableconstants::TABLE_ALIAS_USER_VERSION . '.' .
                VersionedBaseTableconstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES . ' AS ' .
                EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES
            )->from(
                //'cds_authentication_event', 'cae'
                $this->tableNameAuthenticationEvent,
                //'cae'
                TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT
            )
                ->leftJoin(
                    //'cae',
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT,
                    //'cds_sp',
                    $this->tableNameSp,
                    //'vs',
                    BaseTableConstants::TABLE_ALIAS_SP,
                    //'cae.sp_id = cs.id'
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                    TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_SP_ID . ' = ' .
                    BaseTableConstants::TABLE_ALIAS_SP . '.' .
                    BaseTableConstants::TABLE_SP_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'cae',
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT,
                    //'vds_user_version',
                    $this->tableNameUserVersion,
                    //'vuv',
                    VersionedBaseTableconstants::TABLE_ALIAS_USER_VERSION,
                    //'cae.user_version_id = vuv.id'
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                    TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_USER_VERSION_ID . ' = ' .
                    VersionedBaseTableconstants::TABLE_ALIAS_USER_VERSION . '.' .
                    VersionedBaseTableconstants::TABLE_USER_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'vuv',
                    VersionedBaseTableconstants::TABLE_ALIAS_USER_VERSION,
                    //'vds_user',
                    $this->tableNameUser,
                    //'vu',
                    VersionedBaseTableconstants::TABLE_ALIAS_USER,
                    //'vuv.user_id = vu.id'
                    VersionedBaseTableconstants::TABLE_ALIAS_USER_VERSION . '.' .
                    VersionedBaseTableconstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID . ' = ' .
                    VersionedBaseTableconstants::TABLE_ALIAS_USER . '.' .
                    VersionedBaseTableconstants::TABLE_USER_COLUMN_NAME_ID
                )
                ->where(
                    //'vu.identifier_hash_sha256 = ' .
                    VersionedBaseTableconstants::TABLE_ALIAS_USER . '.' .
                    VersionedBaseTableconstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 . ' = ' .
                    $authenticationEventsQueryBuilder->createNamedParameter($userIdentifierHashSha256)
                )
                ->orderBy(
                    //'cae.id',
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                    TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT,
                    'DESC'
                );

            if ($maxResults !== null) {
                $authenticationEventsQueryBuilder->setMaxResults($maxResults)->setFirstResult($firstResult);
            }

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
}
