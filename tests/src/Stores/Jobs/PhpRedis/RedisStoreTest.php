<?php

/** @noinspection PhpComposerExtensionStubsInspection ext-redis should only be installed if used. */

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Stores\Jobs\PhpRedis;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use Redis;
use RedisException;
use SimpleSAML\Module\accounting\Entities\GenericJob;
use SimpleSAML\Module\accounting\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\accounting\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Jobs\PhpRedis\RedisStore;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Jobs\PhpRedis\RedisStore
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\AbstractStore
 */
class RedisStoreTest extends TestCase
{
    /**
     * @var Stub|ModuleConfiguration
     */
    protected $moduleConfigurationStub;
    /**
     * @var MockObject|LoggerInterface
     */
    protected $loggerMock;
    /**
     * @var MockObject|Redis
     */
    protected $redisMock;
    /**
     * @var Stub|JobInterface
     */
    protected $jobStub;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->redisMock = $this->createMock(Redis::class);
        $this->jobStub = $this->createStub(JobInterface::class);
        $this->jobStub->method('getType')->willReturn(GenericJob::class);
    }

    /**
     * @throws StoreException
     */
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
                $this->loggerMock,
                null,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->redisMock
            )
        );
    }

    /**
     * @throws StoreException
     */
    public function testThrowsIfHostConnectionParameterNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        /** @psalm-suppress PossiblyInvalidArgument */
        $this->assertInstanceOf(
            RedisStore::class,
            new RedisStore(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                null,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->redisMock
            )
        );
    }

    public function testThrowsOnConnectionError(): void
    {
        $this->expectException(StoreException::class);

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Error trying to connect to Redis DB.'));

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->redisMock->method('connect')->willThrowException(new RedisException('test'));

        /** @psalm-suppress PossiblyInvalidArgument */
        $this->assertInstanceOf(
            RedisStore::class,
            new RedisStore(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                null,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->redisMock
            )
        );
    }

    public function testThrowsOnAuthError(): void
    {
        $this->expectException(StoreException::class);

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Error trying to set auth parameter for Redis.'));

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample', 'auth' => 'test']);

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->redisMock->method('auth')->willThrowException(new RedisException('test'));

        /** @psalm-suppress PossiblyInvalidArgument */
        $this->assertInstanceOf(
            RedisStore::class,
            new RedisStore(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                null,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->redisMock
            )
        );
    }

    public function testThrowsOnSetPrefixOptionError(): void
    {
        $this->expectException(StoreException::class);
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Could not set key prefix for Redis.'));

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->redisMock->method('setOption')->willThrowException(new RedisException('test'));

        /** @psalm-suppress PossiblyInvalidArgument */
        $this->assertInstanceOf(
            RedisStore::class,
            new RedisStore(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                null,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->redisMock
            )
        );
    }

    /**
     * @throws StoreException
     */
    public function testCanCallRPushMethod(): void
    {
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->redisMock->method('isConnected')->willReturn(true);
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->redisMock->expects($this->once())
            ->method('rPush')
            ->with($this->stringStartsWith(RedisStore::LIST_KEY_JOB), $this->isType('string'));

        /** @psalm-suppress PossiblyInvalidArgument */
        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock
        );

        /** @psalm-suppress PossiblyInvalidArgument */
        $redisStore->enqueue($this->jobStub);
    }

    public function testThrowsOnRPushError(): void
    {
        $this->expectException(StoreException::class);
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Could not add job to Redis list.'));

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->redisMock->method('rPush')->willThrowException(new RedisException('test'));

        /** @psalm-suppress PossiblyInvalidArgument */
        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock
        );

        /** @psalm-suppress PossiblyInvalidArgument */
        $redisStore->enqueue($this->jobStub);
    }

    /**
     * @throws StoreException
     */
    public function testCanDequeueJob(): void
    {
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->redisMock->method('lPop')
            ->willReturn(serialize($this->jobStub));

        /** @psalm-suppress PossiblyInvalidArgument */
        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock
        );

        /** @psalm-suppress PossiblyInvalidArgument, MixedArgument, PossiblyUndefinedMethod */
        $this->assertInstanceOf(JobInterface::class, $redisStore->dequeue($this->jobStub->getType()));
    }

    public function testThrowsOnLPopError(): void
    {
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->redisMock->method('lPop')
            ->willThrowException(new RedisException('test'));

        $this->expectException(StoreException::class);
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Could not pop job from Redis list.'));

        /** @psalm-suppress PossiblyInvalidArgument */
        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock
        );

        /** @psalm-suppress PossiblyInvalidArgument, MixedArgument, PossiblyUndefinedMethod */
        $redisStore->dequeue($this->jobStub->getType());
    }

    public function testThrowsIfNotAbleToDeserializeJobEntry(): void
    {
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->redisMock->method('lPop')
            ->willReturn('invalid');

        $this->expectException(StoreException::class);
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Could not deserialize job entry which was available in Redis.'));

        /** @psalm-suppress PossiblyInvalidArgument */
        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock
        );

        // Suppress notice being raised using @
        /** @psalm-suppress PossiblyInvalidArgument, MixedArgument, PossiblyUndefinedMethod */
        @$redisStore->dequeue($this->jobStub->getType());
    }

    /**
     * @throws StoreException
     */
    public function testCanMarkFailedJob(): void
    {
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->redisMock->expects($this->once())
            ->method('rPush')
            ->with($this->stringStartsWith(RedisStore::LIST_KEY_JOB_FAILED), $this->isType('string'));

        /** @psalm-suppress PossiblyInvalidArgument */
        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock
        );

        /** @psalm-suppress PossiblyInvalidArgument */
        $redisStore->markFailedJob($this->jobStub);
    }

    public function testThrowsOnMarkingFailedJobError(): void
    {
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->redisMock->method('rPush')
            ->willThrowException(new RedisException('test'));

        $this->expectException(StoreException::class);
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Could not mark job as failed.'));

        /** @psalm-suppress PossiblyInvalidArgument */
        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock
        );

        /** @psalm-suppress PossiblyInvalidArgument */
        $redisStore->markFailedJob($this->jobStub);
    }

    /**
     * @throws StoreException
     */
    public function testSetupIsNotNeeded(): void
    {
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        /** @psalm-suppress PossiblyInvalidArgument */
        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock
        );

        /** @psalm-suppress PossiblyInvalidArgument */
        $this->assertFalse($redisStore->needsSetup());
    }
}
