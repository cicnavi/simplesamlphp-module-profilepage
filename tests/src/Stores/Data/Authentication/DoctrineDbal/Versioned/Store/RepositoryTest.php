<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Repository;
use PHPUnit\Framework\TestCase;
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

    protected function setUp(): void
    {
        $connectionParameters = ['driver' => 'pdo_sqlite', 'memory' => true,];
        $this->connection = new Connection($connectionParameters);
        $this->loggerStub = $this->createStub(LoggerInterface::class);
        $this->migrator = new Migrator($this->connection, $this->loggerStub);
        $moduleConfiguration = new ModuleConfiguration();
        $migrationsDirectory = $moduleConfiguration->getModuleSourceDirectory() . DIRECTORY_SEPARATOR . 'Stores' .
            DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'Authentication' . DIRECTORY_SEPARATOR .
            'DoctrineDbal' . DIRECTORY_SEPARATOR . 'Versioned' . DIRECTORY_SEPARATOR . 'Store' . DIRECTORY_SEPARATOR .
            AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;
        $namespace = Store::class . '\\' . AbstractMigrator::DEFAULT_MIGRATIONS_DIRECTORY_NAME;

        $this->migrator->runSetup();
        $this->migrator->runNonImplementedMigrationClasses($migrationsDirectory, $namespace);

        /** @psalm-suppress PossiblyInvalidArgument */
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
        /** @psalm-suppress PossiblyInvalidArgument */
        $this->assertInstanceOf(
            Repository::class,
            new Repository($this->connection, $this->loggerStub)
        );
    }

    public function testCanInsertAndGetIdp(): array
    {
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);

        $result = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($this->idpEntityId, $result[Store\TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($this->idpEntityIdHash, $result[Store\TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_IDP_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    /**
     * @depends testCanInsertAndGetIdp
     */
    public function testCanInsertAndGetIdpVersion(array $idpResult): array
    {
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $idpId = $idpResult[Store\TableConstants::TABLE_IDP_COLUMN_NAME_ID];

        $this->repository->insertIdpVersion($idpId, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);

        $result = $this->repository->getIdpVersion($idpId, $this->idpMetadataHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($this->idpMetadata, $result[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $this->idpMetadataHash,
            $result[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256]
        );
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    public function testCanInsertAndGetSp(): array
    {
        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);

        $result = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($this->spEntityId, $result[Store\TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($this->spEntityIdHash, $result[Store\TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_SP_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    /**
     * @depends testCanInsertAndGetSp
     */
    public function testCanInsertAndGetSpVersion(array $spResult): array
    {
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $spId = $spResult[Store\TableConstants::TABLE_SP_COLUMN_NAME_ID];

        $this->repository->insertSpVersion($spId, $this->spMetadata, $this->spMetadataHash, $this->createdAt);

        $result = $this->repository->getSpVersion($spId, $this->spMetadataHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($this->spMetadata, $result[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $this->spMetadataHash,
            $result[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256]
        );
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    public function testCanInsertAndGetUser(): array
    {
        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);

        $result = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($this->userIdentifier, $result[Store\TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $this->userIdentifierHash,
            $result[Store\TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256]
        );
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_USER_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    /**
     * @depends testCanInsertAndGetUser
     */
    public function testCanInsertAndGetUserVersion(array $userResult): array
    {
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $userId = $userResult[Store\TableConstants::TABLE_USER_COLUMN_NAME_ID];

        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);

        $result = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($this->userAttributes, $result[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $this->userAttributesHash,
            $result[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES_HASH_SHA256]
        );
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_CREATED_AT]
        );

        return $result;
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
        $idpVersionId = $idpVersionResult[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_ID];
        $spVersionId = $spVersionResult[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];
        $userVersionId = $userVersionResult[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $result = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        $this->assertSame(
            $idpVersionId,
            $result[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_IDP_VERSION_ID]
        );
        $this->assertSame(
            $spVersionId,
            $result[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID]
        );
        $this->assertSame(
            $userVersionId,
            $result[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_SP_VERSION_ID]
        );
        $this->assertSame(
            $this->createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    /**
     * @depends testCanInsertAndGetIdpSpUserVersion
     */
    public function testCanInsertAuthenticationEvent(array $idpSpUserVersionResult): void
    {
        $idpSpUserVersionId = $idpSpUserVersionResult[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];
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

    public function testCanGetConnectedServiceProviders(): void
    {
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);
        $idpResult = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $idpId = $idpResult[Store\TableConstants::TABLE_IDP_COLUMN_NAME_ID];
        $this->repository->insertIdpVersion($idpId, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);
        $idpVersionResult = $this->repository->getIdpVersion($idpId, $this->idpMetadataHash)->fetchAssociative();

        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);
        $spResult = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $spId = $spResult[Store\TableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $this->spMetadata, $this->spMetadataHash, $this->createdAt);
        $spVersionResult = $this->repository->getSpVersion($spId, $this->spMetadataHash)->fetchAssociative();

        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);
        $userResult = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $userId = $userResult[Store\TableConstants::TABLE_USER_COLUMN_NAME_ID];
        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);
        $userVersionResult = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $idpVersionId = $idpVersionResult[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_ID];
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $spVersionId = $spVersionResult[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $userVersionId = $userVersionResult[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $idpSpUserVersionId = $idpSpUserVersionResult[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);
        
        $resultArray = $this->repository->getConnectedServiceProviders($this->userIdentifierHash);

        $this->assertCount(1, $resultArray);
        $this->assertSame(
            "1",
            $resultArray[$this->spEntityId][Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );

        $resultArray = $this->repository->getConnectedServiceProviders($this->userIdentifierHash);
        $this->assertCount(1, $resultArray);

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);
        $resultArray = $this->repository->getConnectedServiceProviders($this->userIdentifierHash);
        $this->assertCount(1, $resultArray);
        $this->assertSame(
            "2",
            $resultArray[$this->spEntityId][Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );

        // Simulate another SP
        $spEntityIdNew = $this->spEntityId . '-new';
        $spEntityIdHashNew = $this->spEntityIdHash . '-new';
        $spMetadataNew = $this->spMetadata . '-new';
        $spMetadataHashNew = $this->spMetadataHash . '-new';
        $this->repository->insertSp($spEntityIdNew, $spEntityIdHashNew, $this->createdAt);
        $spResult = $this->repository->getSp($spEntityIdHashNew)->fetchAssociative();
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $spId = $spResult[Store\TableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $spMetadataNew, $spMetadataHashNew, $this->createdAt);
        $spVersionResult = $this->repository->getSpVersion($spId, $spMetadataHashNew)->fetchAssociative();
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $spVersionId = $spVersionResult[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $idpSpUserVersionId = $idpSpUserVersionResult[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);

        $resultArray = $this->repository->getConnectedServiceProviders($this->userIdentifierHash);
        $this->assertCount(2, $resultArray);
        $this->assertSame(
            "1",
            $resultArray[$spEntityIdNew][Store\TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS]
        );
    }

    public function testCanGetActivity(): void
    {
        $this->repository->insertIdp($this->idpEntityId, $this->idpEntityIdHash, $this->createdAt);
        $idpResult = $this->repository->getIdp($this->idpEntityIdHash)->fetchAssociative();
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $idpId = $idpResult[Store\TableConstants::TABLE_IDP_COLUMN_NAME_ID];
        $this->repository->insertIdpVersion($idpId, $this->idpMetadata, $this->idpMetadataHash, $this->createdAt);
        $idpVersionResult = $this->repository->getIdpVersion($idpId, $this->idpMetadataHash)->fetchAssociative();

        $this->repository->insertSp($this->spEntityId, $this->spEntityIdHash, $this->createdAt);
        $spResult = $this->repository->getSp($this->spEntityIdHash)->fetchAssociative();
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $spId = $spResult[Store\TableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $this->spMetadata, $this->spMetadataHash, $this->createdAt);
        $spVersionResult = $this->repository->getSpVersion($spId, $this->spMetadataHash)->fetchAssociative();

        $this->repository->insertUser($this->userIdentifier, $this->userIdentifierHash, $this->createdAt);
        $userResult = $this->repository->getUser($this->userIdentifierHash)->fetchAssociative();
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $userId = $userResult[Store\TableConstants::TABLE_USER_COLUMN_NAME_ID];
        $this->repository
            ->insertUserVersion($userId, $this->userAttributes, $this->userAttributesHash, $this->createdAt);
        $userVersionResult = $this->repository->getUserVersion($userId, $this->userAttributesHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $idpVersionId = $idpVersionResult[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_ID];
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $spVersionId = $spVersionResult[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $userVersionId = $userVersionResult[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $idpSpUserVersionId = $idpSpUserVersionResult[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);

        $resultArray = $this->repository->getActivity($this->userIdentifierHash);
        $this->assertCount(1, $resultArray);

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);
        $resultArray = $this->repository->getActivity($this->userIdentifierHash);
        $this->assertCount(2, $resultArray);

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);
        $resultArray = $this->repository->getActivity($this->userIdentifierHash);
        $this->assertCount(3, $resultArray);

        // Simulate another SP
        $spEntityIdNew = $this->spEntityId . '-new';
        $spEntityIdHashNew = $this->spEntityIdHash . '-new';
        $spMetadataNew = $this->spMetadata . '-new';
        $spMetadataHashNew = $this->spMetadataHash . '-new';
        $this->repository->insertSp($spEntityIdNew, $spEntityIdHashNew, $this->createdAt);
        $spResult = $this->repository->getSp($spEntityIdHashNew)->fetchAssociative();
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $spId = $spResult[Store\TableConstants::TABLE_SP_COLUMN_NAME_ID];
        $this->repository->insertSpVersion($spId, $spMetadataNew, $spMetadataHashNew, $this->createdAt);
        $spVersionResult = $this->repository->getSpVersion($spId, $spMetadataHashNew)->fetchAssociative();
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $spVersionId = $spVersionResult[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_ID];

        $this->repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $this->createdAt);
        $idpSpUserVersionResult = $this->repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)
            ->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $idpSpUserVersionId = $idpSpUserVersionResult[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];

        $this->repository->insertAuthenticationEvent($idpSpUserVersionId, $this->createdAt, $this->createdAt);

        $resultArray = $this->repository->getActivity($this->userIdentifierHash);

        $this->assertCount(4, $resultArray);
    }
}
