<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\MockObject\Stub;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\RawActivity;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\TableConstants;
use SimpleSAML\Test\Module\accounting\Constants\DateTime;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\RawActivity
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractRawEntity
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
    protected string $happenedAt;
    protected string $clientIpAddress;

    protected array $rawRow;
    /**
     * @var AbstractPlatform|Stub
     */
    protected $abstractPlatformStub;

    protected function setUp(): void
    {
        $this->serviceProviderMetadata = ['sp' => 'metadata'];
        $this->userAttributes = ['user' => 'attribute'];
        $this->happenedAt = '2022-02-22 22:22:22';
        $this->clientIpAddress = '123.123.123.123';

        $this->rawRow = [
            TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA => serialize($this->serviceProviderMetadata),
            TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES => serialize($this->userAttributes),
            TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT => $this->happenedAt,
            TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_CLIENT_IP_ADDRESS => $this->clientIpAddress,
        ];
        $this->abstractPlatformStub = $this->createStub(AbstractPlatform::class);
        $this->abstractPlatformStub->method('getDateTimeFormatString')->willReturn(DateTime::DEFAULT_FORMAT);
    }

    public function testCanCreateInstance(): void
    {
        /** @psalm-suppress PossiblyInvalidArgument */
        $rawActivity = new RawActivity($this->rawRow, $this->abstractPlatformStub);

        $this->assertInstanceOf(RawActivity::class, $rawActivity);
    }

    public function testCanGetProperties(): void
    {
        /** @psalm-suppress PossiblyInvalidArgument */
        $rawActivity = new RawActivity($this->rawRow, $this->abstractPlatformStub);

        $this->assertInstanceOf(DateTimeImmutable::class, $rawActivity->getHappenedAt());
        $this->assertSame($this->serviceProviderMetadata, $rawActivity->getServiceProviderMetadata());
        $this->assertSame($this->userAttributes, $rawActivity->getUserAttributes());
        $this->assertSame($this->clientIpAddress, $rawActivity->getClientIpAddress());
    }

    public function testIpAddressCanBeMissing(): void
    {
        $rawRow = $this->rawRow;
        unset($rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_CLIENT_IP_ADDRESS]);

        /** @psalm-suppress PossiblyInvalidArgument */
        $rawActivity = new RawActivity($rawRow, $this->abstractPlatformStub);
        $this->assertNull($rawActivity->getClientIpAddress());
    }

    public function testThrowsIfColumnNotPresent(): void
    {
        $rawRow = $this->rawRow;
        unset($rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT]);

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawActivity($rawRow, $this->abstractPlatformStub);
    }

    public function testThrowsForNonStringServiceProviderMetadata(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA] = 1;

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawActivity($rawRow, $this->abstractPlatformStub);
    }

    public function testThrowsForNonStringUserAttributes(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES] = 1;

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawActivity($rawRow, $this->abstractPlatformStub);
    }

    public function testThrowsForNonStringHappenedAt(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT] = 1;

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawActivity($rawRow, $this->abstractPlatformStub);
    }

    public function testThrowsForInvalidServiceProviderMetadata(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA] = serialize(1);

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawActivity($rawRow, $this->abstractPlatformStub);
    }

    public function testThrowsForInvalidUserAttributes(): void
    {
        $rawRow = $this->rawRow;
        $rawRow[TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES] = serialize(1);

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        new RawActivity($rawRow, $this->abstractPlatformStub);
    }
}
