<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting;

use DateInterval;
use Exception;
use SimpleSAML\Configuration;
use SimpleSAML\Module\accounting\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\accounting\ModuleConfiguration\AccountingProcessingType;
use SimpleSAML\Module\accounting\ModuleConfiguration\ConnectionType;
use SimpleSAML\Module\accounting\Providers\Interfaces\AuthenticationDataProviderInterface;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;
use SimpleSAML\Module\accounting\Trackers\Interfaces\AuthenticationDataTrackerInterface;
use Throwable;

class ModuleConfiguration
{
    public const MODULE_NAME = 'accounting';

    /**
     * Default file name for module configuration. Can be overridden, for example, for testing purposes.
     */
    public const FILE_NAME = 'module_accounting.php';

    public const OPTION_USER_ID_ATTRIBUTE_NAME = 'user_id_attribute_name';
    public const OPTION_DEFAULT_AUTHENTICATION_SOURCE = 'default_authentication_source';
    public const OPTION_ACCOUNTING_PROCESSING_TYPE = 'accounting_processing_type';
    public const OPTION_JOBS_STORE = 'jobs_store';
    public const OPTION_DEFAULT_DATA_TRACKER_AND_PROVIDER = 'default_data_tracker_and_provider';
    public const OPTION_ADDITIONAL_TRACKERS = 'additional_trackers';
    public const OPTION_CONNECTIONS_AND_PARAMETERS = 'connections_and_parameters';
    public const OPTION_CLASS_TO_CONNECTION_MAP = 'class_to_connection_map';
    public const OPTION_CRON_TAG_FOR_JOB_RUNNER = 'cron_tag_for_job_runner';
    public const OPTION_JOB_RUNNER_MAXIMUM_EXECUTION_TIME = 'job_runner_maximum_execution_time';
    public const OPTION_JOB_RUNNER_SHOULD_PAUSE_AFTER_NUMBER_OF_JOBS_PROCESSED =
        'job_runner_should_pause_after_number_of_jobs_processed';
    public const OPTION_TRACKER_DATA_RETENTION_POLICY = 'tracker_data_retention_policy';
    public const OPTION_CRON_TAG_FOR_TRACKER_DATA_RETENTION_POLICY = 'cron_tag_for_tracker_data_retention_policy';

    /**
     * Contains configuration from module configuration file.
     */
    protected Configuration $configuration;

    /**
     * @throws Exception
     */
    public function __construct(string $fileName = null, array $overrides = [])
    {
        $fileName = $fileName ?? self::FILE_NAME;

        $fullConfigArray = array_merge(Configuration::getConfig($fileName)->toArray(), $overrides);

        $this->configuration = Configuration::loadFromArray($fullConfigArray);

        $this->validate();
    }

    public function getAccountingProcessingType(): string
    {
        return $this->getConfiguration()->getString(self::OPTION_ACCOUNTING_PROCESSING_TYPE);
    }

    public function getCronTagForJobRunner(): string
    {
        return $this->getConfiguration()->getString(self::OPTION_CRON_TAG_FOR_JOB_RUNNER);
    }

    /**
     * Get underlying SimpleSAMLphp Configuration instance.
     *
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    public function getJobsStoreClass(): string
    {
        return $this->getConfiguration()->getString(self::OPTION_JOBS_STORE);
    }

    public function getJobRunnerMaximumExecutionTime(): ?DateInterval
    {
        $value = $this->get(self::OPTION_JOB_RUNNER_MAXIMUM_EXECUTION_TIME);

        if (is_null($value)) {
            return null;
        }

        if (! is_string($value)) {
            $message = sprintf('Job runner maximum activity must be defined either as null, or DateInterval' .
                               'duration (string).');
            throw new InvalidConfigurationException($message);
        }

        try {
            return new DateInterval($value);
        } catch (Throwable $exception) {
            $message = sprintf('Can not create DateInterval instance using value %s as parameter.', $value);
            throw new InvalidConfigurationException($message);
        }
    }

    public function getJobRunnerShouldPauseAfterNumberOfJobsProcessed(): ?int
    {
        $value = $this->get(self::OPTION_JOB_RUNNER_SHOULD_PAUSE_AFTER_NUMBER_OF_JOBS_PROCESSED);

        if (is_null($value)) {
            return null;
        }

        if (! is_int($value)) {
            $message = sprintf(
                'Option \'%s\' must be defined either as null, or positive integer.',
                self::OPTION_JOB_RUNNER_SHOULD_PAUSE_AFTER_NUMBER_OF_JOBS_PROCESSED
            );
            throw new InvalidConfigurationException($message);
        }

        if ($value < 1) {
            $message = sprintf(
                'Option \'%s\' must positive integer.',
                self::OPTION_JOB_RUNNER_SHOULD_PAUSE_AFTER_NUMBER_OF_JOBS_PROCESSED
            );
            throw new InvalidConfigurationException($message);
        }

        return $value;
    }

    public function getDefaultDataTrackerAndProviderClass(): string
    {
        return $this->getConfiguration()->getString(self::OPTION_DEFAULT_DATA_TRACKER_AND_PROVIDER);
    }

    public function getConnectionsAndParameters(): array
    {
        return $this->getConfiguration()->getArray(self::OPTION_CONNECTIONS_AND_PARAMETERS);
    }

    public function getAdditionalTrackers(): array
    {
        return $this->getConfiguration()->getArray(self::OPTION_ADDITIONAL_TRACKERS);
    }

    public function getClassToConnectionsMap(): array
    {
        return $this->getConfiguration()->getArray(self::OPTION_CLASS_TO_CONNECTION_MAP);
    }

    /**
     * Get configuration option from module configuration file.
     *
     * @param string $option
     * @return mixed
     */
    public function get(string $option)
    {
        if (!$this->configuration->hasValue($option)) {
            throw new InvalidConfigurationException(
                sprintf('Configuration option does not exist (%s).', $option)
            );
        }

        return $this->configuration->getValue($option);
    }

