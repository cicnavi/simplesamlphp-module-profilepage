<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned;

use DateTimeImmutable;
use Doctrine\DBAL\Result;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\TableConstants;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\accounting\Constants\RawRowResult;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store
 * @uses   \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses   \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses   \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses   \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory
 * @uses   \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Repository
 * @uses   \SimpleSAML\Module\accounting\Entities\Authentication\Event
 * @uses   \SimpleSAML\Module\accounting\Entities\Authentication\State
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000000CreateIdpTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000100CreateIdpVersionTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000200CreateSpTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000300CreateSpVersionTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000400CreateUserTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000500CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000600CreateIdpSpUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000700CreateAuthenticationEventTable
 * @uses \SimpleSAML\Module\accounting\Helpers\FilesystemHelper
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\HashDecoratedState
 * @uses \SimpleSAML\Module\accounting\Helpers\HashHelper
 * @uses \SimpleSAML\Module\accounting\Helpers\ArrayHelper
 * @uses \SimpleSAML\Module\accounting\Helpers\NetworkHelper
 * @uses \SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider\Bag
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider
 * @uses \SimpleSAML\Module\accounting\Entities\ConnectedServiceProvider
 * @uses \SimpleSAML\Module\accounting\Entities\User
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractRawEntity
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\RawConnectedServiceProvider
 * @uses \SimpleSAML\Module\accounting\Entities\Activity\Bag
 * @uses \SimpleSAML\Module\accounting\Entities\Activity
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\RawActivity
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\AbstractStore
 *
 * @psalm-suppress all
 */
class StoreTest extends TestCase
{
    protected Stub $moduleConfigurationStub;
    protected Migrator $migrator;
    protected Stub $factoryStub;
    protected Connection $connection;
    protected State $state;
    protected Event $authenticationEvent;
    protected Store\HashDecoratedState $hashDecoratedState;
    /**
     * @var MockObject|Store\Repository
     */
    protected $repositoryMock;
    /**
     * @var Result|Stub
     */
    protected $resultStub;
    /**
     * @var MockObject|LoggerInterface
     */
    protected $loggerMock;

    /**
     * @throws StoreException
     */
    protected function setUp(): void
    {
        $connectionParams = ConnectionParameters::DBAL_SQLITE_MEMORY;
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn($connectionParams);
        $this->moduleConfigurationStub->method('getUserIdAttributeName')
            ->willReturn('hrEduPersonPersistentID');

        $this->connection = new Connection($connectionParams);

        $this->loggerMock = $this->createMock(LoggerInterface::class);

        /** @psalm-suppress InvalidArgument */
        $this->migrator = new Migrator($this->connection, $this->loggerMock);

        $this->factoryStub = $this->createStub(Factory::class);
        $this->factoryStub->method('buildConnection')->willReturn($this->connection);
        $this->factoryStub->method('buildMigrator')->willReturn($this->migrator);

        $this->state = new State(StateArrays::FULL);
        $this->authenticationEvent = new Event($this->state);

        $this->hashDecoratedState = new Store\HashDecoratedState($this->state);
        $this->repositoryMock = $this->createMock(Store\Repository::class);

        $this->resultStub = $this->createStub(Result::class);
    }

    /**
     * @throws StoreException
     */
    public function testCanConstructInstance(): void
    {
        /** @psalm-suppress InvalidArgument */
        $this->assertInstanceOf(
            Store::class,
            new Store(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                null,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->factoryStub
            )
        );
    }

