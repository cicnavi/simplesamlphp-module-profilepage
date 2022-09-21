<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\RawConnectedServiceProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\TableConstants;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\RawConnectedServiceProvider
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractRawEntity
 */
class RawConnectedServiceProviderTest extends TestCase
{
    protected int $numberOfAuthentications;
    protected string $lastAuthenticationAt;
    protected string $firstAuthenticationAt;
    /**
     * @var string[]
     */
    protected array $serviceProviderMetadata;
    /**
     * @var string[]
     */
    protected array $userAttributes;
    protected array $rawRow;
    /**
     * @var AbstractPlatform|AbstractPlatform&\PHPUnit\Framework\MockObject\Stub|\PHPUnit\Framework\MockObject\Stub
     */
    protected $abstractPlatformStub;
    protected string $dateTimeFormat;

    protected function setUp(): void
    {
        $this->numberOfAuthentications = 2;
        $this->lastAuthenticationAt = '2022-02-22 22:22:22';
        $this->firstAuthenticationAt = '2022-02-02 22:22:22';
        $this->serviceProviderMetadata = ['sp' => 'metadata'];
        $this->userAttributes = ['user' => 'attribute'];
        $this->rawRow = [
            TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS =>
                $this->numberOfAuthentications,
            TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_LAST_AUTHENTICATION_AT =>
                $this->lastAuthenticationAt,
            TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_FIRST_AUTHENTICATION_AT =>
                $this->firstAuthenticationAt,
            TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_METADATA =>
                serialize($this->serviceProviderMetadata),
            TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES =>
                serialize($this->userAttributes),
        ];
        $this->dateTimeFormat = 'Y-m-d H:i:s';
        $this->abstractPlatformStub = $this->createStub(AbstractPlatform::class);
        $this->abstractPlatformStub->method('getDateTimeFormatString')
            ->willReturn($this->dateTimeFormat);
    }

    public function testCanCreateInstance(): void
    {
        /** @psalm-suppress PossiblyInvalidArgument */
        $this->assertInstanceOf(
            RawConnectedServiceProvider::class,
            new RawConnectedServiceProvider($this->rawRow, $this->abstractPlatformStub)
        );
    }

    public function testCanGetProperties(): void
    {
        /** @psalm-suppress PossiblyInvalidArgument */
        $rawConnectedServiceProvider = new RawConnectedServiceProvider($this->rawRow, $this->abstractPlatformStub);

        $this->assertSame($this->numberOfAuthentications, $rawConnectedServiceProvider->getNumberOfAuthentications());
        $this->assertInstanceOf(\DateTimeImmutable::class, $rawConnectedServiceProvider->getLastAuthenticationAt());
        $this->assertSame(
            $this->lastAuthenticationAt,
            $rawConnectedServiceProvider->getLastAuthenticationAt()->format($this->dateTimeFormat)
        );
        $this->assertInstanceOf(\DateTimeImmutable::class, $rawConnectedServiceProvider->getFirstAuthenticationAt());
        $this->assertSame(
            $this->firstAuthenticationAt,
            $rawConnectedServiceProvider->getFirstAuthenticationAt()->format($this->dateTimeFormat)
        );
        $this->assertSame($this->serviceProviderMetadata, $rawConnectedServiceProvider->getServiceProviderMetadata());
        $this->assertSame($this->userAttributes, $rawConnectedServiceProvider->getUserAttributes());
    }

    public function testThrowsIfColumnNotSet(): void
    {
        $rawRow = $this->rawRow;
        unset($rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES]);

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawConnectedServiceProvider($rawRow, $this->abstractPlatformStub);
    }

    public function testThrowsIfNumberOfAuthenticationsNotNumeric(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS] = 'a';

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawConnectedServiceProvider($rawRow, $this->abstractPlatformStub);
    }

    public function testThrowsIfLastAuthenticationAtNotString(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_LAST_AUTHENTICATION_AT] = 1;

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawConnectedServiceProvider($rawRow, $this->abstractPlatformStub);
    }

    public function testThrowsIfFirstAuthenticationAtNotString(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_FIRST_AUTHENTICATION_AT] = 1;

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawConnectedServiceProvider($rawRow, $this->abstractPlatformStub);
    }

    public function testThrowsIfSpMetadataNotString(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_METADATA] = 1;

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawConnectedServiceProvider($rawRow, $this->abstractPlatformStub);
    }

    public function testThrowsIfUserAttributesNotString(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES] = 1;

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawConnectedServiceProvider($rawRow, $this->abstractPlatformStub);
    }

    public function testThrowsIfSpMetadataNotValid(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_SP_METADATA] = serialize(1);

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawConnectedServiceProvider($rawRow, $this->abstractPlatformStub);
    }

    public function testThrowsIfUserAttributesNotValid(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[TableConstants::ENTITY_CONNECTED_ORGANIZATION_COLUMN_NAME_USER_ATTRIBUTES] = serialize(1);

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawConnectedServiceProvider($rawRow, $this->abstractPlatformStub);
    }
}
