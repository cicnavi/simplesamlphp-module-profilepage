<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\RawJob;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\TableConstants;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event\State;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;
use SimpleSAML\Test\Module\profilepage\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store\RawJob
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity
 * @uses \SimpleSAML\Module\profilepage\Helpers\Network
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 */
class RawJobTest extends TestCase
{
    protected Event $authenticationEvent;
    protected array $validRawRow;
    protected Stub $abstractPlatformStub;
    protected MockObject $serializerMock;

    protected function setUp(): void
    {
        $this->abstractPlatformStub = $this->createStub(AbstractPlatform::class);

        $this->abstractPlatformStub->method('getDateTimeFormatString')
            ->willReturn('YYYY-MM-DD HH:MM:SS');

        $this->authenticationEvent = new Event(new State\Saml2(StateArrays::SAML2_FULL));
        $this->validRawRow = [
            TableConstants::COLUMN_NAME_ID => 1,
            TableConstants::COLUMN_NAME_PAYLOAD => serialize(StateArrays::SAML2_FULL),
            TableConstants::COLUMN_NAME_TYPE => $this->authenticationEvent::class,
            TableConstants::COLUMN_NAME_CREATED_AT => 1_645_564_942,
        ];

        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->serializerMock->method('undo')->will($this->returnCallback(
            fn($argument) => unserialize($argument)
        ));
    }

    public function testCanInstantiateValidRawJob(): void
    {
        $abstractPlatform = new SqlitePlatform();
        $rawJob = new RawJob($this->validRawRow, $abstractPlatform, $this->serializerMock);
        $this->assertSame($rawJob->getId(), $this->validRawRow[TableConstants::COLUMN_NAME_ID]);
        $this->assertEquals(StateArrays::SAML2_FULL, $rawJob->getPayload());
        $this->assertSame($rawJob->getType(), $this->validRawRow[TableConstants::COLUMN_NAME_TYPE]);
        $this->assertInstanceOf(DateTimeImmutable::class, $rawJob->getCreatedAt());
    }

    public function testThrowsOnEmptyColumn(): void
    {
        $invalidRawRow = $this->validRawRow;
        unset($invalidRawRow[TableConstants::COLUMN_NAME_ID]);

        $this->expectException(UnexpectedValueException::class);

        new RawJob($invalidRawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsOnNonNumericId(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[TableConstants::COLUMN_NAME_ID] = 'a';

        $this->expectException(UnexpectedValueException::class);

        new RawJob($invalidRawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsOnNonStringPayload(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[TableConstants::COLUMN_NAME_PAYLOAD] = 123;

        $this->expectException(UnexpectedValueException::class);

        new RawJob($invalidRawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsOnNonAbstractPayload(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[TableConstants::COLUMN_NAME_PAYLOAD] = serialize('abc');

        $this->expectException(UnexpectedValueException::class);

        new RawJob($invalidRawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsOnNonStringType(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[TableConstants::COLUMN_NAME_TYPE] = 123;

        $this->expectException(UnexpectedValueException::class);

        new RawJob($invalidRawRow, $this->abstractPlatformStub, $this->serializerMock);
    }

    public function testThrowsOnNonNumericCreatedAt(): void
    {
        $invalidRawRow = $this->validRawRow;
        $invalidRawRow[TableConstants::COLUMN_NAME_CREATED_AT] = 'abc';

        $this->expectException(UnexpectedValueException::class);

        new RawJob($invalidRawRow, $this->abstractPlatformStub, $this->serializerMock);
    }
}
