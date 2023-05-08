<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store;

use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants
    as BaseTableConstants;
use SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Saml2;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\accounting\Constants\DateTime;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Helpers\Filesystem
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateSpTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateSpVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateIdpSpUserVersionTable
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
            'Bases' . DIRECTORY_SEPARATOR .
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

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanInsertAndGetIdp(): array
    {
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);

        $result = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();

        $this->assertSame($this->idpEntityId, $result[BaseTableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID]);
        $this->assertSame(
            $this->idpEntityIdHash,
            $result[BaseTableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[BaseTableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    public function testInsertIdpThrowsOnNonUniqueIdpEntityIdHash(): void
    {
        $this->expectException(StoreException::class);

        // Can't insert duplicate idp entity ID hash.
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);
    }

    public function testGetIdpThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getIdp($this->idpEntityIdHash);
    }

    /**
     * @depends testCanInsertAndGetIdp
     * @throws StoreException
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanInsertAndGetIdpVersion(array $idpResult): array
    {
        $idpId = (int)$idpResult[BaseTableConstants::TABLE_IDP_COLUMN_NAME_ID];

        $this->repository->insertIdpVersion($idpId, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);

        $result = $this->repository->getIdpVersion($idpId, $this->idpMetadataHash)->fetchAssociative();

        $this->assertSame($this->idpMetadata, $result[BaseTableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA]);
        $this->assertSame(
            $this->idpMetadataHash,
            $result[BaseTableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[BaseTableConstants::TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    public function testInsertIdpVersionThrowsOnNonUniqueIdpMetadataHash(): void
    {
        $this->expectException(StoreException::class);
        // IdP Metadata Hash must be unique.
        $this->repository->insertIdpVersion(1, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);
        $this->repository->insertIdpVersion(1, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);
    }

    public function testGetIdpVersionThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getIdpVersion(1, $this->idpMetadataHash);
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanInsertAndGetSp(): array
    {
        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);

        $result = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();

        $this->assertSame($this->spEntityId, $result[BaseTableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID]);
        $this->assertSame(
            $this->spEntityIdHash,
            $result[BaseTableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[BaseTableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    public function testInsertSpThrowsOnNonUniqueSpEntityIdHash(): void
    {
        $this->expectException(StoreException::class);
        // SP Entity ID Hash must be unique.
        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);
        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);
    }

    public function testGetSpThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getSp($this->spEntityIdHash);
    }

    /**
     * @depends testCanInsertAndGetSp
     * @throws StoreException
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanInsertAndGetSpVersion(array $spResult): array
    {
        $spId = (int)$spResult[BaseTableConstants::TABLE_SP_COLUMN_NAME_ID];

        $this->repository->insertSpVersion($spId, $this->spMetadata, $this->spMetadataHash, $this->createdAt);

        $result = $this->repository->getSpVersion($spId, $this->spMetadataHash)->fetchAssociative();

        $this->assertSame($this->spMetadata, $result[BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA]);
        $this->assertSame(
            $this->spMetadataHash,
            $result[BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[BaseTableConstants::TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    public function testInsertSpVersionThrowsOnNonUniqueMetadataHash(): void
    {
        $this->expectException(StoreException::class);
        // SP metadata hash must be unique.
        $this->repository->insertSpVersion(1, $this->spMetadata, $this->spMetadataHash, $this->createdAt);
        $this->repository->insertSpVersion(1, $this->spMetadata, $this->spMetadataHash, $this->createdAt);
    }

    public function testGetSpVersionThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getSpVersion(1, $this->spMetadataHash);
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanInsertAndGetUser(): array
    {
        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);

        $result = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();

        $this->assertSame($this->userIdentifier, $result[BaseTableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER]);
        $this->assertSame(
            $this->userIdentifierHash,
            $result[BaseTableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[BaseTableConstants::TABLE_USER_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    public function testInsertUserThrowsOnNonUniqueIdentifierHash(): void
    {
        $this->expectException(StoreException::class);
        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);
        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);
    }

    public function testGetUserThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getUser($this->userIdentifierHash);
    }

    /**
     * @depends testCanInsertAndGetUser
     * @throws StoreException
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanInsertAndGetUserVersion(array $userResult): array
    {
        $userId = (int)$userResult[BaseTableConstants::TABLE_USER_COLUMN_NAME_ID];

        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);

        $result = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();

        $this->assertSame(
            $this->userAttributes,
            $result[BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES]
        );
        $this->assertSame(
            $this->userAttributesHash,
            $result[BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES_HASH_SHA256]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[BaseTableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    public function testInsertUserVersionThrowsOnNonUniqueAttributesHash(): void
    {
        $this->expectException(StoreException::class);
        $this->repository
            ->insertUserVersion(1, $this->userAttributes, $this->userAttributesHash, $this->createdAt);
        $this->repository
            ->insertUserVersion(1, $this->userAttributes, $this->userAttributesHash, $this->createdAt);
    }

    public function testGetUserVersionThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getUserVersion(1, $this->userIdentifierHash);
    }
}
