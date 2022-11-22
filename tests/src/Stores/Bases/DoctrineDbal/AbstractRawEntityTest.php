<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Stores\Bases\DoctrineDbal;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractRawEntity;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Test\Module\accounting\Constants\DateTime;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractRawEntity
 */
class AbstractRawEntityTest extends TestCase
{
    /**
     * @var AbstractPlatform|AbstractPlatform&\PHPUnit\Framework\MockObject\Stub|\PHPUnit\Framework\MockObject\Stub
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
        /** @psalm-suppress InvalidArgument */
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
        /** @psalm-suppress InvalidArgument */
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

        /** @psalm-suppress InvalidArgument */
        new class ($this->rawRow, $this->abstractPlatformStub) extends AbstractRawEntity {
            protected function validate(
                array $rawRow
            ): void {
                $this->resolveDateTimeImmutable('invalid');
            }
        };
    }
}
