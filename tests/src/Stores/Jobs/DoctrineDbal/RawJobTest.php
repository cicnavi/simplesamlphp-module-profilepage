<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Jobs\DoctrineDbal;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use SimpleSAML\Module\accounting\Entities\AuthenticationEvent;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\JobsStore;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\RawJob;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\RawJob
 * @uses \SimpleSAML\Module\accounting\Entities\AuthenticationEvent
 */
class RawJobTest extends TestCase
{
    protected AuthenticationEvent $authenticationEvent;
    protected array $validRawRow;
    protected \PHPUnit\Framework\MockObject\Stub $abstractPlatformStub;

    protected function setUp(): void
    {
        $this->abstractPlatformStub = $this->createStub(AbstractPlatform::class);
        $this->authenticationEvent = new AuthenticationEvent(['sample' => 'state']);
        $this->validRawRow = [
            JobsStore::COLUMN_NAME_ID => 1,
            JobsStore::COLUMN_NAME_PAYLOAD => serialize($this->authenticationEvent),
            JobsStore::COLUMN_NAME_TYPE => get_class($this->authenticationEvent),
            JobsStore::COLUMN_NAME_CREATED_AT => '2022-08-17 13:26:12',
        ];
    }

    public function testCanInstantiateValidRawJob(): void
    {
        $abstractPlatform = new SqlitePlatform();
        $rawJob = new RawJob($this->validRawRow, $abstractPlatform);
        $this->assertSame($rawJob->getId(), $this->validRawRow[JobsStore::COLUMN_NAME_ID]);
        $this->assertEquals($rawJob->getPayload(), $this->authenticationEvent);
        $this->assertSame($rawJob->getType(), $this->validRawRow[JobsStore::COLUMN_NAME_TYPE]);
        $this->assertInstanceOf(\DateTimeImmutable::class, $rawJob->getCreatedAt());
    }

    public function testThrowsOnEmptyColumn(): void
    {
        $invalidRawRow = $this->validRawRow;
        unset($invalidRawRow[JobsStore::COLUMN_NAME_ID]);

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new RawJob($invalidRawRow, $this->abstractPlatformStub);
    }

    public function testThrowsOnNonNumericId(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[JobsStore::COLUMN_NAME_ID] = 'a';

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new RawJob($invalidRawRow, $this->abstractPlatformStub);
    }

    public function testThrowsOnNonStringPayload(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[JobsStore::COLUMN_NAME_PAYLOAD] = 123;

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new RawJob($invalidRawRow, $this->abstractPlatformStub);
    }

    public function testThrowsOnNonAbstractPayload(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[JobsStore::COLUMN_NAME_PAYLOAD] = serialize('abc');

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new RawJob($invalidRawRow, $this->abstractPlatformStub);
    }

    public function testThrowsOnNonStringType(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[JobsStore::COLUMN_NAME_TYPE] = 123;

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new RawJob($invalidRawRow, $this->abstractPlatformStub);
    }

    public function testThrowsOnNonStringCreatedAt(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[JobsStore::COLUMN_NAME_CREATED_AT] = 123;

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new RawJob($invalidRawRow, $this->abstractPlatformStub);
    }

    public function testThrowsOnNonValidCreatedAt(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[JobsStore::COLUMN_NAME_CREATED_AT] = '123';

        $this->expectException(UnexpectedValueException::class);

        /** @psalm-suppress InvalidArgument */
        new RawJob($invalidRawRow, $this->abstractPlatformStub);
    }
}
