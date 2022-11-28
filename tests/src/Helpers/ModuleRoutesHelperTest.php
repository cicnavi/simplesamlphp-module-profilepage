<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use PHPUnit\Framework\MockObject\Stub;
use SimpleSAML\Module\accounting\Helpers\ModuleRoutesHelper;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Utils\HTTP;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\ModuleRoutesHelper
 */
class ModuleRoutesHelperTest extends TestCase
{
    protected const BASE_URL = 'https://example.org/ssp/';
    /**
     * @var Stub|HTTP
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

        /** @psalm-suppress PossiblyInvalidArgument */
        $moduleRoutesHelper = new ModuleRoutesHelper($this->sspHttpUtilsStub);

        $this->assertSame($moduleUrlWithPath, $moduleRoutesHelper->getUrl($path));
    }

    public function testCanCallMethodToAddParamsToModuleUrl(): void
    {
        $path = 'sample-path';
        $params = ['sample' => 'param'];
        $fullUrl = 'full-url-with-sample-param';

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->sspHttpUtilsStub->method('addURLParameters')->willReturn($fullUrl);
        /** @psalm-suppress PossiblyInvalidArgument */
        $moduleRoutesHelper = new ModuleRoutesHelper($this->sspHttpUtilsStub);

        $this->assertSame($fullUrl, $moduleRoutesHelper->getUrl($path, $params));
    }
}
