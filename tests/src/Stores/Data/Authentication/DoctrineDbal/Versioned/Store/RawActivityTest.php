<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\RawActivity;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\TableConstants;

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
    protected array $rawRow;
    /**
     * @var AbstractPlatform|AbstractPlatform&\PHPUnit\Framework\MockObject\Stub|\PHPUnit\Framework\MockObject\Stub
     */
    protected $abstractPlatformStub;

    protected function setUp(): void
    {
        $this->serviceProviderMetadata = ['sp' => 'metadata'];
        $this->userAttributes = ['user' => 'attribute'];
        $this->happenedAt = '2022-02-22 22:22:22';
        $this->rawRow = [
            TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_SP_METADATA => serialize($this->serviceProviderMetadata),
            TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_USER_ATTRIBUTES => serialize($this->userAttributes),
            TableConstants::ENTITY_ACTIVITY_COLUMN_NAME_HAPPENED_AT => $this->happenedAt,
        ];
        $this->abstractPlatformStub = $this->createStub(AbstractPlatform::class);
        $this->abstractPlatformStub->method('getDateTimeFormatString')->willReturn('Y-m-d H:i:s');
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

        $this->assertInstanceOf(\DateTimeImmutable::class, $rawActivity->getHappenedAt());
        $this->assertSame($this->serviceProviderMetadata, $rawActivity->getServiceProviderMetadata());
        $this->assertSame($this->userAttributes, $rawActivity->getUserAttributes());
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
