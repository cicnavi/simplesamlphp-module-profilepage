<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store;

use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store\Repository;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\accounting\Constants\DateTime;
// phpcs:ignore
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\TableConstants as BaseTableConstants;
// phpcs:ignore
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants as VersionedBaseTableConstants;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\EntityTableConstants;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateSpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Current\Store\Migrations\Version20240505400CreateConnectedServiceTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Helpers\Filesystem
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 *
 */
class RepositoryTest extends TestCase
{
    protected Connection $connection;
    protected \Doctrine\DBAL\Connection $dbal;
    protected Stub $loggerStub;
    protected Migrator $migrator;
    protected string $dateTimeFormat;
    protected string $idpEntityId;
    protected string $idpEntityIdHash;
    protected string $idpMetadata;
    protected string $idpMetadataHash;
    protected string $spEntityId;
    protected string $spMetadataHash;
    protected string $userIdentifier;
    protected string $userIdentifierHash;
    protected string $userAttributes;
    protected string $userAttributesHash;
    protected Repository $repository;
    protected DateTimeImmutable $createdAt;
    protected Stub $connectionStub;
    protected string $spEntityIdHash;
    protected string $spMetadata;

    /**
     * @throws StoreException
     * @throws MigrationException
     */
    protected function setUp(): void
    {
        // For stubbing.
        $this->connectionStub = $this->createStub(Connection::class);
        $this->loggerStub = $this->createStub(LoggerInterface::class);

        // For real DB testing.
        $connectionParameters = ConnectionParameters::DBAL_SQLITE_MEMORY;
        $this->connection = new Connection($connectionParameters);
        $this->migrator = new Migrator($this->connection, $this->loggerStub);
        $moduleConfiguration = new ModuleConfiguration();
        $migrationsDirectory = $moduleConfiguration->getModuleSourceDirectory() . DIRECTORY_SEPARATOR .
            'Data' . DIRECTORY_SEPARATOR .
            'Stores' . DIRECTORY_SEPARATOR .
            'Accounting' . DIRECTORY_SEPARATOR .
            'ConnectedServices' . DIRECTORY_SEPARATOR .
            'DoctrineDbal' . DIRECTORY_SEPARATOR .
            'Current' . DIRECTORY_SEPARATOR .
            'Store' . DIRECTORY_SEPARATOR .
            AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
        $namespace = Store::class . '\\' . AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;

        $this->migrator->runSetup();
        $this->migrator->runNonImplementedMigrationClasses($migrationsDirectory, $namespace);

        $this->repository = new Repository($this->connection, $this->loggerStub);

        $this->dateTimeFormat = DateTime::DEFAULT_FORMAT;

        $this->idpEntityId = 'idp-entity-id';
        $this->idpEntityIdHash = 'idp-entity-id-hash';

        $this->idpMetadata = 'idp-metadata';
        $this->idpMetadataHash = 'idp-metadata-hash';

        $this->spEntityId = 'sp-entity-id';
        $this->spEntityIdHash = 'sp-entity-id-hash';

        $this->spMetadata = 'sp-metadata';
        $this->spMetadataHash = 'sp-metadata-hash';

        $this->userIdentifier = 'user-identifier';
        $this->userIdentifierHash = 'user-identifier-hash';

        $this->userAttributes = 'user-attributes';
        $this->userAttributesHash = 'user-attributes-hash';

        $this->createdAt = new DateTimeImmutable();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            Repository::class,
            new Repository($this->connection, $this->loggerStub)
        );
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanGetConnectedServices(): void
    {
        $this->repository->insertSp(
            $this->spEntityId,
            $this->spEntityIdHash,
            $this->spMetadata,
            $this->spMetadataHash,
            $this->createdAt
        );
        $spResult = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();
        $spId = (int)$spResult[BaseTableConstants::TABLE_SP_COLUMN_NAME_ID];

        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);
        $userResult = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();
        $userId = (int)$userResult[VersionedBaseTableConstants::TABLE_USER_COLUMN_NAME_ID];
        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);
        $userVersionResult = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();
        $userVersionId = (int)$userVersionResult[VersionedBaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $resultArray = $this->repository->getConnectedServices($this->userIdentifierHash);
        $this->assertCount(0, $resultArray);

        $this->repository->insertConnectedService($spId, $userId, $userVersionId);

        $resultArray = $this->repository->getConnectedServices($this->userIdentifierHash);
        $this->assertCount(1, $resultArray);

        $this->assertEquals(
            '1',
            $resultArray[0]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );
        $this->assertSame(
            $this->spMetadata,
            $resultArray[0]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA]
        );
        $this->assertSame(
            $this->userAttributes,
            $resultArray[0]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES]
        );

        $connectedServiceId = (int)$this->repository->getConnectedService($spId, $userId)->fetchOne();

        $this->repository->updateConnectedServiceVersionCount(
            $connectedServiceId,
            $userVersionId,
            new DateTimeImmutable()
        );

        $resultArray = $this->repository->getConnectedServices($this->userIdentifierHash);
        $this->assertCount(1, $resultArray);
        $this->assertEquals(
            '2',
            $resultArray[0]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );
        $this->assertSame(
            $this->spMetadata,
            $resultArray[0]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA]
        );
        $this->assertSame(
            $this->userAttributes,
            $resultArray[0]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES]
        );

        // Simulate another SP
        $spEntityIdNew = $this->spEntityId . '-new';
        $spEntityIdHashNew = $this->spEntityIdHash . '-new';
        $spMetadataNew = $this->spMetadata . '-new';
        $spMetadataHashNew = $this->spMetadataHash . '-new';
        $this->repository->insertSp(
            $spEntityIdNew,
            $spEntityIdHashNew,
            $spMetadataNew,
            $spMetadataHashNew,
            $this->createdAt
        );
        $spResult = $this->repository->getSp($spEntityIdHashNew)->fetchAssociative();
        $spId = (int)$spResult[BaseTableConstants::TABLE_SP_COLUMN_NAME_ID];

        $this->repository->insertConnectedService($spId, $userId, $userVersionId);

        $resultArray = $this->repository->getConnectedServices($this->userIdentifierHash);
        $this->assertCount(2, $resultArray);
        $this->assertEquals(
            '1',
            $resultArray[1]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );
        $this->assertSame(
            $spMetadataNew,
            $resultArray[1]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA]
        );
        $this->assertSame(
            $this->userAttributes,
            $resultArray[0]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES]
        );

        // Simulate change in user attributes
        $userAttributesNew = $this->userAttributes . '-new';
        $userAttributesHashNew = $this->userAttributesHash . '-new';
        $this->repository->insertUserVersion($userId, $userAttributesNew, $userAttributesHashNew, $this->createdAt);
        $userVersionResult = $this->repository->getUserVersion($userId, $userAttributesHashNew)->fetchAssociative();
        $userVersionId = (int)$userVersionResult[VersionedBaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $connectedServiceId = (int)$this->repository->getConnectedService($spId, $userId)->fetchOne();

        $this->repository->updateConnectedServiceVersionCount(
            $connectedServiceId,
            $userVersionId,
            new DateTimeImmutable()
        );

        $resultArray = $this->repository->getConnectedServices($this->userIdentifierHash);

        $this->assertCount(2, $resultArray);
        $this->assertEquals(
            '2',
            $resultArray[1]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );
        $this->assertSame(
            $spMetadataNew,
            $resultArray[1]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA]
        );
        // New SP with new user attributes version...
        $this->assertSame(
            $userAttributesNew,
            $resultArray[1]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES]
        );

        // First SP still has old user attributes version...
        $this->assertSame(
            $this->userAttributes,
            $resultArray[0]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES]
        );
    }

    public function testInsertConnectedServiceThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->insertConnectedService(1, 1, 1);
    }

    public function testGetConnectedServiceThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getConnectedService(1, 1);
    }

    public function testGetConnectedServicesThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getConnectedServices($this->userIdentifierHash);
    }

    public function testUpdateConnectedServiceVersionCountThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->updateConnectedServiceVersionCount(1, 1, new DateTimeImmutable());
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanDeleteConnectedServicesOlderThan(): void
    {
        $this->repository->insertSp(
            $this->spEntityId,
            $this->spEntityIdHash,
            $this->spMetadata,
            $this->spMetadataHash,
            $this->createdAt
        );
        $spResult = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();
        $spId = (int)$spResult[BaseTableConstants::TABLE_SP_COLUMN_NAME_ID];

        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);
        $userResult = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();
        $userId = (int)$userResult[VersionedBaseTableConstants::TABLE_USER_COLUMN_NAME_ID];
        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);
        $userVersionResult = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();
        $userVersionId = (int)$userVersionResult[VersionedBaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertConnectedService($spId, $userId, $userVersionId);

        $resultArray = $this->repository->getConnectedServices($this->userIdentifierHash);
        $this->assertCount(1, $resultArray);

        $dateTimeInFuture = $this->createdAt->add(new DateInterval('P1D'));

        $this->repository->deleteConnectedServicesOlderThan($dateTimeInFuture);

        $resultArray = $this->repository->getConnectedServices($this->userIdentifierHash);
        $this->assertCount(0, $resultArray);
    }

    public function testDeleteAuthenticationEventsOlderThanThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->deleteConnectedServicesOlderThan(new DateTimeImmutable());
    }
}
