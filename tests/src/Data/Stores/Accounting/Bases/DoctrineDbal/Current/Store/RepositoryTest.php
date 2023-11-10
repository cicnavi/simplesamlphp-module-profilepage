<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Repository;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\TableConstants;
// phpcs:ignore
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\TableConstants as VersionedTableConstants;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Saml2;
use SimpleSAML\Module\profilepage\Exceptions\Exception;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException\MigrationException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Test\Module\profilepage\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\profilepage\Constants\DateTime;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Repository
 * @uses \SimpleSAML\Module\profilepage\Helpers\Filesystem
 * @uses \SimpleSAML\Module\profilepage\ModuleConfiguration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateIdpTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateSpTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateUserTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Current\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserVersionTable
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\DoctrineDbal\Versioned\Store\Migrations\CreateUserTable
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

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanInsertAndGetIdp(): array
    {
        $this->repository->insertIdp(
            $this->idpEntityId,
            $this->idpEntityIdHash,
            $this->idpMetadata,
            $this->idpMetadataHash,
            $this->createdAt
        );

        $result = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();

        $this->assertSame($this->idpEntityId, $result[TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID]);
        $this->assertSame(
            $this->idpEntityIdHash,
            $result[TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256]
        );
        $this->assertSame($this->idpMetadata, $result[TableConstants::TABLE_IDP_COLUMN_NAME_METADATA]);
        $this->assertSame($this->idpMetadataHash, $result[TableConstants::TABLE_IDP_COLUMN_NAME_METADATA_HASH_SHA256]);
        $this->assertSame(
            $this->createdAt->getTimestamp(),
            (int)$result[TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    public function testInsertIdpThrowsOnNonUniqueIdpEntityIdHash(): void
    {
        $this->expectException(StoreException::class);

        // Can't insert duplicate idp entity ID hash.
        $this->repository->insertIdp(
            $this->idpEntityId,
            $this->idpEntityIdHash,
            $this->idpMetadata,
            $this->idpMetadataHash,
            $this->createdAt
        );
        $this->repository->insertIdp(
            $this->idpEntityId,
            $this->idpEntityIdHash,
            $this->idpMetadata,
            $this->idpMetadataHash,
            $this->createdAt
        );
    }

    public function testGetIdpThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getIdp($this->idpEntityIdHash);
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanUpdateIdp(): void
    {
        $this->repository->insertIdp(
            $this->idpEntityId,
            $this->idpEntityIdHash,
            $this->idpMetadata,
            $this->idpMetadataHash,
            $this->createdAt
        );

        $result = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();
        $this->assertSame($this->idpMetadata, $result[TableConstants::TABLE_IDP_COLUMN_NAME_METADATA]);

        $this->repository->updateIdp(
            (int)$result[TableConstants::TABLE_IDP_COLUMN_NAME_ID],
            'new-idp-metadata',
            'new-idp-metadata-hash'
        );

        $newResult = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();
        $this->assertSame('new-idp-metadata', $newResult[TableConstants::TABLE_IDP_COLUMN_NAME_METADATA]);
        $this->assertSame(
            'new-idp-metadata-hash',
            $newResult[TableConstants::TABLE_IDP_COLUMN_NAME_METADATA_HASH_SHA256]
        );

        $this->assertNotSame(
            $result[TableConstants::TABLE_IDP_COLUMN_NAME_METADATA],
            $newResult[TableConstants::TABLE_IDP_COLUMN_NAME_METADATA]
        );
        $this->assertNotSame(
            $this->idpMetadata,
            $newResult[TableConstants::TABLE_IDP_COLUMN_NAME_METADATA]
        );
    }

    public function testUpdateIdpThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->updateIdp(
            1,
            'new-idp-metadata',
            'new-idp-metadata-hash'
        );
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanInsertAndGetSp(): array
    {
        $this->repository->insertSp(
            $this->spEntityId,
            $this->spEntityIdHash,
            $this->spMetadata,
            $this->spMetadataHash,
            $this->createdAt
        );

        $result = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();

        $this->assertSame($this->spEntityId, $result[TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID]);
        $this->assertSame(
            $this->spEntityIdHash,
            $result[TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256]
        );
        $this->assertSame($this->spMetadata, $result[TableConstants::TABLE_SP_COLUMN_NAME_METADATA]);
        $this->assertSame($this->spMetadataHash, $result[TableConstants::TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256]);
        $this->assertSame(
            $this->createdAt->getTimestamp(),
            (int)$result[TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    public function testInsertSpThrowsOnNonUniqueSpEntityIdHash(): void
    {
        $this->expectException(StoreException::class);
        // SP Entity ID Hash must be unique.
        $this->repository->insertSp(
            $this->spEntityId,
            $this->spEntityIdHash,
            $this->spMetadata,
            $this->spMetadataHash,
            $this->createdAt
        );
        $this->repository->insertSp(
            $this->spEntityId,
            $this->spEntityIdHash,
            $this->spMetadata,
            $this->spMetadataHash,
            $this->createdAt
        );
    }

    public function testGetSpThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getSp($this->spEntityIdHash);
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanUpdateSp(): void
    {
        $this->repository->insertSp(
            $this->spEntityId,
            $this->spEntityIdHash,
            $this->spMetadata,
            $this->spMetadataHash,
            $this->createdAt
        );

        $result = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();
        $this->assertSame($this->spMetadata, $result[TableConstants::TABLE_SP_COLUMN_NAME_METADATA]);

        $this->repository->updateSp(
            (int)$result[TableConstants::TABLE_SP_COLUMN_NAME_ID],
            'new-sp-metadata',
            'new-sp-metadata-hash'
        );

        $newResult = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();
        $this->assertSame('new-sp-metadata', $newResult[TableConstants::TABLE_SP_COLUMN_NAME_METADATA]);
        $this->assertSame(
            'new-sp-metadata-hash',
            $newResult[TableConstants::TABLE_SP_COLUMN_NAME_METADATA_HASH_SHA256]
        );

        $this->assertNotSame(
            $result[TableConstants::TABLE_SP_COLUMN_NAME_METADATA],
            $newResult[TableConstants::TABLE_SP_COLUMN_NAME_METADATA]
        );
        $this->assertNotSame(
            $this->spMetadata,
            $newResult[TableConstants::TABLE_SP_COLUMN_NAME_METADATA]
        );
    }

    public function testUpdateSpThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->updateSp(
            1,
            'new-sp-metadata',
            'new-sp-metadata-hash'
        );
    }

    /**
     * @throws StoreException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCanInsertAndGetUser(): array
    {
        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);

        $result = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();

        $this->assertSame($this->userIdentifier, $result[VersionedTableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER]);
        $this->assertSame(
            $this->userIdentifierHash,
            $result[VersionedTableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256]
        );
        $this->assertSame(
            $this->createdAt->getTimestamp(),
            (int)$result[VersionedTableConstants::TABLE_USER_COLUMN_NAME_CREATED_AT]
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
}
