<?php

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Current\Store;

use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Current\Store\Repository;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Current\Store;
use SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Saml2;
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

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Current\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\Current\Store\Migrations\Version20220801000700CreateAuthenticationEventTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateSpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Helpers\Filesystem
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 */
class RepositoryTest extends TestCase
{
    protected Connection $connection;
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
    protected string $clientIpAddress;
    protected string $authenticationProtocolDesignation;

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
            'Activity' . DIRECTORY_SEPARATOR .
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
        $this->clientIpAddress = '123.123.123.123';
        $this->authenticationProtocolDesignation = Saml2::DESIGNATION;
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            Repository::class,
            new Repository($this->connection, $this->loggerStub)
        );
    }

    public function testInsertAuthenticationEventThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->insertAuthenticationEvent(1, 1, $this->createdAt);
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanGetActivity(): void
    {
        $this->repository->insertSp(
            $this->spEntityId,
            $this->spEntityIdHash,
            $this->spMetadata,
            $this->spEntityIdHash,
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


        $this->repository->insertAuthenticationEvent(
            $spId,
            $userVersionId,
            $this->createdAt,
            $this->clientIpAddress,
            $this->authenticationProtocolDesignation,
            $this->createdAt
        );

        $resultArray = $this->repository->getActivity($this->userIdentifierHash, 10, 0);
        $this->assertCount(1, $resultArray);

        $this->repository->insertAuthenticationEvent(
            $spId,
            $userVersionId,
            $this->createdAt,
            $this->clientIpAddress,
            $this->authenticationProtocolDesignation,
            $this->createdAt
        );
        $resultArray = $this->repository->getActivity($this->userIdentifierHash, 10, 0);
        $this->assertCount(2, $resultArray);

        $this->repository->insertAuthenticationEvent(
            $spId,
            $userVersionId,
            $this->createdAt,
            $this->clientIpAddress,
            $this->authenticationProtocolDesignation,
            $this->createdAt
        );
        $resultArray = $this->repository->getActivity($this->userIdentifierHash, 10, 0);
        $this->assertCount(3, $resultArray);

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

        $this->repository->insertAuthenticationEvent(
            $spId,
            $userVersionId,
            $this->createdAt,
            $this->clientIpAddress,
            $this->authenticationProtocolDesignation,
            $this->createdAt
        );
        $resultArray = $this->repository->getActivity($this->userIdentifierHash, 10, 0);
        $this->assertCount(4, $resultArray);

        // Simulate a change in user attributes
    }

    public function testGetActivityThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getActivity($this->userIdentifierHash, 10, 0);
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanDeleteAuthenticationEventsOlderThan(): void
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

        $this->repository->insertAuthenticationEvent(
            $spId,
            $userVersionId,
            $this->createdAt,
            $this->clientIpAddress,
            $this->authenticationProtocolDesignation,
            $this->createdAt
        );

        $resultArray = $this->repository->getActivity($this->userIdentifierHash, 10, 0);
        $this->assertCount(1, $resultArray);

        $dateTimeInFuture = $this->createdAt->add(new DateInterval('P1D'));

        $this->repository->deleteAuthenticationEventsOlderThan($dateTimeInFuture);

        $resultArray = $this->repository->getActivity($this->userIdentifierHash, 10, 0);
        $this->assertCount(0, $resultArray);
    }

    public function testDeleteAuthenticationEventsOlderThanThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->deleteAuthenticationEventsOlderThan(new DateTimeImmutable());
    }
}
