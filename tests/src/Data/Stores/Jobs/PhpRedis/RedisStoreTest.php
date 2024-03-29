<?php

/** @noinspection PhpComposerExtensionStubsInspection ext-redis should only be installed if used. */

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Jobs\PhpRedis;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Redis;
use RedisException;
use SimpleSAML\Module\profilepage\Data\Stores\Jobs\PhpRedis\RedisStore;
use SimpleSAML\Module\profilepage\Entities\GenericJob;
use SimpleSAML\Module\profilepage\Entities\Interfaces\JobInterface;
use SimpleSAML\Module\profilepage\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\profilepage\Exceptions\StoreException;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Test\Module\profilepage\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Jobs\PhpRedis\RedisStore
 * @uses \SimpleSAML\Module\profilepage\Data\Stores\Bases\AbstractStore
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractJob
 */
class RedisStoreTest extends TestCase
{
    /**
     * @var Stub
     */
    protected $moduleConfigurationStub;
    /**
     * @var MockObject
     */
    protected $loggerMock;
    /**
     * @var MockObject
     */
    protected $redisMock;
    /**
     * @var Stub
     */
    protected $jobStub;
    private MockObject $serializerMock;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->redisMock = $this->createMock(Redis::class);
        $this->jobStub = $this->createStub(JobInterface::class);
        $this->jobStub->method('getType')->willReturn(GenericJob::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->serializerMock->method('undo')->willReturnCallback(
            fn($argument) => unserialize($argument)
        );
    }

    /**
     * @throws StoreException
     */
    public function testCanCreateInstance(): void
    {
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        $this->assertInstanceOf(
            RedisStore::class,
            new RedisStore(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                null,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->redisMock,
                $this->serializerMock,
            )
        );
    }

    /**
     * @throws StoreException
     */
    public function testThrowsIfHostConnectionParameterNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->assertInstanceOf(
            RedisStore::class,
            new RedisStore(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                null,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->redisMock,
                $this->serializerMock,
            )
        );
    }

    public function testThrowsOnConnectionError(): void
    {
        $this->expectException(StoreException::class);

        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Error trying to connect to Redis DB.'));

        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        $this->redisMock->method('connect')->willThrowException(new RedisException('test'));

        $this->assertInstanceOf(
            RedisStore::class,
            new RedisStore(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                null,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->redisMock,
                $this->serializerMock,
            )
        );
    }

    public function testThrowsOnAuthError(): void
    {
        $this->expectException(StoreException::class);

        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Error trying to set auth parameter for Redis.'));

        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample', 'auth' => 'test']);

        $this->redisMock->method('auth')->willThrowException(new RedisException('test'));

        $this->assertInstanceOf(
            RedisStore::class,
            new RedisStore(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                null,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->redisMock,
                $this->serializerMock,
            )
        );
    }

    public function testThrowsOnSetPrefixOptionError(): void
    {
        $this->expectException(StoreException::class);
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Could not set key prefix for Redis.'));

        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        $this->redisMock->method('setOption')->willThrowException(new RedisException('test'));

        $this->assertInstanceOf(
            RedisStore::class,
            new RedisStore(
                $this->moduleConfigurationStub,
                $this->loggerMock,
                null,
                ModuleConfiguration\ConnectionType::MASTER,
                $this->redisMock,
                $this->serializerMock,
            )
        );
    }

    /**
     * @throws StoreException
     */
    public function testCanCallRPushMethod(): void
    {
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        $this->redisMock->method('isConnected')->willReturn(true);
        $this->redisMock->expects($this->once())
            ->method('rPush')
            ->with($this->stringStartsWith(RedisStore::LIST_KEY_JOB), $this->isType('string'));

        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock,
            $this->serializerMock,
        );

        $redisStore->enqueue($this->jobStub);
    }

    public function testThrowsOnRPushError(): void
    {
        $this->expectException(StoreException::class);
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Could not add job to Redis list.'));

        $this->redisMock->method('rPush')->willThrowException(new RedisException('test'));

        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock,
            $this->serializerMock,
        );

        $redisStore->enqueue($this->jobStub);
    }

    /**
     * @throws StoreException
     */
    public function testCanDequeueJob(): void
    {
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        $this->redisMock->method('lPop')
            ->willReturn(serialize(StateArrays::SAML2_FULL));

        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock,
            $this->serializerMock,
        );

        $this->assertInstanceOf(JobInterface::class, $redisStore->dequeue($this->jobStub->getType()));
    }

    public function testThrowsOnLPopError(): void
    {
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        $this->redisMock->method('lPop')
            ->willThrowException(new RedisException('test'));

        $this->expectException(StoreException::class);
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Could not pop job from Redis list.'));

        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock,
            $this->serializerMock,
        );

        $redisStore->dequeue($this->jobStub->getType());
    }

    public function testThrowsIfNotAbleToDeserializeJobEntry(): void
    {
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        $this->redisMock->method('lPop')
            ->willReturn('invalid');

        $this->expectException(StoreException::class);
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Could not deserialize job entry which was available in Redis.'));

        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock,
            $this->serializerMock,
        );

        // Suppress notice being raised using @
        @$redisStore->dequeue($this->jobStub->getType());
    }

    /**
     * @throws StoreException
     */
    public function testCanMarkFailedJob(): void
    {
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        $this->redisMock->expects($this->once())
            ->method('rPush')
            ->with($this->stringStartsWith(RedisStore::LIST_KEY_JOB_FAILED), $this->isType('string'));

        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock,
            $this->serializerMock,
        );

        $redisStore->markFailedJob($this->jobStub);
    }

    public function testThrowsOnMarkingFailedJobError(): void
    {
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        $this->redisMock->method('rPush')
            ->willThrowException(new RedisException('test'));

        $this->expectException(StoreException::class);
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Could not mark job as failed.'));

        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock,
            $this->serializerMock,
        );

        $redisStore->markFailedJob($this->jobStub);
    }

    /**
     * @throws StoreException
     */
    public function testSetupIsNotNeeded(): void
    {
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn(['host' => 'sample']);

        $redisStore = new RedisStore(
            $this->moduleConfigurationStub,
            $this->loggerMock,
            null,
            ModuleConfiguration\ConnectionType::MASTER,
            $this->redisMock,
            $this->serializerMock,
        );

        $this->assertFalse($redisStore->needsSetup());
    }
}
