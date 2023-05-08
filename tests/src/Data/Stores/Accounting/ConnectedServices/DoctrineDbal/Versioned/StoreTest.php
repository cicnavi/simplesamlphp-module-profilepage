<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned;

use DateTimeImmutable;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\HashDecoratedState;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Repository;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\TableConstants;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\Event\State;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\accounting\Constants\RawRowResult;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000000CreateIdpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000100CreateIdpVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000200CreateSpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000300CreateSpVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000400CreateUserTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000500CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000700CreateConnectedServiceTable
 * @uses \SimpleSAML\Module\accounting\Helpers\Filesystem
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\HashDecoratedState
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateSpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateSpVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpSpUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Helpers\Hash
 * @uses \SimpleSAML\Module\accounting\Helpers\Arr
 * @uses \SimpleSAML\Module\accounting\Helpers\Network
 * @uses \SimpleSAML\Module\accounting\Entities\ConnectedService\Bag
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider
 * @uses \SimpleSAML\Module\accounting\Entities\ConnectedService
 * @uses \SimpleSAML\Module\accounting\Entities\User
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\RawConnectedService
 * @uses \SimpleSAML\Module\accounting\Entities\Activity\Bag
 * @uses \SimpleSAML\Module\accounting\Entities\Activity
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 * @uses \SimpleSAML\Module\accounting\Entities\Providers\Service\Saml2
 * @uses \SimpleSAML\Module\accounting\Helpers\ProviderResolver
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Saml2
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
            Repository::class
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
        unset($rawResult[TableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]);

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
