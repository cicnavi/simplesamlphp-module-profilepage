<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Traits\Repository\DeletableAuthenticationEventsTrait;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository as BaseRepository;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants
    as BaseTableConstants;
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
        int $idpSpUserVersionId,
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
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT =>
                            $happenedAt->getTimestamp(),
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CLIENT_IP_ADDRESS => $clientIpAddress,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION =>
                            $authenticationProtocolDesignation,
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CREATED_AT => $createdAt->getTimestamp(),
                    ],
                    [
                        TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_IDP_SP_USER_VERSION_ID => Types::BIGINT,
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
            $this->logger->error($message, compact('idpSpUserVersionId'));
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
                //'vae.happened_at',
                TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT,
                TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_CLIENT_IP_ADDRESS,
                TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION,
                //'vsv.metadata AS sp_metadata',
                BaseTableConstants::TABLE_ALIAS_SP_VERSION . '.' .
                BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA .
                ' AS ' . EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA,
                //'vuv.attributes AS user_attributes'
                BaseTableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES . ' AS ' .
                EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES
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
                    BaseTableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                    //'vae.idp_sp_user_version_id = visuv.id'
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                    TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_IDP_SP_USER_VERSION_ID . ' = ' .
                    BaseTableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                    BaseTableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'visuv',
                    BaseTableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                    //'vds_sp_version',
                    $this->tableNameSpVersion,
                    //'vsv',
                    BaseTableConstants::TABLE_ALIAS_SP_VERSION,
                    //'visuv.sp_version_id = vsv.id'
                    BaseTableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                    BaseTableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID . ' = ' .
                    BaseTableConstants::TABLE_ALIAS_SP_VERSION . '.' .
                    BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'vsv',
                    BaseTableConstants::TABLE_ALIAS_SP_VERSION,
                    //'vds_sp',
                    $this->tableNameSp,
                    //'vs',
                    BaseTableConstants::TABLE_ALIAS_SP,
                    //'vsv.sp_id = vs.id'
                    BaseTableConstants::TABLE_ALIAS_SP_VERSION . '.' .
                    BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_SP_ID .
                    ' = ' . BaseTableConstants::TABLE_ALIAS_SP . '.' .
                    BaseTableConstants::TABLE_SP_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'visuv',
                    BaseTableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                    //'vds_user_version',
                    $this->tableNameUserVersion,
                    //'vuv',
                    BaseTableConstants::TABLE_ALIAS_USER_VERSION,
                    //'visuv.user_version_id = vuv.id'
                    BaseTableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                    BaseTableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_USER_VERSION_ID . ' = ' .
                    BaseTableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                    BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID
                )
                ->leftJoin(
                    //'vuv',
                    BaseTableConstants::TABLE_ALIAS_USER_VERSION,
                    //'vds_user',
                    $this->tableNameUser,
                    //'vu',
                    BaseTableConstants::TABLE_ALIAS_USER,
                    //'vuv.user_id = vu.id'
                    BaseTableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                    BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_USER_ID . ' = ' .
                    BaseTableConstants::TABLE_ALIAS_USER . '.' .
                    BaseTableConstants::TABLE_USER_COLUMN_NAME_ID
                )
                ->where(
                    //'vu.identifier_hash_sha256 = ' .
                    BaseTableConstants::TABLE_ALIAS_USER . '.' .
                    BaseTableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 . ' = ' .
                    $authenticationEventsQueryBuilder->createNamedParameter($userIdentifierHashSha256)
                )
                ->orderBy(
                //'vae.id',
                    TableConstants::TABLE_ALIAS_AUTHENTICATION_EVENT . '.' .
                    TableConstants::TABLE_AUTHENTICATION_EVENT_COLUMN_NAME_HAPPENED_AT,
                    'DESC'
                );

            if ($maxResults !== null) {
                $authenticationEventsQueryBuilder->setMaxResults($maxResults)
                    ->setFirstResult($firstResult);
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