    public function getUserIdAttributeName(): string
    {
        return $this->getConfiguration()->getString(self::OPTION_USER_ID_ATTRIBUTE_NAME);
    }

    public function getDefaultAuthenticationSource(): string
    {
        return $this->getConfiguration()->getString(self::OPTION_DEFAULT_AUTHENTICATION_SOURCE);
    }

    public function getClassConnectionKey(string $class, string $connectionType = ConnectionType::MASTER): string
    {
        $this->validateConnectionType($connectionType);

        $connections = $this->getClassToConnectionsMap();

        if (!isset($connections[$class])) {
            throw new InvalidConfigurationException(sprintf('Connection for class \'%s\' not set.', $class));
        }

        $connectionValue = $connections[$class];

        // If the key is defined directly, return that.
        if (is_string($connectionValue)) {
            return $connectionValue;
        }

        if (!is_array($connectionValue)) {
            throw new InvalidConfigurationException(
                sprintf('Connection for class \'%s\' is not defined as string nor as array.', $class)
            );
        }

        if (!isset($connectionValue[ConnectionType::MASTER])) {
            $message = sprintf(
                'Connection for class \'%s\' is defined as array, however no master connection key is set.',
                $class
            );
            throw new InvalidConfigurationException($message);
        }

        // By default, use master connection key.
        $connectionKey = (string)$connectionValue[ConnectionType::MASTER];

        if ($connectionType === ConnectionType::MASTER || (! isset($connectionValue[ConnectionType::SLAVE]))) {
            return $connectionKey;
        }

        if (is_array($connectionValue[ConnectionType::SLAVE])) {
            // Return random slave connection key.
            $slaveConnections = $connectionValue[ConnectionType::SLAVE];
            $connectionKey = (string)$slaveConnections[array_rand($slaveConnections)];
        }

        return $connectionKey;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function getClassConnectionParameters(string $class, string $connectionType = ConnectionType::MASTER): array
    {
        return $this->getConnectionParameters($this->getClassConnectionKey($class, $connectionType));
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function getConnectionParameters(string $connectionKey): array
    {
        $connections = $this->getConnectionsAndParameters();

        if (!isset($connections[$connectionKey]) || !is_array($connections[$connectionKey])) {
            throw new InvalidConfigurationException(
                sprintf('Connection parameters not set for key \'%s\'.', $connectionKey)
            );
        }

        return $connections[$connectionKey];
    }

    public function getModuleSourceDirectory(): string
    {
        return __DIR__;
    }

    public function getModuleRootDirectory(): string
    {
        return dirname(__DIR__);
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function validate(): void
    {
        $errors = [];

        try {
            $this->validateAccountingProcessingType();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }

        // If accounting processing type is async, validate jobs store class and connection.
        if ($this->getAccountingProcessingType() === AccountingProcessingType::VALUE_ASYNCHRONOUS) {
            try {
                $this->validateJobsStoreClass();
                $this->validateCronTagForJobRunner();
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        try {
            $this->validateDefaultDataTrackerAndProvider();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }

        try {
            $this->validateAdditionalTrackers();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }

        try {
            $this->validateClassToConnectionMap();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }

        try {
            $this->validateTrackerDataRetentionPolicy();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }


        if (!empty($errors)) {
            $message = sprintf('Module configuration validation failed with errors: %s', implode(' ', $errors));
            throw new InvalidConfigurationException($message);
        }
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function validateDefaultDataTrackerAndProvider(): void
    {
        $errors = [];

        // Default data tracker and provider must implement proper interfaces.
        $defaultDataTrackerAndProviderClass = $this->getDefaultDataTrackerAndProviderClass();

        if (!is_subclass_of($defaultDataTrackerAndProviderClass, AuthenticationDataTrackerInterface::class)) {
            $errors[] = sprintf(
                'Default authentication data tracker and provider class \'%s\' does not implement interface \'%s\'.',
                $defaultDataTrackerAndProviderClass,
                AuthenticationDataTrackerInterface::class
            );
        }

        if (!is_subclass_of($defaultDataTrackerAndProviderClass, AuthenticationDataProviderInterface::class)) {
            $errors[] = sprintf(
                'Default authentication data tracker and provider class \'%s\' does not implement interface \'%s\'.',
                $defaultDataTrackerAndProviderClass,
                AuthenticationDataProviderInterface::class
            );
        }

        if (!empty($errors)) {
            throw new InvalidConfigurationException(implode(' ', $errors));
        }
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function validateAdditionalTrackers(): void
    {
        $errors = [];

        // Validate additional trackers
        /**
         * @var string $trackerClass
         */
        foreach ($this->getAdditionalTrackers() as $trackerClass) {
            /** @psalm-suppress DocblockTypeContradiction */
            if (!is_string($trackerClass)) {
                $errors[] = 'Additional trackers array must contain class strings only.';
            } elseif (!is_subclass_of($trackerClass, AuthenticationDataTrackerInterface::class)) {
                $errors[] = sprintf(
                    'Tracker class \'%s\' does not implement interface \'%s\'.',
                    $trackerClass,
                    AuthenticationDataTrackerInterface::class
                );
            }
        }

        if (!empty($errors)) {
            throw new InvalidConfigurationException(implode(' ', $errors));
        }
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function validateConnectionType(string $connectionType): void
    {
        if (!in_array($connectionType, ConnectionType::VALID_OPTIONS)) {
            $message = sprintf(
                'Connection type \'%s\' is not valid. Possible values are: %s.',
                $connectionType,
                implode(', ', ConnectionType::VALID_OPTIONS)
            );
            throw new InvalidConfigurationException($message);
        }
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function validateAccountingProcessingType(): void
    {
        // Only defined accounting processing types are allowed.
        if (!in_array($this->getAccountingProcessingType(), AccountingProcessingType::VALID_OPTIONS)) {
            $message = sprintf(
                'Accounting processing type is not valid; possible values are: %s.',
                implode(', ', AccountingProcessingType::VALID_OPTIONS)
            );

            throw new InvalidConfigurationException($message);
        }
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function validateJobsStoreClass(): void
    {
        // Jobs store class must implement JobsStoreInterface
        $jobsStore = $this->getJobsStoreClass();
        if (!class_exists($jobsStore) || !is_subclass_of($jobsStore, JobsStoreInterface::class)) {
            $message = sprintf(
                'Provided jobs store class \'%s\' does not implement interface \'%s\'.',
                $jobsStore,
                JobsStoreInterface::class
            );

            throw new InvalidConfigurationException($message);
        }
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function validateClassToConnectionMap(): void
    {
        $errors = [];

        $connectionsAndParameters = $this->getConnectionsAndParameters();
        // Each defined class should have defined connection parameters.
        $classToConnectionMap = array_keys($this->getClassToConnectionsMap());
        /** @var string $class */
        foreach ($classToConnectionMap as $class) {
            $connectionKey = $this->getClassConnectionKey($class);
            if (! array_key_exists($connectionKey, $connectionsAndParameters)) {
                $errors[] = sprintf(
                    'Class \'%s\' has connection key \'%s\' set, however parameters for that key are not set.',
                    $class,
                    $connectionKey
                );
            }
        }

        if (!empty($errors)) {
            throw new InvalidConfigurationException(implode(' ', $errors));
        }
    }

    protected function validateCronTagForJobRunner(): void
    {
        $this->getCronTagForJobRunner();
    }

    protected function validateTrackerDataRetentionPolicy(): void
    {
        if ($this->getTrackerDataRetentionPolicy() !== null) {
            $this->getCronTagForTrackerDataRetentionPolicy();
        }
    }

    public function getTrackerDataRetentionPolicy(): ?DateInterval
    {
        /** @var string|null $value */
        $value = $this->getConfiguration()
            ->getOptionalString(self::OPTION_TRACKER_DATA_RETENTION_POLICY, null);

        if (is_null($value)) {
            return null;
        }

        try {
            return new DateInterval($value);
        } catch (Throwable $exception) {
            $message = sprintf('Can not create DateInterval instance using value %s as parameter.', $value);
            throw new InvalidConfigurationException($message);
        }
    }

    public function getCronTagForTrackerDataRetentionPolicy(): string
    {
        return $this->getConfiguration()->getString(self::OPTION_CRON_TAG_FOR_TRACKER_DATA_RETENTION_POLICY);
    }
}
