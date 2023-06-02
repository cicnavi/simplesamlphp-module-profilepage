<?php

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Helpers\SspModule;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\SspModule
 */
class SspModuleTest extends TestCase
{
    public function testIsEnabled(): void
    {
        // Config file is at tests/config-templates/config.php
        $this->assertFalse((new SspModule())->isEnabled('invalid'));
        $this->assertTrue((new SspModule())->isEnabled('admin'));
    }
}
