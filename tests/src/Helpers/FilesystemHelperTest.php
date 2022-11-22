<?php

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Exceptions\InvalidValueException;
use SimpleSAML\Module\accounting\Helpers\FilesystemHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\FilesystemHelper
 */
class FilesystemHelperTest extends TestCase
{
    public function testCanGetRealPath(): void
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . '..';

        $realPath = (new FilesystemHelper())->getRealPath($path);

        $this->assertSame(dirname(__DIR__), $realPath);
    }

    public function testGetRealPathThrowsOnInvalidPaths(): void
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'invalid';

        $this->expectException(InvalidValueException::class);

        (new FilesystemHelper())->getRealPath($path);
    }
}
