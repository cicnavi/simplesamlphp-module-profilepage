<?php

namespace SimpleSAML\Module\accounting\Helpers;

class RandomHelper
{
    public function getRandomInt(int $minimum = PHP_INT_MIN, int $maximum = PHP_INT_MAX): int
    {
        try {
            return random_int($minimum, $maximum);
        } catch (\Throwable $exception) {
            return mt_rand($minimum, $maximum);
        }
    }
}
