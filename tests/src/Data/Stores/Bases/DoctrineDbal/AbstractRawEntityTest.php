<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Data\Stores\Bases\DoctrineDbal;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Test\Module\accounting\Constants\DateTime;

/**
 * @covers \SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity
 */
class AbstractRawEntityTest extends TestCase
{
    /**
     * @var AbstractPlatform|Stub
     */
    protected $abstractPlatformStub;
    /**
     * @var string[]
     */
    protected array $rawRow;

    protected function setUp(): void
    {
        $this->abstractPlatformStub = $this->createStub(AbstractPlatform::class);
        $this->abstractPlatformStub->method('getDateTimeFormatString')
            ->willReturn(DateTime::DEFAULT_FORMAT);
        $this->rawRow = ['sample' => 'test'];
    }

    public function testCanCreateInstance(): void
    {
        $rawEntityInstance = new class ($this->rawRow, $this->abstractPlatformStub) extends AbstractRawEntity {
            protected function validate(
                array $rawRow
            ): void {
            }
        };
        $this->assertInstanceOf(AbstractRawEntity::class, $rawEntityInstance);
    }

    public function testCanResolveDateTimeImmutable(): void
    {
        $rawEntityInstance = new class ($this->rawRow, $this->abstractPlatformStub) extends AbstractRawEntity {
            protected function validate(
                array $rawRow
            ): void {
                $this->resolveDateTimeImmutable('2022-09-21 14:49:20');
            }
        };

        $this->assertInstanceOf(AbstractRawEntity::class, $rawEntityInstance);
    }

    public function testThrowsForInvalidDateTime(): void
    {
        $this->expectException(UnexpectedValueException::class);

        new class ($this->rawRow, $this->abstractPlatformStub) extends AbstractRawEntity {
            protected function validate(
                array $rawRow
            ): void {
                $this->resolveDateTimeImmutable('invalid');
            }
        };
    }
}