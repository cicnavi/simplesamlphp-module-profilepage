<?php

namespace SimpleSAML\Test\Module\accounting;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module\accounting\ModuleConfiguration;

/**
 * @covers \SimpleSAML\Module\accounting\ModuleConfiguration
 */
class ModuleConfigurationTest extends TestCase
{
    public function testSample(): void
    {
        $moduleConfiguration = new ModuleConfiguration('module_accounting_basic.php');

        $this->assertInstanceOf(Configuration::class, $moduleConfiguration->getConfiguration());
    }
}
