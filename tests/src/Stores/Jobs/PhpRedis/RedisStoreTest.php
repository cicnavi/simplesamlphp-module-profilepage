<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Stores\Jobs\PhpRedis;

use Psr\Log\LoggerInterface;
use Redis;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Jobs\PhpRedis\RedisStore;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\PhpRedis\RedisStore
 */
class RedisStoreTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|ModuleConfiguration
     */
    protected $moduleConfigurationStub;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|LoggerInterface
     */
    protected $loggerStub;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Redis
     */
    protected $redisMock;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->loggerStub = $this->createStub(LoggerInterface::class);
        $this->redisMock = $this->createMock(Redis::class);
    }

    public function testCanCreateInstance(): void
    {
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        /** @psalm-suppress PossiblyInvalidArgument */
        $this->assertInstanceOf(
            RedisStore::class,
            new RedisStore(
                $this->moduleConfigurationStub,
                $this->loggerStub,
                null,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->redisMock
            )
        );

        /** @psalm-suppress PossiblyInvalidArgument */
        $this->assertInstanceOf(
            RedisStore::class,
            RedisStore::build($this->moduleConfigurationStub, $this->loggerStub)
        );
    }
}
