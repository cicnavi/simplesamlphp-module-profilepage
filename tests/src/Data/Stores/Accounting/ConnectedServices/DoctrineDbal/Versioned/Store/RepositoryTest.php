<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store;

use DateInterval;
use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
// phpcs:ignore
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants as BaseTableConstants;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Repository;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\TableConstants;
use SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\accounting\Constants\DateTime;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\EntityTableConstants;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Helpers\Filesystem
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000000CreateIdpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000100CreateIdpVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000200CreateSpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000300CreateSpVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000400CreateUserTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000500CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\Versioned\Store\Migrations\Version20220801000700CreateConnectedServiceTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateSpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateSpVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpSpUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 */
class RepositoryTest extends TestCase
{
    protected Connection $connection;
    protected \Doctrine\DBAL\Connection $dbal;
    /**
     * @var Stub
     */
    protected $loggerStub;
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
    /**
     * @var Stub
     */
    protected $connectionStub;
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
            'Versioned' . DIRECTORY_SEPARATOR .
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
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);
        $idpResult = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();
        $idpId = (int)$idpResult[BaseTableConstants::TABLE_IDP_COLUMN_NAME_ID];
        $this->repository->insertIdpVersion($idpId, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);

        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);
        $spResult = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();
        $spId = (int)$spResult[BaseTableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $this->spMetadata, $this->spMetadataHash, $this->createdAt);

        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);
        $userResult = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();
        $userId = (int)$userResult[BaseTableConstants::TABLE_USER_COLUMN_NAME_ID];
        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);

        $idpVersionResult = $this->repository->getIdpVersion($idpId, $this->idpMetadataHash)->fetchAssociative();
        $spVersionResult = $this->repository->getSpVersion($spId, $this->spMetadataHash)->fetchAssociative();
        $userVersionResult = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();

        $idpVersionId = (int)$idpVersionResult[BaseTableConstants::TABLE_IDP_VERSION_COLUMN_NAME_ID];
        $spVersionId = (int)$spVersionResult[BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];
        $userVersionId = (int)$userVersionResult[BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        $idpSpUserVersionId =
            (int)$idpSpUserVersionResult[BaseTableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $resultArray = $this->repository->getConnectedServices($this->userIdentifierHash);

        $this->assertCount(0, $resultArray);

        $this->repository->insertConnectedService($idpSpUserVersionId);

        $resultArray = $this->repository->getConnectedServices($this->userIdentifierHash);
        $this->assertCount(1, $resultArray);

        $this->assertEquals(
            '1',
            $resultArray[$this->spEntityId]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );
        $this->assertSame(
            $this->spMetadata,
            $resultArray[$this->spEntityId]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA]
        );
        $this->assertSame(
            $this->userAttributes,
            $resultArray[$this->spEntityId]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES]
        );

        $connectedServiceId = (int)$this->repository->getConnectedService($idpSpUserVersionId)->fetchOne();

        $this->repository->updateConnectedServiceVersionCount(
            $connectedServiceId,
            new DateTimeImmutable()
        );

        $resultArray = $this->repository->getConnectedServices($this->userIdentifierHash);
        $this->assertCount(1, $resultArray);
        $this->assertEquals(
            '2',
            $resultArray[$this->spEntityId]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );
        $this->assertSame(
            $this->spMetadata,
            $resultArray[$this->spEntityId]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA]
        );
        $this->assertSame(
            $this->userAttributes,
            $resultArray[$this->spEntityId]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES]
        );

        // Simulate another SP
        $spEntityIdNew = $this->spEntityId . '-new';
        $spEntityIdHashNew = $this->spEntityIdHash . '-new';
        $spMetadataNew = $this->spMetadata . '-new';
        $spMetadataHashNew = $this->spMetadataHash . '-new';
        $this->repository->insertSp($spEntityIdNew, $spEntityIdHashNew, $this->createdAt);
        $spResult = $this->repository->getSp($spEntityIdHashNew)->fetchAssociative();
        $spId = (int)$spResult[BaseTableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $spMetadataNew, $spMetadataHashNew, $this->createdAt);
        $spVersionResult = $this->repository->getSpVersion($spId, $spMetadataHashNew)->fetchAssociative();
        $spVersionId = (int)$spVersionResult[BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        $idpSpUserVersionId =
            (int)$idpSpUserVersionResult[BaseTableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertConnectedService($idpSpUserVersionId);

        $resultArray = $this->repository->getConnectedServices($this->userIdentifierHash);
        $this->assertCount(2, $resultArray);
        $this->assertEquals(
            '1',
            $resultArray[$spEntityIdNew]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );
        $this->assertSame(
            $spMetadataNew,
            $resultArray[$spEntityIdNew]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA]
        );
        $this->assertSame(
            $this->userAttributes,
            $resultArray[$this->spEntityId]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES]
        );

        // Simulate change in user attributes
        $userAttributesNew = $this->userAttributes . '-new';
        $userAttributesHashNew = $this->userAttributesHash . '-new';
        $this->repository->insertUserVersion($userId, $userAttributesNew, $userAttributesHashNew, $this->createdAt);
        $userVersionResult = $this->repository->getUserVersion($userId, $userAttributesHashNew)->fetchAssociative();
        $userVersionId = (int)$userVersionResult[BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];
        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();
        $idpSpUserVersionId =
            (int)$idpSpUserVersionResult[BaseTableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertConnectedService($idpSpUserVersionId);

        $resultArray = $this->repository->getConnectedServices($this->userIdentifierHash);

        $this->assertCount(2, $resultArray);
        $this->assertEquals(
            '2',
            $resultArray[$spEntityIdNew]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );
        $this->assertSame(
            $spMetadataNew,
            $resultArray[$spEntityIdNew]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA]
        );
        // New SP with new user attributes version...
        $this->assertSame(
            $userAttributesNew,
            $resultArray[$spEntityIdNew]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES]
        );

        // First SP still has old user attributes version...
        $this->assertSame(
            $this->userAttributes,
            $resultArray[$this->spEntityId]
            [EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES]
        );
    }

    public function testGetConnectedServicesThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getConnectedServices($this->userIdentifierHash);
    }

    public function testGetConnectedServiceThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getConnectedService(1);
    }

    public function testInsertConnectedServiceThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->insertConnectedService(1);
    }

    public function testUpdatetConnectedServiceCountThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->updateConnectedServiceVersionCount(1, new DateTimeImmutable());
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanDeleteConnectedServicesOlderThan(): void
    {
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);
        $idpResult = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();
        $idpId = (int)$idpResult[BaseTableConstants::TABLE_IDP_COLUMN_NAME_ID];
        $this->repository->insertIdpVersion($idpId, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);

        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);
        $spResult = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();
        $spId = (int)$spResult[BaseTableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $this->spMetadata, $this->spMetadataHash, $this->createdAt);

        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);
        $userResult = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();
        $userId = (int)$userResult[BaseTableConstants::TABLE_USER_COLUMN_NAME_ID];
        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);

        $idpVersionResult = $this->repository->getIdpVersion($idpId, $this->idpMetadataHash)->fetchAssociative();
        $spVersionResult = $this->repository->getSpVersion($spId, $this->spMetadataHash)->fetchAssociative();
        $userVersionResult = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();

        $idpVersionId = (int)$idpVersionResult[BaseTableConstants::TABLE_IDP_VERSION_COLUMN_NAME_ID];
        $spVersionId = (int)$spVersionResult[BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];
        $userVersionId = (int)$userVersionResult[BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        $idpSpUserVersionId =
            (int)$idpSpUserVersionResult[BaseTableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertConnectedService($idpSpUserVersionId);

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

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanTouchConnectedServiceVersionsTimestamp(): void
    {
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);
        $idpResult = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();
        $idpId = (int)$idpResult[BaseTableConstants::TABLE_IDP_COLUMN_NAME_ID];
        $this->repository->insertIdpVersion($idpId, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);

        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);
        $spResult = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();
        $spId = (int)$spResult[BaseTableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $this->spMetadata, $this->spMetadataHash, $this->createdAt);

        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);
        $userResult = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();
        $userId = (int)$userResult[BaseTableConstants::TABLE_USER_COLUMN_NAME_ID];
        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);

        $idpVersionResult = $this->repository->getIdpVersion($idpId, $this->idpMetadataHash)->fetchAssociative();
        $spVersionResult = $this->repository->getSpVersion($spId, $this->spMetadataHash)->fetchAssociative();
        $userVersionResult = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();

        $idpVersionId = (int)$idpVersionResult[BaseTableConstants::TABLE_IDP_VERSION_COLUMN_NAME_ID];
        $spVersionId = (int)$spVersionResult[BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];
        $userVersionId = (int)$userVersionResult[BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        $idpSpUserVersionId =
            (int)$idpSpUserVersionResult[BaseTableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $authenticationAt = new DateTimeImmutable();

        $this->repository->insertConnectedService($idpSpUserVersionId, $authenticationAt, $authenticationAt);

        $resultArray = $this->repository->getConnectedService($idpSpUserVersionId)->fetchAssociative();
        $this->assertSame(
            $authenticationAt->getTimestamp(),
            $resultArray[TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT]
        );

        $newAuthenticationAt = $authenticationAt->add(new DateInterval('P1D'));

        $this->repository->touchConnectedServiceVersionsTimestamp($userId, $spId, $newAuthenticationAt);

        $resultArray = $this->repository->getConnectedService($idpSpUserVersionId)->fetchAssociative();

        $this->assertNotSame(
            $authenticationAt->getTimestamp(),
            $resultArray[TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT]
        );
        $this->assertSame(
            $newAuthenticationAt->getTimestamp(),
            $resultArray[TableConstants::TABLE_CONNECTED_SERVICE_COLUMN_NAME_UPDATED_AT]
        );
    }

    public function testTouchConnectedServiceVersionsTimestampThrowsOnInvalidDbalForSelect(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->touchConnectedServiceVersionsTimestamp(1, 1);
    }
}
