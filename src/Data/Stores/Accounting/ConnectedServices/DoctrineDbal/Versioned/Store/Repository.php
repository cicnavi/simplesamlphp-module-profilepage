<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store;

use DateTimeImmutable;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository as BaseRepository;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants as BaseTableConstants;
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
    public function getConnectedService(int $idpSpUserVersionId): Result
    {
        try {
            $queryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $queryBuilder->select(
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_ID,
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_IDP_SP_USER_VERSION_ID,
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
                            TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_IDP_SP_USER_VERSION_ID,
                            $queryBuilder->createNamedParameter($idpSpUserVersionId, ParameterType::INTEGER)
                        )
                    )
                )->setMaxResults(1);

            return $queryBuilder->executeQuery();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error executing query to get connected service. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error($message, compact('idpSpUserVersionId'));
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function insertConnectedService(
        int $idpSpUserVersionId,
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
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_IDP_SP_USER_VERSION_ID => ':' .
                            TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_IDP_SP_USER_VERSION_ID,
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
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_IDP_SP_USER_VERSION_ID =>
                            $idpSpUserVersionId,
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
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_IDP_SP_USER_VERSION_ID => Types::BIGINT,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT => Types::BIGINT,
                        TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT => Types::BIGINT,
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
                compact('idpSpUserVersionId', 'count', 'firstAuthenticationAt', 'lastAuthenticationAt')
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function updateConnectedServiceVersionCount(
        int $connectedServiceId,
        DateTimeImmutable $happenedAt,
        int $incrementCountBy = 1
    ): void {
        try {
            $incrementCountBy = max($incrementCountBy, 1);

            $updateCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();

            $updateCountQueryBuilder->update($this->tableNameConnectedService)
                ->set(
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_COUNT,
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_COUNT . ' + ' . $incrementCountBy
                )
                ->set(
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT,
                    ':' . TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT
                )
                ->setParameter(
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT,
                    $happenedAt->getTimestamp(),
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
                compact('connectedServiceId', 'happenedAt', 'incrementCountBy')
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @throws StoreException
     */
    public function touchConnectedServiceVersionsTimestamp(
        int $userId,
        int $spId,
        DateTimeImmutable $happenedAt = null
    ): void {
        try {
            $happenedAt ??= new DateTimeImmutable();

            $selectConnectedServiceVersionsQueryBuilder = $this->connection->dbal()->createQueryBuilder();

            $selectConnectedServiceVersionsQueryBuilder->select(
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '. ' .
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_ID
            )
                ->from($this->tableNameConnectedService, TableConstants::TABLE_ALIAS_CONNECTED_SERVICE)
                ->innerJoin(
                //'vcs',
                    TableConstants::TABLE_ALIAS_CONNECTED_SERVICE,
                    //'vds_idp_sp_user_version',
                    $this->tableNameIdpSpUserVersion,
                    //'visuv',
                    BaseTableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                    //'vcs.idp_sp_user_version_id =  visuv.id'
                    TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '.' .
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_IDP_SP_USER_VERSION_ID . ' = ' .
                    BaseTableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                    BaseTableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID
                )->innerJoin(
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
                )->innerJoin(
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
                )->innerJoin(
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
                )->innerJoin(
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
                    $selectConnectedServiceVersionsQueryBuilder->expr()->and(
                        $selectConnectedServiceVersionsQueryBuilder->expr()->eq(
                        //'vs.id = ' .
                            BaseTableConstants::TABLE_ALIAS_SP . '.' . BaseTableConstants::TABLE_SP_COLUMN_NAME_ID,
                            $selectConnectedServiceVersionsQueryBuilder->createNamedParameter($spId)
                        ),
                        $selectConnectedServiceVersionsQueryBuilder->expr()->eq(
                        //'vu.id = ' .
                            BaseTableConstants::TABLE_ALIAS_USER . '.' . BaseTableConstants::TABLE_USER_COLUMN_NAME_ID,
                            $selectConnectedServiceVersionsQueryBuilder->createNamedParameter($userId)
                        )
                    )
                );

            /** @var array<array-key,string> $connectedServiceVersions */
            $connectedServiceVersions = $selectConnectedServiceVersionsQueryBuilder->executeQuery()->fetchFirstColumn();
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error selecting connected service versions. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error(
                $message,
                compact('userId', 'spId', 'happenedAt')
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }

        $updateLastAuthenticationAtQueryBuilder = $this->connection->dbal()->createQueryBuilder();

        $updateLastAuthenticationAtQueryBuilder->update($this->tableNameConnectedService)
            ->set(
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT,
                ':' . TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT
            )
            ->setParameter(
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT,
                $happenedAt->getTimestamp(),
                Types::BIGINT
            )
            ->where(
                $updateLastAuthenticationAtQueryBuilder->expr()->in(
                    TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_ID,
                    $connectedServiceVersions
                )
            );

        try {
            $updateLastAuthenticationAtQueryBuilder->executeStatement();
            // @codeCoverageIgnoreStart
        } catch (Throwable $exception) {
            $message = sprintf(
                'Error touching connected service versions. Error was: %s.',
                $exception->getMessage()
            );
            $this->logger->error(
                $message,
                compact('userId', 'spId', 'happenedAt')
            );
            throw new StoreException($message, (int)$exception->getCode(), $exception);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws StoreException
     */
    public function getConnectedServices(string $userIdentifierHashSha256): array
    {
        try {
            $connectedServicesQueryBuilder = $this->connection->dbal()->createQueryBuilder();
            $lastMetadataAndAttributesQueryBuilder = $this->connection->dbal()->createQueryBuilder();

            /** @psalm-suppress TooManyArguments */
            $connectedServicesQueryBuilder->select(
                //'vs.entity_id AS sp_entity_id',
                BaseTableConstants::TABLE_ALIAS_SP . '.' .
                BaseTableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID . ' AS ' .
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_ENTITY_ID,
                //'SUM(vcs.count) AS number_of_authentications',
                'SUM(' .  TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '.' .
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_COUNT . ') AS ' .
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS,
                //'MAX(vcs.last_authentication_at) AS last_authentication_at',
                'MAX(' .  TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '.' .
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT . ') AS ' .
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT,
                //'MIN(vcs.first_authentication_at) AS first_authentication_at',
                'MIN(' .  TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '.' .
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT . ') AS ' .
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT,
            )->from($this->tableNameConnectedService, TableConstants::TABLE_ALIAS_CONNECTED_SERVICE)
            ->innerJoin(
                //'vcs',
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE,
                //'vds_idp_sp_user_version',
                $this->tableNameIdpSpUserVersion,
                //'visuv',
                BaseTableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                //'vcs.idp_sp_user_version_id =  visuv.id'
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '.' .
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_IDP_SP_USER_VERSION_ID . ' = ' .
                BaseTableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                BaseTableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID
            )->innerJoin(
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
            )->innerJoin(
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
            )->innerJoin(
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
            )->innerJoin(
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
                $connectedServicesQueryBuilder->createNamedParameter($userIdentifierHashSha256)
            )->groupBy(
                //'sp_entity_id'
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_ENTITY_ID
            )->orderBy(
                //'number_of_authentications',
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS,
                'DESC'
            );

            /** @psalm-suppress TooManyArguments */
            $lastMetadataAndAttributesQueryBuilder->select(
                //'vs.entity_id AS sp_entity_id',
                BaseTableConstants::TABLE_ALIAS_SP . '.' .
                BaseTableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID . ' AS ' .
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_ENTITY_ID,
                //'vsv.metadata AS sp_metadata',
                BaseTableConstants::TABLE_ALIAS_SP_VERSION . '.' .
                BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA . ' AS ' .
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA,
                //'vuv.attributes AS user_attributes',
                BaseTableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES . ' AS ' .
                EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES
            )->from(
                //'vds_connected_service',
                $this->tableNameConnectedService,
                //'vcs'
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE
            )->innerJoin(
            //'vcs',
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE,
                //'vds_idp_sp_user_version',
                $this->tableNameIdpSpUserVersion,
                //'visuv',
                BaseTableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION,
                //'vcs.idp_sp_user_version_id =  visuv.id'
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '.' .
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_IDP_SP_USER_VERSION_ID . ' = ' .
                BaseTableConstants::TABLE_ALIAS_IDP_SP_USER_VERSION . '.' .
                BaseTableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID
            )->innerJoin(
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
            )->innerJoin(
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
            )->innerJoin(
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
            )->innerJoin(
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
            // SP version join for latest one capability
            ->leftJoin(
                //'vsv',
                BaseTableConstants::TABLE_ALIAS_SP_VERSION,
                //'vds_sp_version',
                $this->tableNameSpVersion,
                //'vsv2',
                BaseTableConstants::TABLE_ALIAS_SP_VERSION_2, // Another alias for self joining...
                //'vsv.id = vsv2.id AND vsv.id < vsv2.id' // To be able to get latest one...
                BaseTableConstants::TABLE_ALIAS_SP_VERSION . '.' .
                BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID . ' = ' .
                BaseTableConstants::TABLE_ALIAS_SP_VERSION_2 . '.' .
                BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID . ' AND ' .
                BaseTableConstants::TABLE_ALIAS_SP_VERSION . '.' .
                BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID . ' < ' .
                BaseTableConstants::TABLE_ALIAS_SP_VERSION_2 . '.' .
                BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID
            )
            // User version join for latest one capability
            ->leftJoin(
                //'vuv',
                BaseTableConstants::TABLE_ALIAS_USER_VERSION,
                //'vds_user_version',
                $this->tableNameUserVersion,
                //'vuv2',
                BaseTableConstants::TABLE_ALIAS_USER_VERSION_2, // Another alias for self joining...
                //'vuv.id = vuv2.id AND vuv.id < vuv2.id' // To be able to get latest one...
                BaseTableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID . ' = ' .
                BaseTableConstants::TABLE_ALIAS_USER_VERSION_2 . '.' .
                BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID . ' AND ' .
                BaseTableConstants::TABLE_ALIAS_USER_VERSION . '.' .
                BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID . ' < ' .
                BaseTableConstants::TABLE_ALIAS_USER_VERSION_2 . '.' .
                BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID
            )->where(
                //'vu.identifier_hash_sha256 = ' .
                BaseTableConstants::TABLE_ALIAS_USER . '.' .
                BaseTableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256 . ' = ' .
                $lastMetadataAndAttributesQueryBuilder->createNamedParameter($userIdentifierHashSha256)
            )->andWhere(
                //'vsv2.id IS NULL'
                BaseTableConstants::TABLE_ALIAS_SP_VERSION_2 . '.' .
                BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID . ' IS NULL'
            )->andWhere(
                //'vuv2.id IS NULL'
                BaseTableConstants::TABLE_ALIAS_USER_VERSION_2 . '.' .
                BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID . ' IS NULL'
            )->orderBy(
                TableConstants::TABLE_ALIAS_CONNECTED_SERVICE . '.' .
                TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_IDP_SP_USER_VERSION_ID,
                'ASC'
            );

            $connectedServices = $connectedServicesQueryBuilder->executeQuery()->fetchAllAssociativeIndexed();
            $lastMetadataAndAttributes = $lastMetadataAndAttributesQueryBuilder
                ->executeQuery()
                ->fetchAllAssociativeIndexed();

            return array_merge_recursive($connectedServices, $lastMetadataAndAttributes);
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
