<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store;

use DateInterval;
use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Repository;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants
    as BaseTableConstants;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Saml2;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\profilepage\Constants\DateTime;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Helpers\Filesystem
 * @uses \SimpleSAML\Module\profilepage\ModuleConfiguration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000000CreateIdpTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000100CreateIdpVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000200CreateSpTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000300CreateSpVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000400CreateUserTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000500CreateUserVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000600CreateIdpSpUserVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Activity\DoctrineDbal\Versioned\Store\Migrations\Version20220801000700CreateAuthenticationEventTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateSpTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateSpVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpSpUserVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
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

        $repository->insertAuthenticationEvent(1, $this->createdAt);
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanGetActivity(): void
    {
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);
        $idpResult = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();
        $idpId = (int)$idpResult[BaseTableConstants::TABLE_IDP_COLUMN_NAME_ID];
        $this->repository->insertIdpVersion($idpId, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);
        $idpVersionResult = $this->repository->getIdpVersion($idpId, $this->idpMetadataHash)->fetchAssociative();

        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);
        $spResult = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();
        $spId = (int)$spResult[BaseTableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $this->spMetadata, $this->spMetadataHash, $this->createdAt);
        $spVersionResult = $this->repository->getSpVersion($spId, $this->spMetadataHash)->fetchAssociative();

        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);
        $userResult = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();
        $userId = (int)$userResult[BaseTableConstants::TABLE_USER_COLUMN_NAME_ID];
        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);
        $userVersionResult = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();

        $idpVersionId = (int)$idpVersionResult[BaseTableConstants::TABLE_IDP_VERSION_COLUMN_NAME_ID];
        $spVersionId = (int)$spVersionResult[BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];
        $userVersionId = (int)$userVersionResult[BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        $idpSpUserVersionId =
            (int)$idpSpUserVersionResult[BaseTableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertAuthenticationEvent(
            $idpSpUserVersionId,
            $this->createdAt,
            $this->clientIpAddress,
            $this->authenticationProtocolDesignation,
            $this->createdAt
        );

        $resultArray = $this->repository->getActivity($this->userIdentifierHash, 10, 0);
        $this->assertCount(1, $resultArray);

        $this->repository->insertAuthenticationEvent(
            $idpSpUserVersionId,
            $this->createdAt,
            $this->clientIpAddress,
            $this->authenticationProtocolDesignation,
            $this->createdAt
        );
        $resultArray = $this->repository->getActivity($this->userIdentifierHash, 10, 0);
        $this->assertCount(2, $resultArray);

        $this->repository->insertAuthenticationEvent(
            $idpSpUserVersionId,
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

        $this->repository->insertAuthenticationEvent(
            $idpSpUserVersionId,
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
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);
        $idpResult = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();
        $idpId = (int)$idpResult[BaseTableConstants::TABLE_IDP_COLUMN_NAME_ID];
        $this->repository->insertIdpVersion($idpId, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);
        $idpVersionResult = $this->repository->getIdpVersion($idpId, $this->idpMetadataHash)->fetchAssociative();

        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);
        $spResult = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();
        $spId = (int)$spResult[BaseTableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $this->spMetadata, $this->spMetadataHash, $this->createdAt);
        $spVersionResult = $this->repository->getSpVersion($spId, $this->spMetadataHash)->fetchAssociative();

        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);
        $userResult = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();
        $userId = (int)$userResult[BaseTableConstants::TABLE_USER_COLUMN_NAME_ID];
        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);
        $userVersionResult = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();

        $idpVersionId = (int)$idpVersionResult[BaseTableConstants::TABLE_IDP_VERSION_COLUMN_NAME_ID];
        $spVersionId = (int)$spVersionResult[BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];
        $userVersionId = (int)$userVersionResult[BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        $idpSpUserVersionId =
            (int)$idpSpUserVersionResult[BaseTableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertAuthenticationEvent(
            $idpSpUserVersionId,
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
