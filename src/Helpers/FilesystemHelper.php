<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Exceptions\InvalidValueException;

class FilesystemHelper
{
    public static function getRealPath(string $path): string
    {
        $path = realpath($path);

        if ($path === false || ! (is_dir($path) || is_file($path))) {
            throw new InvalidValueException('Given path can not be translated to real path.');
        }

        return $path;
    }
}