    /**
     * @throws StoreException
     */
    public function testCanBuildInstance(): void
    {
        /** @psalm-suppress InvalidArgument */
        $this->assertInstanceOf(
            Store::class,
            Store::build($this->moduleConfigurationStub, $this->loggerMock)
        );
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     * @throws MigrationException
     */
    public function testCanPersistAuthenticationEvent(): void
    {
        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub
        );
        $store->runSetup();

        $idpCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $idpVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $spCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $spVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $userCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $userVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $idpSpUserVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $authenticationEventCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();

        $idpCountQueryBuilder->select('COUNT(id) as idpCount')->from(
            //'vds_idp'
            $this->connection->preparePrefixedTableName(
                Store\TableConstants::TABLE_PREFIX . Store\TableConstants::TABLE_NAME_IDP
            )
        );
        $idpVersionCountQueryBuilder->select('COUNT(id) as idpVersionCount')->from(
            //'vds_idp_version'
            $this->connection->preparePrefixedTableName(
                Store\TableConstants::TABLE_PREFIX . Store\TableConstants::TABLE_NAME_IDP_VERSION
            )
        );
        $spCountQueryBuilder->select('COUNT(id) as spCount')->from(
            //'vds_sp'
            $this->connection->preparePrefixedTableName(
                Store\TableConstants::TABLE_PREFIX . Store\TableConstants::TABLE_NAME_SP
            )
        );
        $spVersionCountQueryBuilder->select('COUNT(id) as spVersionCount')->from(
            //'vds_sp_version'
            $this->connection->preparePrefixedTableName(
                Store\TableConstants::TABLE_PREFIX . Store\TableConstants::TABLE_NAME_SP_VERSION
            )
        );
        $userCountQueryBuilder->select('COUNT(id) as userCount')->from(
            //'vds_user'
            $this->connection->preparePrefixedTableName(
                Store\TableConstants::TABLE_PREFIX . Store\TableConstants::TABLE_NAME_USER
            )
        );
        $userVersionCountQueryBuilder->select('COUNT(id) as userVersionCount')->from(
            //'vds_user_version'
            $this->connection->preparePrefixedTableName(
                Store\TableConstants::TABLE_PREFIX . Store\TableConstants::TABLE_NAME_USER_VERSION
            )
        );
        $idpSpUserVersionCountQueryBuilder->select('COUNT(id) as idpSpUserVersionCount')
            ->from(
                //'vds_idp_sp_user_version'
                $this->connection->preparePrefixedTableName(
                    Store\TableConstants::TABLE_PREFIX . Store\TableConstants::TABLE_NAME_IDP_SP_USER_VERSION
                )
            );
        $authenticationEventCountQueryBuilder->select('COUNT(id) as authenticationEventCount')
            ->from(
                //'vds_authentication_event'
                $this->connection->preparePrefixedTableName(
                    Store\TableConstants::TABLE_PREFIX . Store\TableConstants::TABLE_NAME_AUTHENTICATION_EVENT
                )
            );

        $this->assertSame(0, (int)$idpCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$idpVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$spVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$userCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$userVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$idpSpUserVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$authenticationEventCountQueryBuilder->executeQuery()->fetchOne());

        $store->persist($this->authenticationEvent);

        $this->assertSame(1, (int)$idpCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$idpVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$spVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$idpSpUserVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$authenticationEventCountQueryBuilder->executeQuery()->fetchOne());

        $store->persist($this->authenticationEvent);

        $this->assertSame(1, (int)$idpCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$idpVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$spVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$idpSpUserVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(2, (int)$authenticationEventCountQueryBuilder->executeQuery()->fetchOne());
    }

    /**
     * @throws StoreException
     */
    public function testResolveIdpIdThrowsOnFirstGetIdpFailure(): void
    {
        $this->repositoryMock->method('getIdp')->willThrowException(new Exception('test'));
        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveIdpIdThrowsOnInsertAndGetIdpFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getIdp')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertIdp')->willThrowException(new Exception('test'));

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);
        $this->loggerMock->expects($this->once())->method('warning');

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveIdpVersionIdThrowsOnFirstGetIdpVersionFailure(): void
    {
        $this->repositoryMock->method('getIdpVersion')->willThrowException(new Exception('test'));
        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveIdpVersionIdThrowsOnInsertAndGetIdpVersionFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getIdpVersion')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertIdpVersion')->willThrowException(new Exception('test'));

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);
        $this->loggerMock->expects($this->once())->method('warning');

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveSpIdThrowsOnFirstGetSpFailure(): void
    {
        $this->repositoryMock->method('getSp')->willThrowException(new Exception('test'));
        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveSpIdThrowsOnInsertAndGetSpFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getSp')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertSp')->willThrowException(new Exception('test'));

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);
        $this->loggerMock->expects($this->once())->method('warning');

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveSpVersionIdThrowsOnFirstGetSpVersionFailure(): void
    {
        $this->repositoryMock->method('getSpVersion')->willThrowException(new Exception('test'));
        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveSpVersionIdThrowsOnInsertAndGetSpVersionFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getSpVersion')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertSpVersion')->willThrowException(new Exception('test'));

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);
        $this->loggerMock->expects($this->once())->method('warning');

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveUserIdThrowsOnInvalidUserIdentifierValue(): void
    {
        $moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $moduleConfigurationStub->method('getUserIdAttributeName')->willReturn('invalid');

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(UnexpectedValueException::class);

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveUserIdThrowsOnFirstGetUserFailure(): void
    {
        $this->repositoryMock->method('getUser')->willThrowException(new Exception('test'));
        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveUserIdThrowsOnInsertAndGetUserFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getUser')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertUser')->willThrowException(new Exception('test'));

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);
        $this->loggerMock->expects($this->once())->method('warning');

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveUserVersionIdThrowsOnFirstGetUserVersionFailure(): void
    {
        $this->repositoryMock->method('getUserVersion')->willThrowException(new Exception('test'));
        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveUserVersionIdThrowsOnInsertAndGetUserVersionFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getUserVersion')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertUserVersion')->willThrowException(new Exception('test'));

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);
        $this->loggerMock->expects($this->once())->method('warning');

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveIdpSpUserVersionIdThrowsOnFirstGetIdpSpUserVersionFailure(): void
    {
        $this->repositoryMock->method('getIdpSpUserVersion')->willThrowException(new Exception('test'));
        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testResolveIdpSpUserVersionIdThrowsOnInsertAndGetIdpSpUserVersionFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getIdpSpUserVersion')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertIdpSpUserVersion')->willThrowException(new Exception('test'));

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);
        $this->loggerMock->expects($this->once())->method('warning');

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testGetConnectedOrganizationsReturnsEmptyBagIfNoResults(): void
    {
        $this->repositoryMock->method('getConnectedServiceProviders')->willReturn([]);

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $connectedServiceProviderBag = $store->getConnectedOrganizations('test');

        $this->assertEmpty($connectedServiceProviderBag->getAll());
    }

    /**
     * @throws StoreException
     */
    public function testCanGetConnectedOrganizationsBag(): void
    {
        $this->repositoryMock->method('getConnectedServiceProviders')
            ->willReturn([RawRowResult::CONNECTED_ORGANIZATION]);

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $connectedServiceProviderBag = $store->getConnectedOrganizations('test');

        $this->assertNotEmpty($connectedServiceProviderBag->getAll());
    }

    /**
     * @throws StoreException
     */
    public function testGetConnectedOrganizationsThrowsForInvalidResult(): void
    {
        $rawResult = RawRowResult::CONNECTED_ORGANIZATION;
        unset($rawResult[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]);

        $this->repositoryMock->method('getConnectedServiceProviders')
            ->willReturn([$rawResult]);

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);
        $store->getConnectedOrganizations('test');
    }

    /**
     * @throws StoreException
     */
    public function testGetActivityReturnsEmptyBagIfNoResults(): void
    {
        $this->repositoryMock->method('getActivity')->willReturn([]);

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $activityBag = $store->getActivity('test', 10, 0);

        $this->assertEmpty($activityBag->getAll());
    }

    /**
     * @throws StoreException
     */
    public function testCanGetActivityBag(): void
    {
        $this->repositoryMock->method('getActivity')
            ->willReturn([RawRowResult::ACTIVITY]);

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $activityBag = $store->getActivity('test', 10, 0);

        $this->assertNotEmpty($activityBag->getAll());
    }

    /**
     * @throws StoreException
     */
    public function testGetActivityThrowsForInvalidResult(): void
    {
        $rawResult = RawRowResult::ACTIVITY;
        unset($rawResult[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT]);

        $this->repositoryMock->method('getActivity')
            ->willReturn([$rawResult]);

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);
        $store->getActivity('test', 10, 0);
    }

    /**
     * @throws StoreException
     */
    public function testCanDeleteDataOlderThan(): void
    {
        $dateTime = new DateTimeImmutable();

        $this->repositoryMock->expects($this->once())
            ->method('deleteAuthenticationEventsOlderThan')
            ->with($dateTime);

        /** @psalm-suppress InvalidArgument */
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->repositoryMock
        );

        $store->deleteDataOlderThan($dateTime);
    }
}
