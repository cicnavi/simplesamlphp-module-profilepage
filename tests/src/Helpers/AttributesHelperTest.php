<?php

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Helpers\AttributesHelper;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\ModuleConfiguration;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\AttributesHelper
 * @uses \SimpleSAML\Module\accounting\ModuleConfiguration
 */
class AttributesHelperTest extends TestCase
{
    /**
     * @var string $sspBaseDir Simulated SSP base directory.
     */
    protected string $sspBaseDir;

    /**
     * @var string[]
     */
    protected array $mapFiles;

    protected function setUp(): void
    {
        $this->sspBaseDir = (new ModuleConfiguration())->getModuleRootDirectory() . DIRECTORY_SEPARATOR . 'tests' .
            DIRECTORY_SEPARATOR;

        $this->mapFiles = ['test.php', 'test2.php'];
    }

    public function testCanLoadAttributeMaps(): void
    {
        $fullAttributeMap = AttributesHelper::getMergedAttributeMapForFiles($this->sspBaseDir, $this->mapFiles);

        $this->assertArrayHasKey('mobile', $fullAttributeMap);
        $this->assertArrayHasKey('phone', $fullAttributeMap);
    }

    public function testIgnoresNonExistentMaps(): void
    {
        $fullAttributeMap = AttributesHelper::getMergedAttributeMapForFiles($this->sspBaseDir, ['invalid.php']);

        $this->assertEmpty($fullAttributeMap);
    }
}
