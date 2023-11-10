<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Bases\DoctrineDbal;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;
use SimpleSAML\Test\Module\profilepage\Constants\DateTime;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity
 */
class AbstractRawEntityTest extends TestCase
{
    /**
     * @var Stub
     */
    protected $abstractPlatformStub;
    /**
     * @var string[]
     */
    protected array $rawRow;
    protected MockObject $serializerMock;

    protected function setUp(): void
    {
        $this->abstractPlatformStub = $this->createStub(AbstractPlatform::class);
        $this->abstractPlatformStub->method('getDateTimeFormatString')
            ->willReturn(DateTime::DEFAULT_FORMAT);
        $this->rawRow = ['sample' => 'test'];
        $this->serializerMock = $this->createMock(SerializerInterface::class);
    }

    public function testCanCreateInstance(): void
    {
        $rawEntityInstance = new class (
            $this->rawRow,
            $this->abstractPlatformStub,
            $this->serializerMock
        ) extends AbstractRawEntity {
            protected function validate(
                array $rawRow
            ): void {
            }
        };
        $this->assertInstanceOf(AbstractRawEntity::class, $rawEntityInstance);
    }

    public function testCanResolveDateTimeImmutable(): void
    {
        $rawEntityInstance = new class (
            $this->rawRow,
            $this->abstractPlatformStub,
            $this->serializerMock
        ) extends AbstractRawEntity {
            protected function validate(
                array $rawRow
            ): void {
                $this->resolveDateTimeImmutable(1696014686);
            }
        };

        $this->assertInstanceOf(AbstractRawEntity::class, $rawEntityInstance);
    }
}
