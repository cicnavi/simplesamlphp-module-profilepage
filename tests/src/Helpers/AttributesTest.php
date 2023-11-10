<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Helpers;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\HelpersManager;

/**
 * @covers \SimpleSAML\Module\profilepage\Helpers\Attributes
 * @uses \SimpleSAML\Module\profilepage\ModuleConfiguration
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 */
class AttributesTest extends TestCase
{
    /**
     * @var string $sspBaseDir Simulated SSP base directory.
     */
    protected string $sspBaseDir;

    /**
     * @var string[]
     */
    protected array $mapFiles;
    protected HelpersManager $helpersManager;

    protected function setUp(): void
    {
        $this->sspBaseDir = (new ModuleConfiguration())->getModuleRootDirectory() . DIRECTORY_SEPARATOR . 'tests' .
            DIRECTORY_SEPARATOR;

        $this->mapFiles = ['test.php', 'test2.php'];

        $this->helpersManager = new HelpersManager();
    }

    public function testCanLoadAttributeMaps(): void
    {
        $fullAttributeMap = $this->helpersManager->getAttributes()
            ->getMergedAttributeMapForFiles($this->sspBaseDir, $this->mapFiles);

        $this->assertArrayHasKey('mobile', $fullAttributeMap);
        $this->assertArrayHasKey('phone', $fullAttributeMap);
    }

    public function testIgnoresNonExistentMaps(): void
    {
        $fullAttributeMap = $this->helpersManager->getAttributes()
            ->getMergedAttributeMapForFiles($this->sspBaseDir, ['invalid.php']);

        $this->assertEmpty($fullAttributeMap);
    }
}
