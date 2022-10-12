<?php

namespace SimpleSAML\Module\accounting\Helpers;

class DateTimeHelper
{
    /**
     * Convert date interval to seconds, interval being minimum 1 second.
     * @param \DateInterval $dateInterval Minimum is 1 second.
     * @return int
     */
    public static function convertDateIntervalToSeconds(\DateInterval $dateInterval): int
    {
        $reference = new \DateTimeImmutable();
        $endTime = $reference->add($dateInterval);

        $duration = $endTime->getTimestamp() - $reference->getTimestamp();

        if ($duration < 1) {
            $duration = 1;
        }

        return $duration;
    }
}
