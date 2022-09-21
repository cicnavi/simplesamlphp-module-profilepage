<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Providers\Builders;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Providers\Builders\AuthenticationDataProviderBuilder;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Trackers\Authentication\DoctrineDbal\Versioned\Tracker;

/**
 * @covers \SimpleSAML\Module\accounting\Providers\Builders\AuthenticationDataProviderBuilder
 * @uses \SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfigurationHelper
 * @uses \SimpleSAML\Module\accounting\Stores\Builders\Bases\AbstractStoreBuilder
 * @uses \SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractStore
 * @uses \SimpleSAML\Module\accounting\Stores\Builders\DataStoreBuilder
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Migrator
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\Repository
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Connection
 * @uses \SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory
 * @uses \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store
 * @uses \SimpleSAML\Module\accounting\Trackers\Authentication\DoctrineDbal\Versioned\Tracker
 */
class AuthenticationDataProviderBuilderTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $moduleConfigurationStub;

    protected \PHPUnit\Framework\MockObject\Stub $loggerStub;

    protected function setUp(): void
    {
        $this->moduleConfigurationStub = $this->createStub(ModuleConfiguration::class);
        $connectionParams = ['driver' => 'pdo_sqlite', 'memory' => true,];
        $this->moduleConfigurationStub->method('getConnectionParameters')
            ->willReturn($connectionParams);

        $this->loggerStub = $this->createStub(LoggerInterface::class);
    }

    public function testCanCreateInstance(): void
    {
        /** @psalm-suppress InvalidArgument */
        $this->assertInstanceOf(
            AuthenticationDataProviderBuilder::class,
            new AuthenticationDataProviderBuilder($this->moduleConfigurationStub, $this->loggerStub)
        );
    }

    public function testCanBuildDataProvider(): void
    {
        /** @psalm-suppress InvalidArgument */
        $builder = new AuthenticationDataProviderBuilder($this->moduleConfigurationStub, $this->loggerStub);

        $this->assertInstanceOf(Tracker::class, $builder->build(Tracker::class));
    }

    public function testThrowsForInvalidClass(): void
    {
        $this->expectException(Exception::class);

        /** @psalm-suppress InvalidArgument */
        (new AuthenticationDataProviderBuilder($this->moduleConfigurationStub, $this->loggerStub))
            ->build('invalid');
    }
}
