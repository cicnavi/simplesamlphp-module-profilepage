<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\RawActivity;
use SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Saml2;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Interfaces\SerializerInterface;
use SimpleSAML\Test\Module\accounting\Constants\DateTime;
use SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\EntityTableConstants;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Accounting\Activity\DoctrineDbal\RawActivity
 * @uses \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity
 */
class RawActivityTest extends TestCase
{
    /**
     * @var string[]
     */
    protected array $serviceProviderMetadata;
    /**
     * @var string[]
     */
    protected array $userAttributes;
    protected int $happenedAt;
    protected string $clientIpAddress;

    protected array $rawRow;
    /**
     * @var Stub
     */
    protected $abstractPlatformStub;
    protected string $authenticationProtocolDesignation;
    protected MockObject $serializerMock;

    protected function setUp(): void
    {
        $this->serviceProviderMetadata = ['sp' => 'metadata'];
        $this->userAttributes = ['user' => 'attribute'];
        $this->happenedAt = 1645564942;
        $this->clientIpAddress = '123.123.123.123';
        $this->authenticationProtocolDesignation = Saml2::DESIGNATION;

        $this->rawRow = [
            EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA => serialize($this->serviceProviderMetadata),
            EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES => serialize($this->userAttributes),
            EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT => $this->happenedAt,
            EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_CLIENT_IP_ADDRESS => $this->clientIpAddress,
            EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION =>
                $this->authenticationProtocolDesignation,
        ];
        $this->abstractPlatformStub = $this->createStub(AbstractPlatform::class);
        $this->abstractPlatformStub->method('getDateTimeFormatString')
            ->willReturn(DateTime::DEFAULT_FORMAT);

        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->serializerMock->method('do')->willReturnCallback(
            fn($argument) => serialize($argument)
        );
        $this->serializerMock->method('undo')->willReturnCallback(
            fn($argument) => unserialize($argument)
        );
    }

    public function testCanCreateInstance(): void
    {
        $rawActivity = new RawActivity($this->rawRow, $this->abstractPlatformStub, $this->serializerMock);

        $this->assertInstanceOf(
            RawActivity::class,
            $rawActivity
        );
    }

    public function testCanGetProperties(): void
    {
        $rawActivity = new RawActivity($this->rawRow, $this->abstractPlatformStub, $this->serializerMock);

        $this->assertInstanceOf(DateTimeImmutable::class, $rawActivity->getHappenedAt());
        $this->assertSame($this->serviceProviderMetadata, $rawActivity->getServiceProviderMetadata());
        $this->assertSame($this->userAttributes, $rawActivity->getUserAttributes());
        $this->assertSame($this->clientIpAddress, $rawActivity->getClientIpAddress());
        $this->assertSame(
            $this->authenticationProtocolDesignation,
            $rawActivity->getAuthenticationProtocolDesignation()
        );
    }

    public function testIpAddressCanBeNull(): void
    {
        $rawRow = $this->rawRow;
        unset($rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_CLIENT_IP_ADDRESS]);

        $rawActivity = new RawActivity($rawRow, $this->abstractPlatformStub, $this->serializerMock);
        $this->assertNull($rawActivity->getClientIpAddress());
    }

    public function testAuthenticationProtocolDesignationCanBeNull(): void
    {
        $rawRow = $this->rawRow;
        unset($rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_AUTHENTICATION_PROTOCOL_DESIGNATION]);

        $rawActivity = new RawActivity($rawRow, $this->abstractPlatformStub, $this->serializerMock);
        $this->assertNull($rawActivity->getAuthenticationProtocolDesignation());
    }

    public function testThrowsIfColumnNotPresent(): void
    {
        $rawRow = $this->rawRow;
        unset($rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT]);

        $this->expectException(UnexpectedValueException::class);

        new RawActivity($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsForNonStringServiceProviderMetadata(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA] = 1;

        $this->expectException(UnexpectedValueException::class);

        new RawActivity($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsForNonStringUserAttributes(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES] = 1;

        $this->expectException(UnexpectedValueException::class);

        new RawActivity($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsForNonNumericHappenedAt(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT] = 'abc';

        $this->expectException(UnexpectedValueException::class);

        new RawActivity($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsForInvalidServiceProviderMetadata(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA] = serialize(1);

        $this->expectException(UnexpectedValueException::class);

        new RawActivity($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsForInvalidUserAttributes(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[EntityTableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES] = serialize(1);

        $this->expectException(UnexpectedValueException::class);

        new RawActivity($rawRow, $this->abstractPlatformStub, $this->serializerMock);
    }
}
