<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Repository;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Test\Module\accounting\Constants\ConnectionParameters;
use SimpleSAML\Test\Module\accounting\Constants\DateTime;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Helpers\FilesystemHelper
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Bases\AbstractMigration
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000000CreateIdpTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000100CreateIdpVersionTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000200CreateSpTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000300CreateSpVersionTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000400CreateUserTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000500CreateUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000600CreateIdpSpUserVersionTable
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Migrations\Version20220801000700CreateAuthenticationEventTable
 *
 * @psalm-suppress all
 */
class RepositoryTest extends TestCase
{
    protected Connection $connection;
    protected \Doctrine\DBAL\Connection $dbal;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|LoggerInterface|LoggerInterface&\PHPUnit\Framework\MockObject\Stub
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
    protected \DateTimeImmutable $createdAt;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|Connection|Connection&\PHPUnit\Framework\MockObject\Stub
     */
    protected $connectionStub;
    protected string $spEntityIdHash;
    protected string $spMetadata;

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
        $migrationsDirectory = $moduleConfiguration->getModuleSourceDirectory() . DIRECTORY_SEPARATOR . 'Stores' .
            DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'Authentication' . DIRECTORY_SEPARATOR .
            'DoctrineDbal' . DIRECTORY_SEPARATOR . 'Versioned' . DIRECTORY_SEPARATOR . 'Store' . DIRECTORY_SEPARATOR .
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

