<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

use Throwable;

class Random
{
    public function getInt(int $minimum = PHP_INT_MIN, int $maximum = PHP_INT_MAX): int
    {
        try {
            return random_int($minimum, $maximum);
            // @codeCoverageIgnoreStart
        } catch (Throwable $exception) {
            return mt_rand($minimum, $maximum);
            // @codeCoverageIgnoreEnd
        }
    }

    public function getString(int $length = 16): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[$this->getInt(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
