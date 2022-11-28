<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

use Throwable;

class RandomHelper
{
    public function getRandomInt(int $minimum = PHP_INT_MIN, int $maximum = PHP_INT_MAX): int
    {
        try {
            return random_int($minimum, $maximum);
            // @codeCoverageIgnoreStart
        } catch (Throwable $exception) {
            return mt_rand($minimum, $maximum);
            // @codeCoverageIgnoreEnd
        }
    }
}
