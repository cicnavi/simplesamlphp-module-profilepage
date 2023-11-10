<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Services;

use Exception;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Module\profilepage\Services\SspModuleManager;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * @covers \SimpleSAML\Module\profilepage\Services\SspModuleManager
 * @uses \SimpleSAML\Module\profilepage\SspModule\Oidc
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

    /**
     * @throws Exception
     */
    public function testGet(): void
    {
        $sspModuleManager = new SspModuleManager($this->loggerStub, $this->helpersMangerStub);

        // By default, OIDC module will try to connect to the database, which we will not mock in module manager.
        $this->expectException(Throwable::class);
        $sspModuleManager->getOidc();
    }
}
