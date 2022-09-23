<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module\accounting\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores;
use SimpleSAML\Module\accounting\Trackers;

/**
 * @covers \SimpleSAML\Module\accounting\ModuleConfiguration
 */
class ModuleConfigurationTest extends TestCase
{
    protected ModuleConfiguration $moduleConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        // Configuration directory is set by phpunit using php ENV setting feature (check phpunit.xml).
        $this->moduleConfiguration = new ModuleConfiguration('module_accounting.php');
    }

    public function testCanGetUnderlyingConfigurationInstance(): void
    {
        $this->assertInstanceOf(Configuration::class, $this->moduleConfiguration->getConfiguration());
    }

    public function testThrowExceptionsIfInvalidOptionIsSupplied(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->moduleConfiguration->get('invalid');
    }

    public function testCanGetValidOption(): void
    {
        $this->assertIsString($this->moduleConfiguration->get(ModuleConfiguration::OPTION_USER_ID_ATTRIBUTE_NAME));
    }

    public function testCanGetUserIdAttributeName(): void
    {
        $this->assertIsString($this->moduleConfiguration->getUserIdAttributeName());
    }

    public function testCanGetDefaultAuthenticationSource(): void
    {
        $this->assertIsString($this->moduleConfiguration->getDefaultAuthenticationSource());
    }

    public function testCanGetJobsStoreClass(): void
    {
        $this->assertTrue(
            is_subclass_of($this->moduleConfiguration->getJobsStoreClass(), Stores\Interfaces\JobsStoreInterface::class)
        );
    }

    public function testThrowsForInvalidConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration('invalid_module_accounting.php');
    }

    public function testThrowsForInvalidJobsStore(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration('invalid_async_module_accounting.php');
    }

    public function testProperConnectionKeyIsReturned(): void
    {
        $this->assertSame(
            'doctrine_dbal_pdo_sqlite',
            $this->moduleConfiguration->getClassConnectionKey(Stores\Jobs\DoctrineDbal\Store::class)
        );
    }

    public function testCanGetSlaveConnectionKey(): void
    {
        $this->assertSame(
            'doctrine_dbal_pdo_sqlite_slave',
            $this->moduleConfiguration->getClassConnectionKey(
                Trackers\Authentication\DoctrineDbal\Versioned\Tracker::class,
                ModuleConfiguration\ConnectionType::SLAVE
            )
        );
    }

    public function testThrowsForNonStringAndNonArrayConnectionKey(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration('invalid_object_value_module_accounting.php');
    }

    public function testThrowsForNonMasterInArrayConnection(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration('invalid_array_value_module_accounting.php');
    }

    public function testThrowsForInvalidConnectiontype(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->moduleConfiguration->getClassConnectionKey(
            Stores\Jobs\DoctrineDbal\Store::class,
            'invalid'
        );
    }

    public function testInvalidConnectionKeyThrows(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->moduleConfiguration->getClassConnectionParameters('invalid');
    }

    public function testCanGetDefinedConnections(): void
    {
        $this->assertArrayHasKey(
            'doctrine_dbal_pdo_sqlite',
            $this->moduleConfiguration->getConnectionsAndParameters()
        );
    }

    public function testCanGetParametersForSpecificConnection(): void
    {
        $this->assertIsArray($this->moduleConfiguration->getConnectionParameters('doctrine_dbal_pdo_sqlite'));
    }

    public function testGettingSettingsForInvalidConnectionThrows(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->moduleConfiguration->getConnectionParameters('invalid');
    }

    public function testCanGetModuleSourceDirectory(): void
    {
        $this->assertSame(
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR .  'src',
            $this->moduleConfiguration->getModuleSourceDirectory()
        );
    }

    public function testCanGetModuleRootDirectory(): void
    {
        $this->assertSame(
            dirname(__DIR__, 2),
            $this->moduleConfiguration->getModuleRootDirectory()
        );
    }
}
