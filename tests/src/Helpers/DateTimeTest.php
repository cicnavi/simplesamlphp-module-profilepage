<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use DateInterval;
use DateTimeImmutable;
use SimpleSAML\Module\accounting\Helpers\DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\DateTime
 */
class DateTimeTest extends TestCase
{
    public function testCanConvertDateIntervalToSeconds(): void
    {
        $interval = new DateInterval('PT10S');

        $this->assertSame(10, (new DateTime())->convertDateIntervalToSeconds($interval));
    }

    public function testMinimumIntervalIsOneSecond(): void
    {
        $interval = DateInterval::createFromDateString('-10 seconds'); // Negative interval

        $this->assertSame(1, (new DateTime())->convertDateIntervalToSeconds($interval));
    }

    public function testToFormattedString(): void
    {
        $dateTime = new DateTimeImmutable();

        $this->assertSame(
            $dateTime->format(DateTime::FORMAT_MYSQL),
            (new DateTime())->toFormattedString($dateTime)
        );
    }
}
