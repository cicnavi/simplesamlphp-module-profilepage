<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use PHPUnit\Framework\MockObject\Stub;
use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\Module\accounting\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\accounting\Helpers\Routes;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Utils\HTTP;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\Routes
 * @uses \SimpleSAML\Module\accounting\Helpers\Arr
 */
class RoutesTest extends TestCase
{
    protected const BASE_URL = 'https://example.org/ssp/';
    /**
     * @var Stub
     */
    protected $sspHttpUtilsStub;
    protected string $moduleUrl;

    protected function setUp(): void
    {
        $this->sspHttpUtilsStub = $this->createStub(HTTP::class);
        $this->sspHttpUtilsStub->method('getBaseURL')->willReturn(self::BASE_URL);

        $this->moduleUrl = self::BASE_URL . 'module.php/' . ModuleConfiguration::MODULE_NAME;
    }

    public function testCanGetModuleUrl(): void
    {
        $path = 'sample-path';
        $moduleUrlWithPath = $this->moduleUrl . '/' . $path;

        $moduleRoutesHelper = new Routes($this->sspHttpUtilsStub);

        $this->assertSame($moduleUrlWithPath, $moduleRoutesHelper->getUrl($path));
    }

    public function testCanCallMethodToAddParamsToModuleUrl(): void
    {
        $path = 'sample-path';
        $params = ['sample' => 'param'];
        $fullUrl = 'full-url-with-sample-param';

        $this->sspHttpUtilsStub->method('addURLParameters')->willReturn($fullUrl);
        $moduleRoutesHelper = new Routes($this->sspHttpUtilsStub);

        $this->assertSame($fullUrl, $moduleRoutesHelper->getUrl($path, $params));
    }

    public function testCanAppendFragmentParameters(): void
    {
        $associativeFragments = ['a' => 'b'];
        $indexedFragments = ['a', 'b'];

        $expectedUrlAssociative = self::BASE_URL . 'module.php/' . ModuleConfiguration::MODULE_NAME . '/path#a=b';
        $expectedUrlIndexed = self::BASE_URL . 'module.php/' . ModuleConfiguration::MODULE_NAME . '/path#a&b';

        $this->assertSame(
            $expectedUrlAssociative,
            (new Routes($this->sspHttpUtilsStub))->getUrl('path', [], $associativeFragments)
        );
        $this->assertSame(
            $expectedUrlIndexed,
            (new Routes($this->sspHttpUtilsStub))->getUrl('path', [], $indexedFragments)
        );
    }
}
