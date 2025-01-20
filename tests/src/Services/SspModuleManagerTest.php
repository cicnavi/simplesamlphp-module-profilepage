<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Services;

use Exception;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Helpers\SspModule;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Module\profilepage\Services\SspModuleManager;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\SspModule\Oidc;
use Throwable;

/**
 * @covers \SimpleSAML\Module\profilepage\Services\SspModuleManager
 * @uses \SimpleSAML\Module\profilepage\SspModule\Oidc
 */
class SspModuleManagerTest extends TestCase
{
    protected Stub $loggerStub;
    protected Stub $helpersMangerStub;
    protected Stub $sspModuleStub;

    protected function setUp(): void
    {
        $this->loggerStub = $this->createStub(LoggerInterface::class);
        $this->helpersMangerStub = $this->createStub(HelpersManager::class);
        $this->sspModuleStub = $this->createStub(SspModule::class);

        $this->helpersMangerStub->method('getSspModule')->willReturn($this->sspModuleStub);
    }

    protected function sut(
        ?LoggerInterface $logger = null,
        ?HelpersManager $helpersManager = null,
    ): SspModuleManager {
        $logger = $logger ?? $this->loggerStub;
        $helpersManager = $helpersManager ?? $this->helpersMangerStub;

        return new SspModuleManager($logger, $helpersManager);
    }

    /**
     * @throws Exception
     */
    public function testReturnsNullIfOidcNotEnabled(): void
    {
        $this->sspModuleStub->method('isEnabled')
            ->willReturn(false);

        $this->assertNull($this->sut()->getOidc());
    }

    public function testCanBuildOidcModuleWrapper(): void
    {
        $this->sspModuleStub->method('isEnabled')->willReturn(true);

        // By default, OIDC module will try to connect to the database, which we will not mock in module manager.
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Database');

        $this->sut()->getOidc();
    }
}
