<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Helpers;

use Exception;
use SimpleSAML\Module\profilepage\Helpers\SspModule;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\profilepage\Helpers\SspModule
 */
class SspModuleTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testIsEnabled(): void
    {
        // Config file is at tests/config-templates/config.php
        $this->assertFalse((new SspModule())->isEnabled('invalid'));
        $this->assertTrue((new SspModule())->isEnabled('admin'));
    }
}
