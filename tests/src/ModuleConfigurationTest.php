<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module\accounting\Data\Providers\Activity\DoctrineDbal\VersionedDataProvider;
use SimpleSAML\Module\accounting\Data\Stores;
use SimpleSAML\Module\accounting\Data\Stores\Interfaces\JobsStoreInterface;
use SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Module\accounting\Data\Trackers;
use SimpleSAML\Module\accounting\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use stdClass;

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
            is_subclass_of($this->moduleConfiguration->getJobsStoreClass(), JobsStoreInterface::class)
        );
    }

    /**
     * @throws \Exception
     */
    public function testThrowsForInvalidConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration(
            null,
            [
                ModuleConfiguration::OPTION_ACCOUNTING_PROCESSING_TYPE => 'invalid',
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function testThrowsForInvalidJobsStore(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration(
            null,
            [
                ModuleConfiguration::OPTION_ACCOUNTING_PROCESSING_TYPE =>
                    ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS,
                ModuleConfiguration::OPTION_JOBS_STORE => 'invalid',
            ]
        );
    }

    public function testProperConnectionKeyIsReturned(): void
    {
        $this->assertSame(
            'doctrine_dbal_pdo_sqlite',
            $this->moduleConfiguration->getClassConnectionKey(
                Store::class
            )
        );
    }

    public function testCanGetSlaveConnectionKey(): void
    {
        $this->assertSame(
            'doctrine_dbal_pdo_sqlite_slave',
            $this->moduleConfiguration->getClassConnectionKey(
                VersionedDataProvider::class,
                ModuleConfiguration\ConnectionType::SLAVE
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testThrowsForNonStringAndNonArrayConnectionKey(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration(
            null,
            [
                ModuleConfiguration::OPTION_CLASS_TO_CONNECTION_MAP => [
                    'invalid-object-value' => new stdClass(),
                ]
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function testThrowsForNonMasterInArrayConnection(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration(
            null,
            [
                ModuleConfiguration::OPTION_CLASS_TO_CONNECTION_MAP => [
                    'invalid-array-value' => [
                        'no-master-key' => 'invalid',
                    ],
                ]
            ]
        );
    }

    public function testThrowsForInvalidConnectionType(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->moduleConfiguration->getClassConnectionKey(
            Store::class,
            'invalid'
        );
    }

    public function testThrowsIfConnectionForClassNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->moduleConfiguration->getClassConnectionKey('invalid');
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

    public function testCanGetCronTagForJobRunner(): void
    {
        $this->assertSame(
            'accounting_job_runner',
            $this->moduleConfiguration->getCronTagForJobRunner()
        );
    }

    public function testCanGetJobRunnerMaximumExecutionTime(): void
    {
        $this->assertNull($this->moduleConfiguration->getJobRunnerMaximumExecutionTime());
    }

    /**
     * @throws \Exception
     */
    public function testThrowsForNonStringJobRunnerMaximumExecutionTime(): void
    {
        $moduleConfiguration = new ModuleConfiguration(
            null,
            [ModuleConfiguration::OPTION_JOB_RUNNER_MAXIMUM_EXECUTION_TIME => []]
        );

        $this->expectException(InvalidConfigurationException::class);

        $moduleConfiguration->getJobRunnerMaximumExecutionTime();
    }

    /**
     * @throws \Exception
     */
    public function testThrowsForInvalidStringJobRunnerMaximumExecutionTime(): void
    {
        $moduleConfiguration = new ModuleConfiguration(
            null,
            [ModuleConfiguration::OPTION_JOB_RUNNER_MAXIMUM_EXECUTION_TIME => 'invalid']
        );


        $this->expectException(InvalidConfigurationException::class);

        $moduleConfiguration->getJobRunnerMaximumExecutionTime();
    }

    public function testCanGetJobRunnerShouldPauseAfterNumberOfJobsProcessed(): void
    {
        $this->assertSame(10, $this->moduleConfiguration->getJobRunnerShouldPauseAfterNumberOfJobsProcessed());
    }

    /**
     * @throws \Exception
     */
    public function testCanGetNullForJobRunnerShouldPauseAfterNumberOfJobsProcessed(): void
    {
        $moduleConfiguration = new ModuleConfiguration(
            null,
            [ModuleConfiguration::OPTION_JOB_RUNNER_SHOULD_PAUSE_AFTER_NUMBER_OF_JOBS_PROCESSED => false]
        );

        $this->assertNull($moduleConfiguration->getJobRunnerShouldPauseAfterNumberOfJobsProcessed());
    }

    /**
     * @throws \Exception
     */
    public function testThrowsForNonIntegerJobRunnerShouldPauseAfterNumberOfJobsProcessed(): void
    {
        $moduleConfiguration = new ModuleConfiguration(
            null,
            [ModuleConfiguration::OPTION_JOB_RUNNER_SHOULD_PAUSE_AFTER_NUMBER_OF_JOBS_PROCESSED => []]
        );

        $this->expectException(InvalidConfigurationException::class);

        $moduleConfiguration->getJobRunnerShouldPauseAfterNumberOfJobsProcessed();
    }

    /**
     * @throws \Exception
     */
    public function testThrowsForNegativeIntegerJobRunnerShouldPauseAfterNumberOfJobsProcessed(): void
    {
        $moduleConfiguration = new ModuleConfiguration(
            null,
            [ModuleConfiguration::OPTION_JOB_RUNNER_SHOULD_PAUSE_AFTER_NUMBER_OF_JOBS_PROCESSED => -1]
        );

        $this->expectException(InvalidConfigurationException::class);

        $moduleConfiguration->getJobRunnerShouldPauseAfterNumberOfJobsProcessed();
    }

    /**
     * @throws \Exception
     */
    public function testThrowsOnInvalidCronTag(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration(
            null,
            [
                ModuleConfiguration::OPTION_ACCOUNTING_PROCESSING_TYPE =>
                    ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS,
                ModuleConfiguration::OPTION_CRON_TAG_FOR_JOB_RUNNER => -1
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function testThrowsOnInvalidDataProvider(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration(
            null,
            [
                ModuleConfiguration::OPTION_PROVIDER_FOR_ACTIVITY => 'invalid'
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function testThrowsOnInvalidAdditionalTrackers(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration(
            null,
            [
                ModuleConfiguration::OPTION_ADDITIONAL_TRACKERS => ['invalid']
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function testThrowsOnNonStringAdditionalTracker(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration(
            null,
            [
                ModuleConfiguration::OPTION_ADDITIONAL_TRACKERS => [-1]
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function testThrowsWhenClassHasNoConnectionParametersSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration(
            null,
            [
                ModuleConfiguration::OPTION_CONNECTIONS_AND_PARAMETERS => []
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function testThrowsForInvalidTrackerDataRetentionPolicy(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration(
            null,
            [
                ModuleConfiguration::OPTION_TRACKER_DATA_RETENTION_POLICY => 'invalid'
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function testThrowsForInvalidCronTagForTrackerDataRetentionPolicy(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ModuleConfiguration(
            null,
            [
                ModuleConfiguration::OPTION_TRACKER_DATA_RETENTION_POLICY => 'P1D',
                ModuleConfiguration::OPTION_CRON_TAG_FOR_TRACKER_DATA_RETENTION_POLICY => false,
            ]
        );
    }
}
