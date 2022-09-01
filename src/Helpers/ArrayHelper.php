<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

class ArrayHelper
{
    public static function recursivelySortByKey(array &$array): void
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($array as &$value) {
            if (is_array($value)) {
                self::recursivelySortByKey($value);
            }
        }

        ksort($array);
    }

    public static function getNewRecursivelySortedByKey(array $array): array
    {
        self::recursivelySortByKey($array);
        return $array;
    }
}
