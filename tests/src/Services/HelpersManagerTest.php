<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Services;

use SimpleSAML\Module\profilepage\Helpers\Arr;
use SimpleSAML\Module\profilepage\Helpers\Attributes;
use SimpleSAML\Module\profilepage\Helpers\AuthenticationEventStateResolver;
use SimpleSAML\Module\profilepage\Helpers\DateTime;
use SimpleSAML\Module\profilepage\Helpers\Environment;
use SimpleSAML\Module\profilepage\Helpers\Filesystem;
use SimpleSAML\Module\profilepage\Helpers\Hash;
use SimpleSAML\Module\profilepage\Helpers\InstanceBuilderUsingModuleConfiguration;
use SimpleSAML\Module\profilepage\Helpers\Routes;
use SimpleSAML\Module\profilepage\Helpers\Network;
use SimpleSAML\Module\profilepage\Helpers\ProviderResolver;
use SimpleSAML\Module\profilepage\Helpers\Random;
use SimpleSAML\Module\profilepage\Helpers\SspModule;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\profilepage\Services\HelpersManager
 * @uses \SimpleSAML\Module\profilepage\Helpers\Routes
 * @uses \SimpleSAML\Module\profilepage\Helpers\Hash
 */
class HelpersManagerTest extends TestCase
{
    public function testCanGetHelperInstances(): void
    {
        $helpersManager = new HelpersManager();

        $this->assertInstanceOf(Arr::class, $helpersManager->getArr());
        $this->assertInstanceOf(Attributes::class, $helpersManager->getAttributes());
        $this->assertInstanceOf(DateTime::class, $helpersManager->getDateTime());
        $this->assertInstanceOf(Environment::class, $helpersManager->getEnvironment());
        $this->assertInstanceOf(Filesystem::class, $helpersManager->getFilesystem());
        $this->assertInstanceOf(Hash::class, $helpersManager->getHash());
        $this->assertInstanceOf(
            InstanceBuilderUsingModuleConfiguration::class,
            $helpersManager->getInstanceBuilderUsingModuleConfiguration()
        );
        $this->assertInstanceOf(Network::class, $helpersManager->getNetwork());
        $this->assertInstanceOf(Random::class, $helpersManager->getRandom());
        $this->assertInstanceOf(Routes::class, $helpersManager->getRoutes());
        $this->assertInstanceOf(
            AuthenticationEventStateResolver::class,
            $helpersManager->getAuthenticationEventStateResolver()
        );
        $this->assertInstanceOf(ProviderResolver::class, $helpersManager->getProviderResolver());
        $this->assertInstanceOf(SspModule::class, $helpersManager->getSspModule());
    }
}
