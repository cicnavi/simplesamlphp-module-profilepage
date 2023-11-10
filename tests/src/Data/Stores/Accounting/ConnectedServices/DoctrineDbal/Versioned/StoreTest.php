<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned;

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
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
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Services\Serializers\PhpSerializer;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\accounting\Constants\RawRowResult;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;
// phpcs:ignore
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants as BaseTableConstants;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\EntityTableConstants;

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
 * @uses \SimpleSAML\Module\accounting\Factories\SerializerFactory
 * @uses \SimpleSAML\Module\accounting\Services\Serializers\PhpSerializer
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

        $idpCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $idpVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $spCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $spVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $userCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $userVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $idpSpUserVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $connectedServiceCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();

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
        $connectedServiceCountQueryBuilder->select('COUNT(id) as connectedServiceCount')
            ->from(
            //'vds_connected_service'
                $this->connection->preparePrefixedTableName(
                    BaseTableConstants::TABLE_PREFIX . TableConstants::TABLE_NAME_CONNECTED_SERVICE
                )
            );

        $this->assertSame(0, (int)$idpCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$idpVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$spVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$userCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$userVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$idpSpUserVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$connectedServiceCountQueryBuilder->executeQuery()->fetchOne());

        $store->persist($this->authenticationEvent);

        $this->assertSame(1, (int)$idpCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$idpVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$spVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$idpSpUserVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$connectedServiceCountQueryBuilder->executeQuery()->fetchOne());

        $store->persist($this->authenticationEvent);

        $this->assertSame(1, (int)$idpCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$idpVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$spVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$idpSpUserVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$connectedServiceCountQueryBuilder->executeQuery()->fetchOne());
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
