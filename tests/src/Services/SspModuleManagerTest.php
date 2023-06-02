<?php

namespace SimpleSAML\Test\Module\accounting\Services;

use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Module\accounting\Services\SspModuleManager;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\SspModule\Oidc;

/**
 * @covers \SimpleSAML\Module\accounting\Services\SspModuleManager
 * @uses \SimpleSAML\Module\accounting\SspModule\Oidc
 */
class SspModuleManagerTest extends TestCase
{
    protected Stub $loggerStub;
    protected Stub $helpersMangerStub;

    protected function setUp(): void
    {
        $this->loggerStub = $this->createStub(LoggerInterface::class);
        $this->helpersMangerStub = $this->createStub(HelpersManager::class);
    }

    public function testGet(): void
    {
        $sspModuleManager = new SspModuleManager($this->loggerStub, $this->helpersMangerStub);

        // By default, OIDC module will try to connect to the database, which we will not mock in module manager.
        $this->expectException(\Throwable::class);
        $sspModuleManager->getOidc();
    }
}
