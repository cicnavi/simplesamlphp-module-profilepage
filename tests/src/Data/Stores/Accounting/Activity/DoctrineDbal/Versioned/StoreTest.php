<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned;

use DateTimeImmutable;
use Doctrine\DBAL\Result;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\TableConstants;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants as BaseTableConstants;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\HashDecoratedState;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event\State;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\profilepage\Constants\RawRowResult;
use SimpleSAML\Test\Module\profilepage\Constants\StateArrays;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\EntityTableConstants;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000000CreateIdpTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000100CreateIdpVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000200CreateSpTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000300CreateSpVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000400CreateUserTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000500CreateUserVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000600CreateIdpSpUserVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000700CreateAuthenticationEventTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\HashDecoratedState
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateSpTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateSpVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpSpUserVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Helpers\Filesystem
 * @uses \SimpleSAML\Module\profilepage\Helpers\Hash
 * @uses \SimpleSAML\Module\profilepage\Helpers\Arr
 * @uses \SimpleSAML\Module\profilepage\Helpers\Network
 * @uses \SimpleSAML\Module\profilepage\Entities\ConnectedService\Bag
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractProvider
 * @uses \SimpleSAML\Module\profilepage\Entities\ConnectedService
 * @uses \SimpleSAML\Module\profilepage\Entities\User
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity
 * @uses \SimpleSAML\Module\profilepage\Entities\Activity\Bag
 * @uses \SimpleSAML\Module\profilepage\Entities\Activity
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\RawActivity
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Entities\Providers\Service\Saml2
 * @uses \SimpleSAML\Module\profilepage\Helpers\ProviderResolver
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Saml2
 * @uses \SimpleSAML\Module\profilepage\Factories\SerializerFactory
 * @uses \SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer
 */
class StoreTest extends TestCase
{
    protected Stub $moduleConfigurationStub;
    protected Migrator $migrator;
    protected Stub $factoryStub;
    protected Connection $connection;
    protected State\Saml2 $state;
    protected Event $authenticationEvent;
    protected HashDecoratedState $hashDecoratedState;
    /**
     * @var MockObject
     */
    protected $repositoryMock;
    /**
     * @var Stub
     */
    protected $resultStub;
    /**
     * @var MockObject
     */
    protected $loggerMock;
    /**
     * @var MockObject
     */
    protected $helpersManagerMock;

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
        $this->moduleConfigurationStub->method('getSerializerClass')->willReturn(PhpSerializer::class);

        $this->connection = new Connection($connectionParams);

        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->migrator = new Migrator($this->connection, $this->loggerMock);

        $this->factoryStub = $this->createStub(Factory::class);
        $this->factoryStub->method('buildConnection')->willReturn($this->connection);
        $this->factoryStub->method('buildMigrator')->willReturn($this->migrator);

        $this->state = new State\Saml2(StateArrays::SAML2_FULL);
        $this->authenticationEvent = new Event($this->state);

        $this->hashDecoratedState = new HashDecoratedState($this->state);
        $this->repositoryMock = $this->createMock(
            Store\Repository::class
        );

        $this->resultStub = $this->createStub(Result::class);
        $this->helpersManagerMock = $this->createMock(HelpersManager::class);
    }

    /**
     * @throws StoreException
     */
    public function testCanConstructInstance(): void
    {
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
                BaseTableConstants::TABLE_PREFIX . BaseTableConstants::TABLE_NAME_IDP
            )
        );
        $idpVersionCountQueryBuilder->select('COUNT(id) as idpVersionCount')->from(
            //'vds_idp_version'
            $this->connection->preparePrefixedTableName(
                BaseTableConstants::TABLE_PREFIX . BaseTableConstants::TABLE_NAME_IDP_VERSION
            )
        );
        $spCountQueryBuilder->select('COUNT(id) as spCount')->from(
            //'vds_sp'
            $this->connection->preparePrefixedTableName(
                BaseTableConstants::TABLE_PREFIX . BaseTableConstants::TABLE_NAME_SP
            )
        );
        $spVersionCountQueryBuilder->select('COUNT(id) as spVersionCount')->from(
            //'vds_sp_version'
            $this->connection->preparePrefixedTableName(
                BaseTableConstants::TABLE_PREFIX . BaseTableConstants::TABLE_NAME_SP_VERSION
            )
        );
        $userCountQueryBuilder->select('COUNT(id) as userCount')->from(
            //'vds_user'
            $this->connection->preparePrefixedTableName(
                BaseTableConstants::TABLE_PREFIX . BaseTableConstants::TABLE_NAME_USER
            )
        );
        $userVersionCountQueryBuilder->select('COUNT(id) as userVersionCount')->from(
            //'vds_user_version'
            $this->connection->preparePrefixedTableName(
                BaseTableConstants::TABLE_PREFIX . BaseTableConstants::TABLE_NAME_USER_VERSION
            )
        );
        $idpSpUserVersionCountQueryBuilder->select('COUNT(id) as idpSpUserVersionCount')
            ->from(
                //'vds_idp_sp_user_version'
                $this->connection->preparePrefixedTableName(
                    BaseTableConstants::TABLE_PREFIX . BaseTableConstants::TABLE_NAME_IDP_SP_USER_VERSION
                )
            );
        $authenticationEventCountQueryBuilder->select('COUNT(id) as authenticationEventCount')
            ->from(
                //'vds_authentication_event'
                $this->connection->preparePrefixedTableName(
                    BaseTableConstants::TABLE_PREFIX . TableConstants::TABLE_NAME_AUTHENTICATION_EVENT
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
    public function testResolveIdpSpUserVersionIdThrowsOnFirstGetIdpSpUserVersionFailure(): void
    {
        $this->repositoryMock->method('getIdpSpUserVersion')->willThrowException(new Exception('test'));
        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->helpersManagerMock,
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

        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->helpersManagerMock,
            $this->repositoryMock
        );

        $this->expectException(StoreException::class);
        $this->loggerMock->expects($this->once())->method('warning');

        $store->persist($this->authenticationEvent);
    }

    /**
     * @throws StoreException
     */
    public function testGetActivityReturnsEmptyBagIfNoResults(): void
    {
        $this->repositoryMock->method('getActivity')->willReturn([]);

        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->helpersManagerMock,
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

        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->helpersManagerMock,
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
        unset($rawResult[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT]);

        $this->repositoryMock->method('getActivity')
            ->willReturn([$rawResult]);

        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->helpersManagerMock,
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

        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->helpersManagerMock,
            $this->repositoryMock
        );

        $store->deleteDataOlderThan($dateTime);
    }
}
