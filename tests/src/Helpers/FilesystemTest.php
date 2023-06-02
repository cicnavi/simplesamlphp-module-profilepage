<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Exceptions\InvalidValueException;
use SimpleSAML\Module\accounting\Helpers\Filesystem;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\Filesystem
 */
class FilesystemTest extends TestCase
{
    public function testCanGetRealPath(): void
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . '..';

        $realPath = (new Filesystem())->getRealPath($path);

        $this->assertSame(dirname(__DIR__), $realPath);
    }

    public function testGetRealPathThrowsOnInvalidPaths(): void
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'invalid';

        $this->expectException(InvalidValueException::class);

        (new Filesystem())->getRealPath($path);
    }
}
