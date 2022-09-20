<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

class HashHelper
{
    public static function getSha256(string $data): string
    {
        return hash('sha256', $data);
    }

    public static function getSha256ForArray(array $array): string
    {
        ArrayHelper::recursivelySortByKey($array);
        return self::getSha256(serialize($array));
    }
}
