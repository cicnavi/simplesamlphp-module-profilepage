<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current;

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\HashDecoratedState;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store\Repository;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store\TableConstants;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Module\profilepage\Services\Serializers\PhpSerializer;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\profilepage\Constants\RawRowResult;
use SimpleSAML\Test\Module\profilepage\Constants\StateArrays;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event\State;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\TableConstants as BaseTableConstants;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants as VersionedBaseTableConstants;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\EntityTableConstants;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\HashDecoratedState
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\profilepage\Helpers\Arr
 * @uses \SimpleSAML\Module\profilepage\Helpers\Hash
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 * @uses \SimpleSAML\Module\profilepage\Helpers\Network
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\profilepage\Entities\ConnectedService\Bag
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\RawConnectedService
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity
 * @uses \SimpleSAML\Module\profilepage\Entities\ConnectedService
 * @uses \SimpleSAML\Module\profilepage\Entities\User
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateSpTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store\Migrations\Version20240505400CreateConnectedServiceTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\profilepage\Helpers\Filesystem
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
        $this->repositoryMock = $this->createMock(Repository::class);

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
     * @throws Exception
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

        $spCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $userCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $userVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $connectedServicesQueryBuilder = $this->connection->dbal()->createQueryBuilder();

        $spCountQueryBuilder->select('COUNT(id) as spCount')->from(
        //'cds_sp'
            $this->connection->preparePrefixedTableName(
                BaseTableConstants::TABLE_PREFIX . BaseTableConstants::TABLE_NAME_SP
            )
        );

        $userCountQueryBuilder->select('COUNT(id) as userCount')->from(
        //'vds_user'
            $this->connection->preparePrefixedTableName(
                VersionedBaseTableConstants::TABLE_PREFIX . VersionedBaseTableConstants::TABLE_NAME_USER
            )
        );
        $userVersionCountQueryBuilder->select('COUNT(id) as userVersionCount')->from(
        //'vds_user_version'
            $this->connection->preparePrefixedTableName(
                VersionedBaseTableConstants::TABLE_PREFIX . VersionedBaseTableConstants::TABLE_NAME_USER_VERSION
            )
        );

        $connectedServicesQueryBuilder->select('COUNT(id) as connectedServiceCount')
            ->from(
            //'cds_connected_service'
                $this->connection->preparePrefixedTableName(
                    BaseTableConstants::TABLE_PREFIX . TableConstants::TABLE_NAME_CONNECTED_SERVICE
                )
            );

        $this->assertSame(0, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$userCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$userVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$connectedServicesQueryBuilder->executeQuery()->fetchOne());

        $store->persist($this->authenticationEvent);

        $this->assertSame(1, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$connectedServicesQueryBuilder->executeQuery()->fetchOne());

        $store->persist($this->authenticationEvent);

        $this->assertSame(1, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$connectedServicesQueryBuilder->executeQuery()->fetchOne());

        // New SP
        $stateArray = StateArrays::SAML2_FULL;
        $stateArray['SPMetadata']['entityid'] = 'new-entity-id';
        $state = new State\Saml2($stateArray);
        $authenticationEvent = new Event($state);

        $store->persist($authenticationEvent);

        $this->assertSame(2, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(2, (int)$connectedServicesQueryBuilder->executeQuery()->fetchOne());
    }

    /**
     * @throws StoreException
     */
    public function testGetConnectedOrganizationsReturnsEmptyBagIfNoResults(): void
    {
        $this->repositoryMock->method('getConnectedServices')->willReturn([]);

        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->helpersManagerMock,
            $this->repositoryMock
        );

        $connectedServiceProviderBag = $store->getConnectedServices('test');

        $this->assertEmpty($connectedServiceProviderBag->getAll());
    }

    /**
     * @throws StoreException
     */
    public function testCanGetConnectedOrganizationsBag(): void
    {
        $this->repositoryMock->method('getConnectedServices')
            ->willReturn([RawRowResult::CONNECTED_SERVICE]);

        $store = new Store(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->helpersManagerMock,
            $this->repositoryMock
        );

        $connectedServiceProviderBag = $store->getConnectedServices('test');

        $this->assertNotEmpty($connectedServiceProviderBag->getAll());
    }

    /**
     * @throws StoreException
     */
    public function testGetConnectedOrganizationsThrowsForInvalidResult(): void
    {
        $rawResult = RawRowResult::CONNECTED_SERVICE;
        unset($rawResult[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]);

        $this->repositoryMock->method('getConnectedServices')
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
        $store->getConnectedServices('test');
    }

    /**
     * @throws StoreException
     */
    public function testCanDeleteDataOlderThan(): void
    {
        $dateTime = new DateTimeImmutable();

        $this->repositoryMock->expects($this->once())
            ->method('deleteConnectedServicesOlderThan')
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
