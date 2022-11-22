<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Helpers\DateTimeHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\DateTimeHelper
 */
class DateTimeHelperTest extends TestCase
{
    public function testCanConvertDateIntervalToSeconds(): void
    {
        $interval = new \DateInterval('PT10S');

        $this->assertSame(10, (new DateTimeHelper())->convertDateIntervalToSeconds($interval));
    }

    public function testMinimumIntervalIsOneSecond(): void
    {
        $interval = \DateInterval::createFromDateString('-10 seconds'); // Negative interval

        $this->assertSame(1, (new DateTimeHelper())->convertDateIntervalToSeconds($interval));
    }
}
