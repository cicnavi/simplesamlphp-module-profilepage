<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\RawConnectedService;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Interfaces\SerializerInterface;
use SimpleSAML\Test\Module\accounting\Constants\DateTime;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\EntityTableConstants;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Accounting\ConnectedServices\DoctrineDbal\RawConnectedService
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity
 */
class RawConnectedServiceTest extends TestCase
{
    protected int $numberOfAuthentications;
    protected int $lastAuthenticationAt;
    protected int $firstAuthenticationAt;
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
     * @var AbstractPlatform|Stub
     */
    protected $abstractPlatformStub;
    protected string $dateTimeFormat;
    protected MockObject $serializerMock;

    protected function setUp(): void
    {
        $this->numberOfAuthentications = 2;
        $this->lastAuthenticationAt = 1645564942;
        $this->firstAuthenticationAt = 1645564942;
        $this->serviceProviderMetadata = ['sp' => 'metadata'];
        $this->userAttributes = ['user' => 'attribute'];
        $this->rawRow = [
            EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS =>
                $this->numberOfAuthentications,
            EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT =>
                $this->lastAuthenticationAt,
            EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT =>
                $this->firstAuthenticationAt,
            EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA =>
                serialize($this->serviceProviderMetadata),
            EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES =>
                serialize($this->userAttributes),
        ];
        $this->dateTimeFormat = DateTime::DEFAULT_FORMAT;
        $this->abstractPlatformStub = $this->createStub(AbstractPlatform::class);
        $this->abstractPlatformStub->method('getDateTimeFormatString')
            ->willReturn($this->dateTimeFormat);

        $this->serializerMock = $this->createMock(SerializerInterface::class);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            RawConnectedService::class,
            new RawConnectedService($this->rawRow, $this->abstractPlatformStub, $this->serializerMock)
        );
    }

    public function testCanGetProperties(): void
    {
        $rawConnectedServiceProvider = new RawConnectedService(
            $this->rawRow,
            $this->abstractPlatformStub,
            $this->serializerMock
        );

        $this->assertSame($this->numberOfAuthentications, $rawConnectedServiceProvider->getNumberOfAuthentications());
        $this->assertInstanceOf(DateTimeImmutable::class, $rawConnectedServiceProvider->getLastAuthenticationAt());
        $this->assertSame(
            $this->lastAuthenticationAt,
            $rawConnectedServiceProvider->getLastAuthenticationAt()->getTimestamp()
        );
        $this->assertInstanceOf(DateTimeImmutable::class, $rawConnectedServiceProvider->getFirstAuthenticationAt());
        $this->assertSame(
            $this->firstAuthenticationAt,
            $rawConnectedServiceProvider->getFirstAuthenticationAt()->getTimestamp()
        );
        $this->assertSame($this->serviceProviderMetadata, $rawConnectedServiceProvider->getServiceProviderMetadata());
        $this->assertSame($this->userAttributes, $rawConnectedServiceProvider->getUserAttributes());
    }

    public function testThrowsIfColumnNotSet(): void
    {
        $rawRow = $this->rawRow;
        unset($rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES]);

        $this->expectException(UnexpectedValueException::class);

        new RawConnectedService($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsIfNumberOfAuthenticationsNotNumeric(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_NUMBER_OF_AUTHENTICATIONS] = 'a';

        $this->expectException(UnexpectedValueException::class);

        new RawConnectedService($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsIfLastAuthenticationAtNotNumeric(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_LAST_AUTHENTICATION_AT] = 'abc';

        $this->expectException(UnexpectedValueException::class);

        new RawConnectedService($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsIfFirstAuthenticationAtNotNumeric(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_FIRST_AUTHENTICATION_AT] = 'abc';

        $this->expectException(UnexpectedValueException::class);

        new RawConnectedService($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsIfSpMetadataNotString(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA] = 1;

        $this->expectException(UnexpectedValueException::class);

        new RawConnectedService($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsIfUserAttributesNotString(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES] = 1;

        $this->expectException(UnexpectedValueException::class);

        new RawConnectedService($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsIfSpMetadataNotValid(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_SP_METADATA] = serialize(1);

        $this->expectException(UnexpectedValueException::class);

        new RawConnectedService($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsIfUserAttributesNotValid(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[EntityTableConstants::ENTITY_CONNECTED_SERVICE_COLUMN_NAME_USER_ATTRIBUTES] = serialize(1);

        $this->expectException(UnexpectedValueException::class);

        new RawConnectedService($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }
}
