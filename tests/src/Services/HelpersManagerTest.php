<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Services;

use SimpleSAML\Module\accounting\Helpers\ArrayHelper;
use SimpleSAML\Module\accounting\Helpers\AttributesHelper;
use SimpleSAML\Module\accounting\Helpers\DateTimeHelper;
use SimpleSAML\Module\accounting\Helpers\EnvironmentHelper;
use SimpleSAML\Module\accounting\Helpers\FilesystemHelper;
use SimpleSAML\Module\accounting\Helpers\HashHelper;
use SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfigurationHelper;
use SimpleSAML\Module\accounting\Helpers\ModuleRoutesHelper;
use SimpleSAML\Module\accounting\Helpers\NetworkHelper;
use SimpleSAML\Module\accounting\Helpers\RandomHelper;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Services\HelpersManager
 * @uses \SimpleSAML\Module\accounting\Helpers\ModuleRoutesHelper
 * @uses \SimpleSAML\Module\accounting\Helpers\HashHelper
 */
class HelpersManagerTest extends TestCase
{
    public function testCanGetHelperInstances(): void
    {
        $helpersManager = new HelpersManager();

        $this->assertInstanceOf(ArrayHelper::class, $helpersManager->getArrayHelper());
        $this->assertInstanceOf(AttributesHelper::class, $helpersManager->getAttributesHelper());
        $this->assertInstanceOf(DateTimeHelper::class, $helpersManager->getDateTimeHelper());
        $this->assertInstanceOf(EnvironmentHelper::class, $helpersManager->getEnvironmentHelper());
        $this->assertInstanceOf(FilesystemHelper::class, $helpersManager->getFilesystemHelper());
        $this->assertInstanceOf(HashHelper::class, $helpersManager->getHashHelper());
        $this->assertInstanceOf(
            InstanceBuilderUsingModuleConfigurationHelper::class,
            $helpersManager->getInstanceBuilderUsingModuleConfigurationHelper()
        );
        $this->assertInstanceOf(NetworkHelper::class, $helpersManager->getNetworkHelper());
        $this->assertInstanceOf(RandomHelper::class, $helpersManager->getRandomHelper());
        $this->assertInstanceOf(ModuleRoutesHelper::class, $helpersManager->getModuleRoutesHelper());
    }
}
