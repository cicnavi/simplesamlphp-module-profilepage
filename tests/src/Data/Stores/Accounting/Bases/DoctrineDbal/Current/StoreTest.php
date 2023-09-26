<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\TableConstants;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\HashDecoratedState;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store\Repository;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;
use SimpleSAML\Module\accounting\Entities\Authentication\Event\State;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\HashDecoratedState
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\accounting\Helpers\Arr
 * @uses \SimpleSAML\Module\accounting\Helpers\Hash
 * @uses \SimpleSAML\Module\accounting\Helpers\Network
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 * @uses \SimpleSAML\Module\accounting\Traits\HasUserAttributesTrait
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateIdpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateSpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Helpers\Filesystem
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
    protected MockObject $repositoryMock;
    protected Stub $resultStub;
    protected MockObject $loggerMock;
    protected MockObject $helpersManagerMock;

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
    public function testCanResolveSpId(): void
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

        $spCountQueryBuilder->select('COUNT(id) as spCount')->from(
            $this->connection->preparePrefixedTableName(
                TableConstants::TABLE_PREFIX . TableConstants::TABLE_NAME_SP
            )
        );

        $this->assertSame(0, (int)$spCountQueryBuilder->executeQuery()->fetchOne());

        $spId = $store->resolveSpId($this->hashDecoratedState);
        $this->assertSame(1, (int)$spCountQueryBuilder->executeQuery()->fetchOne());

        $newSpId = $store->resolveSpId($this->hashDecoratedState);
        $this->assertSame(1, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame($spId, $newSpId);

        // Test updated metadata
        $updatedStateArray = StateArrays::SAML2_FULL;
        $updatedStateArray['SPMetadata']['name'] = 'Updated name';
        $updatedState = new State\Saml2($updatedStateArray);
        $updatedHasDecoratedState = new HashDecoratedState($updatedState);
        $newSpId = $store->resolveSpId($updatedHasDecoratedState);
        $this->assertSame(1, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertSame($spId, $newSpId);

        // Test new SP
        $newStateArray = StateArrays::SAML2_FULL;
        $newStateArray['SPMetadata']['entityid'] = 'new-entity-id';
        $newState = new State\Saml2($newStateArray);
        $newHasDecoratedState = new HashDecoratedState($newState);
        $newSpId = $store->resolveSpId($newHasDecoratedState);
        $this->assertSame(2, (int)$spCountQueryBuilder->executeQuery()->fetchOne());
        $this->assertNotSame($spId, $newSpId);
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
    public function testResolveSpIdThrowsOnInsertAndLogsWarning(): void
    {
        $this->resultStub->method('fetchAssociative')->willReturn(false);
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
}
