<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class DateTime
{
    public const FORMAT_MYSQL = 'Y-m-d H:i:s';
    /**
     * Convert date interval to seconds, interval being minimum 1 second.
     * @param DateInterval $dateInterval Minimum is 1 second.
     * @return int
     */
    public function convertDateIntervalToSeconds(DateInterval $dateInterval): int
    {
        $reference = new DateTimeImmutable();
        $endTime = $reference->add($dateInterval);

        $duration = $endTime->getTimestamp() - $reference->getTimestamp();

        if ($duration < 1) {
            $duration = 1;
        }

        return $duration;
    }

    public function toFormattedString(
        DateTimeInterface $dateTime = null,
        string $format = self::FORMAT_MYSQL
    ): string {
        $dateTime ??= new DateTimeImmutable();

        return $dateTime->format($format);
    }
}
