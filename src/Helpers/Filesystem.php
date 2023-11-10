<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Helpers;

use SimpleSAML\Module\profilepage\Exceptions\InvalidValueException;

class Filesystem
{
    public function getRealPath(string $path): string
    {
        $realpath = realpath($path);

        if ($realpath === false || ! (is_dir($realpath) || is_file($realpath))) {
            throw new InvalidValueException(sprintf('Given path can not be translated to real path (%s).', $path));
        }

        return $realpath;
    }
}
