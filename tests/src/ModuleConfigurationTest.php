<?php

namespace SimpleSAML\Test\Module\accounting;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module\accounting\Exceptions\ModuleConfiguration\InvalidConfigurationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Jobs\Pdo\MySql\MySqlPdoJobsStore;

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
        $this->assertIsString($this->moduleConfiguration->get(ModuleConfiguration::OPTION_USER_ID_ATTRIBUTE));
    }

    public function testProperConnectionKeyIsReturned(): void
    {
        $this->assertSame('mysql', $this->moduleConfiguration->getStoreConnection(MySqlPdoJobsStore::class));
    }

    public function testInvalidConnectionKeyThrows(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->moduleConfiguration->getStoreConnection('invalid');
    }

    public function testCanGetDefinedConnections(): void
    {
        $this->assertArrayHasKey('mysql', $this->moduleConfiguration->getAllStoreConnectionsAndSettings());
    }

    public function testCanGetSettingsForSpecificConnection(): void
    {
        $this->assertIsArray($this->moduleConfiguration->getStoreConnectionSettings('mysql'));
    }

    public function testGettingSettingsForInvalidConnectionThrows(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->moduleConfiguration->getStoreConnectionSettings('invalid');
    }
}
