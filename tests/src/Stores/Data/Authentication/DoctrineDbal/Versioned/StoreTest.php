<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;
use PHPUnit\Framework\TestCase;
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
 */
class StoreTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $moduleConfigurationStub;
    protected \PHPUnit\Framework\MockObject\Stub $loggerStub;
    protected Migrator $migrator;
    protected \PHPUnit\Framework\MockObject\Stub $factoryStub;
    protected Connection $connection;
    protected State $state;
    protected Event $authenticationEvent;

    public function testCanConstructInstance(): void
    {
        /** @psalm-suppress InvalidArgument */
        $this->assertInstanceOf(
            Store::class,
            new Store($this->moduleConfigurationStub, $this->loggerStub, $this->factoryStub)
        );
    }

    public function testCanBuildInstance(): void
    {
        /** @psalm-suppress InvalidArgument */
        $this->assertInstanceOf(
            Store::class,
            Store::build($this->moduleConfigurationStub, $this->loggerStub)
        );
    }

    public function testCanPersistAuthenticationEvent(): void
    {
        /** @psalm-suppress InvalidArgument */
        $store = new Store($this->moduleConfigurationStub, $this->loggerStub, $this->factoryStub);
        $store->runSetup();

        $idpCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $idpVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $spCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $spVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $userCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $userVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $idpSpUserVersionCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $authenticationEventCountQueryBuilder = $this->connection->dbal()->createQueryBuilder();

        $idpCountQueryBuilder->select('COUNT(id) as idpCount')->from('vds_idp');
        $idpVersionCountQueryBuilder->select('COUNT(id) as idpVersionCount')->from('vds_idp_version');
        $spCountQueryBuilder->select('COUNT(id) as spCount')->from('vds_sp');
        $spVersionCountQueryBuilder->select('COUNT(id) as spVersionCount')->from('vds_sp_version');
        $userCountQueryBuilder->select('COUNT(id) as userCount')->from('vds_user');
        $userVersionCountQueryBuilder->select('COUNT(id) as userVersionCount')->from('vds_user_version');
        $idpSpUserVersionCountQueryBuilder->select('COUNT(id) as idpSpUserVersionCount')
            ->from('vds_idp_sp_user_version');
        $authenticationEventCountQueryBuilder->select('COUNT(id) as authenticationEventCount')
            ->from('vds_authentication_event');

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

    protected function setUp(): void
    {
        $connectionParams = ['driver' => 'pdo_sqlite', 'memory' => true,];
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn($connectionParams);
        $this->moduleConfigurationStub->method('getUserIdAttributeName')
            ->willReturn('hrEduPersonPersistentID');

        $this->connection = new Connection($connectionParams);

        $this->loggerStub = $this->createStub(LoggerInterface::class);

        /** @psalm-suppress InvalidArgument */
        $this->migrator = new Migrator($this->connection, $this->loggerStub);

        $this->factoryStub = $this->createStub(Factory::class);
        $this->factoryStub->method('buildConnection')->willReturn($this->connection);
        $this->factoryStub->method('buildMigrator')->willReturn($this->migrator);

        $this->state = new State(StateArrays::FULL);
        $this->authenticationEvent = new Event($this->state);
    }
}
