<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Helpers;

use Exception;
use Throwable;

class Random
{
    /**
     * @throws Exception
     */
    public function getInt(int $minimum = PHP_INT_MIN, int $maximum = PHP_INT_MAX): int
    {
        try {
            return random_int($minimum, $maximum);
            // @codeCoverageIgnoreStart
        } catch (Throwable) {
            return random_int($minimum, $maximum);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @throws Exception
     */
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
