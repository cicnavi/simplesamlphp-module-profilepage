<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Services;

use SimpleSAML\Module\accounting\Helpers\Arr;
use SimpleSAML\Module\accounting\Helpers\Attributes;
use SimpleSAML\Module\accounting\Helpers\AuthenticationEventStateResolver;
use SimpleSAML\Module\accounting\Helpers\DateTime;
use SimpleSAML\Module\accounting\Helpers\Environment;
use SimpleSAML\Module\accounting\Helpers\Filesystem;
use SimpleSAML\Module\accounting\Helpers\Hash;
use SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfiguration;
use SimpleSAML\Module\accounting\Helpers\Routes;
use SimpleSAML\Module\accounting\Helpers\Network;
use SimpleSAML\Module\accounting\Helpers\ProviderResolver;
use SimpleSAML\Module\accounting\Helpers\Random;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Services\HelpersManager
 * @uses \SimpleSAML\Module\accounting\Helpers\Routes
 * @uses \SimpleSAML\Module\accounting\Helpers\Hash
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
    }
}