        $this->createdAt = new \DateTimeImmutable();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            Repository::class,
            new Repository($this->connection, $this->loggerStub)
        );
    }

    public function testCanInsertAndGetIdp(): array
    {
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);

        $result = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();

        $this->assertSame($this->idpEntityId, $result[Store\TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID]);
        $this->assertSame(
            $this->idpEntityIdHash,
            $result[Store\TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT]
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
        $this->connectionStub->method('dbal')->willThrowException(new \Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getIdp($this->idpEntityIdHash);
    }

    /**
     * @depends testCanInsertAndGetIdp
     */
    public function testCanInsertAndGetIdpVersion(array $idpResult): array
    {
        $idpId = (int)$idpResult[Store\TableConstants::TABLE_IDP_COLUMN_NAME_ID];

        $this->repository->insertIdpVersion($idpId, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);

        $result = $this->repository->getIdpVersion($idpId, $this->idpMetadataHash)->fetchAssociative();

        $this->assertSame($this->idpMetadata, $result[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA]);
        $this->assertSame(
            $this->idpMetadataHash,
            $result[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT]
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
        $this->connectionStub->method('dbal')->willThrowException(new \Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getIdpVersion(1, $this->idpMetadataHash);
    }

    public function testCanInsertAndGetSp(): array
    {
        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);

        $result = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();

        $this->assertSame($this->spEntityId, $result[Store\TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID]);
        $this->assertSame(
            $this->spEntityIdHash,
            $result[Store\TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT]
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
        $this->connectionStub->method('dbal')->willThrowException(new \Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getSp($this->spEntityIdHash);
    }

    /**
     * @depends testCanInsertAndGetSp
     */
    public function testCanInsertAndGetSpVersion(array $spResult): array
    {
        $spId = (int)$spResult[Store\TableConstants::TABLE_SP_COLUMN_NAME_ID];

        $this->repository->insertSpVersion($spId, $this->spMetadata, $this->spMetadataHash, $this->createdAt);

        $result = $this->repository->getSpVersion($spId, $this->spMetadataHash)->fetchAssociative();

        $this->assertSame($this->spMetadata, $result[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA]);
        $this->assertSame(
            $this->spMetadataHash,
            $result[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT]
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
        $this->connectionStub->method('dbal')->willThrowException(new \Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getSpVersion(1, $this->spMetadataHash);
    }

    public function testCanInsertAndGetUser(): array
    {
        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);

        $result = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();

        $this->assertSame($this->userIdentifier, $result[Store\TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER]);
        $this->assertSame(
            $this->userIdentifierHash,
            $result[Store\TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_USER_COLUMN_NAME_CREATED_AT]
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
        $this->connectionStub->method('dbal')->willThrowException(new \Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getUser($this->userIdentifierHash);
    }

    /**
     * @depends testCanInsertAndGetUser
     */
    public function testCanInsertAndGetUserVersion(array $userResult): array
    {
        $userId = (int)$userResult[Store\TableConstants::TABLE_USER_COLUMN_NAME_ID];

        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);

        $result = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();

        $this->assertSame(
            $this->userAttributes,
            $result[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES]
        );
        $this->assertSame(
            $this->userAttributesHash,
            $result[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES_HASH_SHA256]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT]
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
        $this->connectionStub->method('dbal')->willThrowException(new \Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getUserVersion(1, $this->userIdentifierHash);
    }

    /**
     * @depends testCanInsertAndGetIdpVersion
     * @depends testCanInsertAndGetSpVersion
     * @depends testCanInsertAndGetUserVersion
     */
    public function testCanInsertAndGetIdpSpUserVersion(
        array $idpVersionResult,
        array $spVersionResult,
        array $userVersionResult
    ): array {
        $idpVersionId = (int)$idpVersionResult[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_ID];
        $spVersionId = (int)$spVersionResult[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];
        $userVersionId = (int)$userVersionResult[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $result = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        $this->assertSame(
            $idpVersionId,
            (int)$result[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_IDP_VERSION_ID]
        );
        $this->assertSame(
            $spVersionId,
            (int)$result[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID]
        );
        $this->assertSame(
            $userVersionId,
            (int)$result[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    public function testInsertIdpSpUserVersionThrowsOnNonUnique(): void
    {
        $this->expectException(StoreException::class);
        $this->repository->insertIdpSpUserVersion(1, 1, 1, $this->createdAt);
        $this->repository->insertIdpSpUserVersion(1, 1, 1, $this->createdAt);
    }

    public function testGetIdpSpUserVersionThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new \Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getIdpSpUserVersion(1, 1, 1);
    }

    /**
     * @depends testCanInsertAndGetIdpSpUserVersion
     */
    public function testCanInsertAuthenticationEvent(array $idpSpUserVersionResult): void
    {
        $idpSpUserVersionId =
            (int)$idpSpUserVersionResult[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];
        $createdAt = $happenedAt = new \DateTimeImmutable();

        $authenticationEventCounterQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $authenticationEventCounterQueryBuilder->select('COUNT(id) as authenticationEventCount')
            ->from(
                Store\TableConstants::TABLE_PREFIX .
                $this->connection
                    ->preparePrefixedTableName(Store\TableConstants::TABLE_NAME_AUTHENTICATION_EVENT)
            );

        $this->assertSame(0, (int)$authenticationEventCounterQueryBuilder->executeQuery()->fetchOne());

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $happenedAt, $createdAt);

        $this->assertSame(1, (int)$authenticationEventCounterQueryBuilder->executeQuery()->fetchOne());
    }

    public function testInsertAuthenticationEventThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new \Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->insertAuthenticationEvent(1, $this->createdAt);
    }

    public function testCanGetConnectedServiceProviders(): void
    {
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);
        $idpResult = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();
        $idpId = (int)$idpResult[Store\TableConstants::TABLE_IDP_COLUMN_NAME_ID];
        $this->repository->insertIdpVersion($idpId, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);
        $idpVersionResult = $this->repository->getIdpVersion($idpId, $this->idpMetadataHash)->fetchAssociative();

        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);
        $spResult = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();
        $spId = (int)$spResult[Store\TableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $this->spMetadata, $this->spMetadataHash, $this->createdAt);
        $spVersionResult = $this->repository->getSpVersion($spId, $this->spMetadataHash)->fetchAssociative();

        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);
        $userResult = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();
        $userId = (int)$userResult[Store\TableConstants::TABLE_USER_COLUMN_NAME_ID];
        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);
        $userVersionResult = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();

        $idpVersionId = (int)$idpVersionResult[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_ID];
        $spVersionId = (int)$spVersionResult[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];
        $userVersionId = (int)$userVersionResult[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        $idpSpUserVersionId =
            (int)$idpSpUserVersionResult[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);

        $resultArray = $this->repository->getConnectedServiceProviders($this->userIdentifierHash);

        $this->assertCount(1, $resultArray);
        $this->assertSame(
            '1',
            $resultArray[$this->spEntityId]
            [Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );
        $this->assertSame(
            $this->spMetadata,
            $resultArray[$this->spEntityId]
            [Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_METADATA]
        );
        $this->assertSame(
            $this->userAttributes,
            $resultArray[$this->spEntityId]
            [Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES]
        );

        $resultArray = $this->repository->getConnectedServiceProviders($this->userIdentifierHash);
        $this->assertCount(1, $resultArray);

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);
        $resultArray = $this->repository->getConnectedServiceProviders($this->userIdentifierHash);
        $this->assertCount(1, $resultArray);
        $this->assertSame(
            '2',
            $resultArray[$this->spEntityId]
            [Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );
        $this->assertSame(
            $this->spMetadata,
            $resultArray[$this->spEntityId]
            [Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_METADATA]
        );
        $this->assertSame(
            $this->userAttributes,
            $resultArray[$this->spEntityId]
            [Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES]
        );

        // Simulate another SP
        $spEntityIdNew = $this->spEntityId . '-new';
        $spEntityIdHashNew = $this->spEntityIdHash . '-new';
        $spMetadataNew = $this->spMetadata . '-new';
        $spMetadataHashNew = $this->spMetadataHash . '-new';
        $this->repository->insertSp($spEntityIdNew, $spEntityIdHashNew, $this->createdAt);
        $spResult = $this->repository->getSp($spEntityIdHashNew)->fetchAssociative();
        $spId = (int)$spResult[Store\TableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $spMetadataNew, $spMetadataHashNew, $this->createdAt);
        $spVersionResult = $this->repository->getSpVersion($spId, $spMetadataHashNew)->fetchAssociative();
        $spVersionId = (int)$spVersionResult[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();
        $idpSpUserVersionId =
            (int)$idpSpUserVersionResult[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);

        $resultArray = $this->repository->getConnectedServiceProviders($this->userIdentifierHash);
        $this->assertCount(2, $resultArray);
        $this->assertSame(
            '1',
            $resultArray[$spEntityIdNew]
            [Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );
        $this->assertSame(
            $spMetadataNew,
            $resultArray[$spEntityIdNew]
            [Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_METADATA]
        );
        $this->assertSame(
            $this->userAttributes,
            $resultArray[$this->spEntityId]
            [Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES]
        );

        // Simulate change in user attributes
        $userAttributesNew = $this->userAttributes . '-new';
        $userAttributesHashNew = $this->userAttributesHash . '-new';
        $this->repository->insertUserVersion($userId, $userAttributesNew, $userAttributesHashNew, $this->createdAt);
        $userVersionResult = $this->repository->getUserVersion($userId, $userAttributesHashNew)->fetchAssociative();
        $userVersionId = (int)$userVersionResult[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);

        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();
        $idpSpUserVersionId =
            (int)$idpSpUserVersionResult[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);
        $resultArray = $this->repository->getConnectedServiceProviders($this->userIdentifierHash);

        $this->assertCount(2, $resultArray);
        $this->assertSame(
            '2',
            $resultArray[$spEntityIdNew]
            [Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );
        $this->assertSame(
            $spMetadataNew,
            $resultArray[$spEntityIdNew]
            [Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_METADATA]
        );
        // New SP with new user attributes version..
        $this->assertSame(
            $userAttributesNew,
            $resultArray[$spEntityIdNew]
            [Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES]
        );

        // First SP still has old user attributes version...
        $this->assertSame(
            $this->userAttributes,
            $resultArray[$this->spEntityId]
            [Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES]
        );
    }

    public function testGetConnectedServiceProvidersThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new \Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getConnectedServiceProviders($this->userIdentifierHash);
    }

    public function testCanGetActivity(): void
    {
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);
        $idpResult = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();
        $idpId = (int)$idpResult[Store\TableConstants::TABLE_IDP_COLUMN_NAME_ID];
        $this->repository->insertIdpVersion($idpId, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);
        $idpVersionResult = $this->repository->getIdpVersion($idpId, $this->idpMetadataHash)->fetchAssociative();

        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);
        $spResult = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();
        $spId = (int)$spResult[Store\TableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $this->spMetadata, $this->spMetadataHash, $this->createdAt);
        $spVersionResult = $this->repository->getSpVersion($spId, $this->spMetadataHash)->fetchAssociative();

        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);
        $userResult = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();
        $userId = (int)$userResult[Store\TableConstants::TABLE_USER_COLUMN_NAME_ID];
        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);
        $userVersionResult = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();

        $idpVersionId = (int)$idpVersionResult[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_ID];
        $spVersionId = (int)$spVersionResult[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];
        $userVersionId = (int)$userVersionResult[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        $idpSpUserVersionId =
            (int)$idpSpUserVersionResult[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);

        $resultArray = $this->repository->getActivity($this->userIdentifierHash, 10, 0);
        $this->assertCount(1, $resultArray);

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);
        $resultArray = $this->repository->getActivity($this->userIdentifierHash, 10, 0);
        $this->assertCount(2, $resultArray);

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);
        $resultArray = $this->repository->getActivity($this->userIdentifierHash, 10, 0);
        $this->assertCount(3, $resultArray);

        // Simulate another SP
        $spEntityIdNew = $this->spEntityId . '-new';
        $spEntityIdHashNew = $this->spEntityIdHash . '-new';
        $spMetadataNew = $this->spMetadata . '-new';
        $spMetadataHashNew = $this->spMetadataHash . '-new';
        $this->repository->insertSp($spEntityIdNew, $spEntityIdHashNew, $this->createdAt);
        $spResult = $this->repository->getSp($spEntityIdHashNew)->fetchAssociative();
        $spId = (int)$spResult[Store\TableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $spMetadataNew, $spMetadataHashNew, $this->createdAt);
        $spVersionResult = $this->repository->getSpVersion($spId, $spMetadataHashNew)->fetchAssociative();
        $spVersionId = (int)$spVersionResult[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        $idpSpUserVersionId =
            (int)$idpSpUserVersionResult[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);
        $resultArray = $this->repository->getActivity($this->userIdentifierHash, 10, 0);
        $this->assertCount(4, $resultArray);

        // Simulate a change in user attributes
    }

    public function testGetActivityThrowsOnInvalidDbal(): void
    {
        $this->connectionStub->method('dbal')->willThrowException(new \Exception('test'));
        $repository = new Repository($this->connectionStub, $this->loggerStub);
        $this->expectException(StoreException::class);

        $repository->getActivity($this->userIdentifierHash, 10, 0);
    }
}
