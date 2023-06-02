<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\HashDecoratedState;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\Event\State;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateSpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateSpVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpSpUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\HashDecoratedState
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Helpers\Filesystem
 * @uses \SimpleSAML\Module\accounting\Helpers\Hash
 * @uses \SimpleSAML\Module\accounting\Helpers\Arr
 * @uses \SimpleSAML\Module\accounting\Helpers\Network
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider
 * @uses \SimpleSAML\Module\accounting\Entities\User
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\AbstractStore
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
     * @throws \Doctrine\DBAL\Exception
     * @throws MigrationException
     */
    public function testCanPersistVersionedData(): void
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
        $idpSpUserVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $userVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();

        $idpCountQueryBuilder->select('COUNT(id) as idpCount')->from(
            //'vds_idp'
            $this->connection->preparePrefixedTableName(
                TableConstants::TABLE_PREFIX . TableConstants::TABLE_NAME_IDP
            )
        );
        $idpVersionCountQueryBuilder->select('COUNT(id) as idpVersionCount')->from(
            //'vds_idp_version'
            $this->connection->preparePrefixedTableName(
                TableConstants::TABLE_PREFIX . TableConstants::TABLE_NAME_IDP_VERSION
            )
        );
        $spCountQueryBuilder->select('COUNT(id) as spCount')->from(
            //'vds_sp'
            $this->connection->preparePrefixedTableName(
                TableConstants::TABLE_PREFIX . TableConstants::TABLE_NAME_SP
            )
        );
        $spVersionCountQueryBuilder->select('COUNT(id) as spVersionCount')->from(
            //'vds_sp_version'
            $this->connection->preparePrefixedTableName(
                TableConstants::TABLE_PREFIX . TableConstants::TABLE_NAME_SP_VERSION
            )
        );
        $userCountQueryBuilder->select('COUNT(id) as userCount')->from(
            //'vds_user'
            $this->connection->preparePrefixedTableName(
                TableConstants::TABLE_PREFIX . TableConstants::TABLE_NAME_USER
            )
        );
        $userVersionCountQueryBuilder->select('COUNT(id) as userVersionCount')->from(
            //'vds_user_version'
            $this->connection->preparePrefixedTableName(
                TableConstants::TABLE_PREFIX . TableConstants::TABLE_NAME_USER_VERSION
            )
        );
        $idpSpUserVersionCountQueryBuilder->select('COUNT(id) as idpSpUserVersionCount')
            ->from(
            //'vds_idp_sp_user_version'
                $this->connection->preparePrefixedTableName(
                    TableConstants::TABLE_PREFIX . TableConstants::TABLE_NAME_IDP_SP_USER_VERSION
                )
            );

        $this->assertSame(0, (int)$idpCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$idpVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$spVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$userCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$idpSpUserVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(0, (int)$userVersionCountQueryBuilder->executeQuery()->fetchOne());

        $idpId = $store->resolveIdpId($this->hashDecoratedState);
        $idpVersionId = $store->resolveIdpVersionId($idpId, $this->hashDecoratedState);
        $spId = $store->resolveSpId($this->hashDecoratedState);
        $spVersionId = $store->resolveSpVersionId($spId, $this->hashDecoratedState);
        $userId = $store->resolveUserId($this->hashDecoratedState);
        $userVersionId = $store->resolveUserVersionId($userId, $this->hashDecoratedState);
        $store->resolveIdpSpUserVersionId($idpVersionId, $spVersionId, $userVersionId);

        $this->assertSame(1, (int)$idpCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$idpVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$spVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$idpSpUserVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userVersionCountQueryBuilder->executeQuery()->fetchOne());

        $idpId = $store->resolveIdpId($this->hashDecoratedState);
        $idpVersionId = $store->resolveIdpVersionId($idpId, $this->hashDecoratedState);
        $spId = $store->resolveSpId($this->hashDecoratedState);
        $spVersionId = $store->resolveSpVersionId($spId, $this->hashDecoratedState);
        $userId = $store->resolveUserId($this->hashDecoratedState);
        $userVersionId = $store->resolveUserVersionId($userId, $this->hashDecoratedState);
        $store->resolveIdpSpUserVersionId($idpVersionId, $spVersionId, $userVersionId);

        $this->assertSame(1, (int)$idpCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$idpVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$spVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$idpSpUserVersionCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame(1, (int)$userVersionCountQueryBuilder->executeQuery()->fetchOne());
    }

    /**
     * @throws StoreException
     */
    public function testResolveIdpIdThrowsOnFirstGetIdpFailure(): void
    {
        $this->repositoryMock->method('getIdp')->willThrowException(new Exception('test'));
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

        $store->resolveIdpId($this->hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    public function testResolveIdpIdThrowsOnInsertAndGetIdpFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getIdp')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertIdp')->willThrowException(new Exception('test'));

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

        $store->resolveIdpId($this->hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    public function testResolveIdpVersionIdThrowsOnFirstGetIdpVersionFailure(): void
    {
        $this->repositoryMock->method('getIdpVersion')->willThrowException(new Exception('test'));
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

        $idpId = $store->resolveIdpId($this->hashDecoratedState);
        $store->resolveIdpVersionId($idpId, $this->hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    public function testResolveIdpVersionIdThrowsOnInsertAndGetIdpVersionFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getIdpVersion')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertIdpVersion')->willThrowException(new Exception('test'));

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

        $idpId = $store->resolveIdpId($this->hashDecoratedState);
        $store->resolveIdpVersionId($idpId, $this->hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    public function testResolveSpIdThrowsOnFirstGetSpFailure(): void
    {
        $this->repositoryMock->method('getSp')->willThrowException(new Exception('test'));
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

        $store->resolveSpId($this->hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    public function testResolveSpIdThrowsOnInsertAndGetSpFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getSp')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertSp')->willThrowException(new Exception('test'));

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

        $store->resolveSpId($this->hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    public function testResolveSpVersionIdThrowsOnFirstGetSpVersionFailure(): void
    {
        $this->repositoryMock->method('getSpVersion')->willThrowException(new Exception('test'));
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

        $spId = $store->resolveSpId($this->hashDecoratedState);
        $store->resolveSpVersionId($spId, $this->hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    public function testResolveSpVersionIdThrowsOnInsertAndGetSpVersionFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getSpVersion')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertSpVersion')->willThrowException(new Exception('test'));

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

        $spId = $store->resolveSpId($this->hashDecoratedState);
        $store->resolveSpVersionId($spId, $this->hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    public function testResolveUserIdThrowsOnInvalidUserIdentifierValue(): void
    {
        $moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $moduleConfigurationStub->method('getUserIdAttributeName')->willReturn('invalid');

        $store = new Store(
            $moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->factoryStub,
            $this->helpersManagerMock,
            $this->repositoryMock
        );

        $this->expectException(UnexpectedValueException::class);

        $store->resolveUserId($this->hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    public function testResolveUserIdThrowsOnFirstGetUserFailure(): void
    {
        $this->repositoryMock->method('getUser')->willThrowException(new Exception('test'));
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

        $store->resolveUserId($this->hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    public function testResolveUserIdThrowsOnInsertAndGetUserFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getUser')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertUser')->willThrowException(new Exception('test'));

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

        $store->resolveUserId($this->hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    public function testResolveUserVersionIdThrowsOnFirstGetUserVersionFailure(): void
    {
        $this->repositoryMock->method('getUserVersion')->willThrowException(new Exception('test'));
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

        $userId = $store->resolveUserId($this->hashDecoratedState);
        $store->resolveUserVersionId($userId, $this->hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    public function testResolveUserVersionIdThrowsOnInsertAndGetUserVersionFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getUserVersion')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertUserVersion')->willThrowException(new Exception('test'));

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

        $userId = $store->resolveUserId($this->hashDecoratedState);
        $store->resolveUserVersionId($userId, $this->hashDecoratedState);
    }

    /**
     * @throws StoreException
     */
    public function testResolveIdpSpUserVersionThrowsOnGet(): void
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

        $store->resolveIdpSpUserVersionId(1, 1, 1);
    }



    /**
     * @throws StoreException
     */
    public function testResolveIdpSpUserVersionLogsWarningAndThrowsOnFailure(): void
    {
        $this->resultStub->method('fetchOne')->willReturn(false);
        $this->repositoryMock->method('getIdpSpUserVersion')->willReturn($this->resultStub);
        $this->repositoryMock->method('insertIdpSpUserVersion')
            ->willThrowException(new Exception('test'));
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
        $store->resolveIdpSpUserVersionId(1, 1, 1);
    }
}
