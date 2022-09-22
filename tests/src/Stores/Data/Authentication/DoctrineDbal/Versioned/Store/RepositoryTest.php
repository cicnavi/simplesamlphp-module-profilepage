<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

use Doctrine\DBAL\DriverManager;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\Bases\AbstractMigrator;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Repository;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\TableConstants;
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
    protected string $userIdentifierHash;

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

        $this->dateTimeFormat = DateTime::DEFAULT_FORMAT;
        $this->userIdentifierHash = 'user-identifier-hash';
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
        $idpEntityId = 'idp-entity-id';
        $idpEntityIdHash = 'idp-entity-id-hash';
        $createdAt = new \DateTimeImmutable();

        /** @psalm-suppress PossiblyInvalidArgument */
        $repository = new Repository($this->connection, $this->loggerStub);

        $repository->insertIdp($idpEntityId, $idpEntityIdHash, $createdAt);

        $result = $repository->getIdp($idpEntityIdHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($idpEntityId, $result[Store\TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($idpEntityIdHash, $result[Store\TableConstants::TABLE_IDP_COLUMN_NAME_ENTITY_ID_HASH_SHA256]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $createdAt->format($this->dateTimeFormat),
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
        $idpMetadata = 'idp-metadata';
        $idpMetadataHash = 'idp-metadata-hash';
        $createdAt = new \DateTimeImmutable();

        /** @psalm-suppress PossiblyInvalidArgument */
        $repository = new Repository($this->connection, $this->loggerStub);

        $repository->insertIdpVersion($idpId, $idpMetadata, $idpMetadataHash, $createdAt);

        $result = $repository->getIdpVersion($idpId, $idpMetadataHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($idpMetadata, $result[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $idpMetadataHash,
            $result[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256]
        );
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_IDP_VERSION_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    public function testCanInsertAndGetSp(): array
    {
        $spEntityId = 'sp-entity-id';
        $spEntityIdHash = 'sp-entity-id-hash';
        $createdAt = new \DateTimeImmutable();

        $repository = new Repository($this->connection, $this->loggerStub);

        $repository->insertSp($spEntityId, $spEntityIdHash, $createdAt);

        $result = $repository->getSp($spEntityIdHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($spEntityId, $result[Store\TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($spEntityIdHash, $result[Store\TableConstants::TABLE_SP_COLUMN_NAME_ENTITY_ID_HASH_SHA256]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $createdAt->format($this->dateTimeFormat),
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
        $spMetadata = 'sp-metadata';
        $spMetadataHash = 'sp-metadata-hash';
        $createdAt = new \DateTimeImmutable();

        /** @psalm-suppress PossiblyInvalidArgument */
        $repository = new Repository($this->connection, $this->loggerStub);

        $repository->insertSpVersion($spId, $spMetadata, $spMetadataHash, $createdAt);

        $result = $repository->getSpVersion($spId, $spMetadataHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($spMetadata, $result[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $spMetadataHash,
            $result[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_METADATA_HASH_SHA256]
        );
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_SP_VERSION_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    public function testCanInsertAndGetUser(): array
    {
        $userIdentifier = 'user-identifier';
        $userIdentifierHash = $this->userIdentifierHash;
        $createdAt = new \DateTimeImmutable();

        $repository = new Repository($this->connection, $this->loggerStub);

        $repository->insertUser($userIdentifier, $userIdentifierHash, $createdAt);

        $result = $repository->getUser($userIdentifierHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($userIdentifier, $result[Store\TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $userIdentifierHash,
            $result[Store\TableConstants::TABLE_USER_COLUMN_NAME_IDENTIFIER_HASH_SHA256]
        );
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $createdAt->format($this->dateTimeFormat),
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
        $userAttributes = 'user-attributes';
        $userAttributesHash = 'user-attributes-hash';
        $createdAt = new \DateTimeImmutable();

        /** @psalm-suppress PossiblyInvalidArgument */
        $repository = new Repository($this->connection, $this->loggerStub);

        $repository->insertUserVersion($userId, $userAttributes, $userAttributesHash, $createdAt);

        $result = $repository->getUserVersion($userId, $userAttributesHash)->fetchAssociative();

        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame($userAttributes, $result[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES]);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $userAttributesHash,
            $result[Store\TableConstants::TABLE_USER_VERSION_COLUMN_NAME_ATTRIBUTES_HASH_SHA256]
        );
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $this->assertSame(
            $createdAt->format($this->dateTimeFormat),
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
        $createdAt = new \DateTimeImmutable();

        $repository = new Repository($this->connection, $this->loggerStub);

        $repository->insertIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId, $createdAt);
        $result = $repository->getIdpSpUserVersion($idpVersionId, $spVersionId, $userVersionId)->fetchAssociative();

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
            $createdAt->format($this->dateTimeFormat),
            $result[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_CREATED_AT]
        );

        return $result;
    }

    /**
     * @depends testCanInsertAndGetIdpSpUserVersion
     */
    public function testCanInsertAuthenticationEvent(array $idpSpUserVersion): void
    {
        $idpSpUserVersionId = $idpSpUserVersion[Store\TableConstants::TABLE_IDP_SP_USER_VERSION_COLUMN_NAME_ID];
        $createdAt = $happenedAt = new \DateTimeImmutable();

        $authenticationEventCounterQueryBuilder = $this->connection->dbal()->createQueryBuilder();
        $authenticationEventCounterQueryBuilder->select('COUNT(id) as authenticationEventCount')
            ->from(
                Store\TableConstants::TABLE_PREFIX .
                $this->connection
                    ->preparePrefixedTableName(Store\TableConstants::TABLE_NAME_AUTHENTICATION_EVENT)
            );

        $this->assertSame(0, (int)$authenticationEventCounterQueryBuilder->executeQuery()->fetchOne());

        $repository = new Repository($this->connection, $this->loggerStub);

        $repository->insertAuthenticationEvent($idpSpUserVersionId, $happenedAt, $createdAt);

        $this->assertSame(1, (int)$authenticationEventCounterQueryBuilder->executeQuery()->fetchOne());
    }
}
