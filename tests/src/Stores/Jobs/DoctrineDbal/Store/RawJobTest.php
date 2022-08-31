<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Jobs\DoctrineDbal\Store;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\RawJob;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store\RawJob
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\State
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractRawEntity
 */
class RawJobTest extends TestCase
{
    protected Event $authenticationEvent;
    protected array $validRawRow;
    protected \PHPUnit\Framework\MockObject\Stub $abstractPlatformStub;

    protected function setUp(): void
    {
        $this->abstractPlatformStub = $this->createStub(AbstractPlatform::class);
        $this->authenticationEvent = new Event(new State(StateArrays::FULL));
        $this->validRawRow = [
            Store\TableConstants::COLUMN_NAME_ID => 1,
            Store\TableConstants::COLUMN_NAME_PAYLOAD => serialize($this->authenticationEvent),
            Store\TableConstants::COLUMN_NAME_TYPE => get_class($this->authenticationEvent),
            Store\TableConstants::COLUMN_NAME_CREATED_AT => '2022-08-17 13:26:12',
        ];
    }

    public function testCanInstantiateValidRawJob(): void
    {
        $abstractPlatform = new SqlitePlatform();
        $rawJob = new Store\RawJob($this->validRawRow, $abstractPlatform);
        $this->assertSame($rawJob->getId(), $this->validRawRow[Store\TableConstants::COLUMN_NAME_ID]);
        $this->assertEquals($rawJob->getPayload(), $this->authenticationEvent);
        $this->assertSame($rawJob->getType(), $this->validRawRow[Store\TableConstants::COLUMN_NAME_TYPE]);
        $this->assertInstanceOf(\DateTimeImmutable::class, $rawJob->getCreatedAt());
    }

    public function testThrowsOnEmptyColumn(): void
    {
        $invalidRawRow = $this->validRawRow;
        unset($invalidRawRow[Store\TableConstants::COLUMN_NAME_ID]);

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new RawJob($invalidRawRow, $this->abstractPlatformStub);
    }

    public function testThrowsOnNonNumericId(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[Store\TableConstants::COLUMN_NAME_ID] = 'a';

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new RawJob($invalidRawRow, $this->abstractPlatformStub);
    }

    public function testThrowsOnNonStringPayload(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[Store\TableConstants::COLUMN_NAME_PAYLOAD] = 123;

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new RawJob($invalidRawRow, $this->abstractPlatformStub);
    }

    public function testThrowsOnNonAbstractPayload(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[Store\TableConstants::COLUMN_NAME_PAYLOAD] = serialize('abc');

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new Store\RawJob($invalidRawRow, $this->abstractPlatformStub);
    }

    public function testThrowsOnNonStringType(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[Store\TableConstants::COLUMN_NAME_TYPE] = 123;

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new Store\RawJob($invalidRawRow, $this->abstractPlatformStub);
    }

    public function testThrowsOnNonStringCreatedAt(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[Store\TableConstants::COLUMN_NAME_CREATED_AT] = 123;

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new RawJob($invalidRawRow, $this->abstractPlatformStub);
    }

    public function testThrowsOnNonValidCreatedAt(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[Store\TableConstants::COLUMN_NAME_CREATED_AT] = '123';

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new Store\RawJob($invalidRawRow, $this->abstractPlatformStub);
    }
}
