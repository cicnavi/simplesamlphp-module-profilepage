<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting;

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
    /**
     * Default file name for module configuration. Can be overridden, for example, for testing purposes.
     */
    public const FILE_NAME = 'module_accounting.php';

    public const OPTION_USER_ID_ATTRIBUTE_NAME = 'user_id_attribute_name';
    public const OPTION_ACCOUNTING_PROCESSING_TYPE = 'accounting_processing_type';
    public const OPTION_JOBS_STORE = 'jobs_store';
    public const OPTION_JOBS_STORE_CONNECTION = 'jobs_store_connection';
    public const OPTION_DEFAULT_DATA_TRACKER_AND_PROVIDER = 'default_data_tracker_and_provider';
    public const OPTION_DEFAULT_DATA_TRACKER_AND_PROVIDER_CONNECTION = 'default_data_tracker_and_provider_connection';
    public const OPTION_ADDITIONAL_TRACKERS = 'additional_trackers';
    public const OPTION_ADDITIONAL_TRACKERS_TO_CONNECTION_MAP = 'additional_trackers_to_connection_map';
    public const OPTION_CONNECTIONS_AND_PARAMETERS = 'connections_and_parameters';

    /**
     * Contains configuration from module configuration file.
     */
    protected Configuration $configuration;

    /**
     * @throws Exception
     */
    public function __construct(string $fileName = null)
    {
        $fileName = $fileName ?? self::FILE_NAME;

        $this->configuration = Configuration::getConfig($fileName);

        $this->validate();
    }

    /**
     * Get configuration option from module configuration file.
     *
     * @param string $option
     * @return mixed
     */
    public function get(string $option)
    {
        if (! $this->configuration->hasValue($option)) {
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

    public function getAccountingProcessingType(): string
    {
        return $this->getConfiguration()->getString(self::OPTION_ACCOUNTING_PROCESSING_TYPE);
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

    public function getJobsStoreConnectionKey(): string
    {
        return $this->getConfiguration()->getString(self::OPTION_JOBS_STORE_CONNECTION);
    }

    public function getDefaultDataTrackerAndProviderClass(): string
    {
        return $this->getConfiguration()->getString(self::OPTION_DEFAULT_DATA_TRACKER_AND_PROVIDER);
    }

    public function getDefaultDataTrackerAndProviderConnection(): array
    {
        return $this->getConfiguration()->getArray(self::OPTION_DEFAULT_DATA_TRACKER_AND_PROVIDER_CONNECTION);
    }

    public function getDefaultDataTrackerAndProviderWriteConnection(): string
    {
        $connections = $this->getDefaultDataTrackerAndProviderConnection();

        return (string) $connections[ConnectionType::MASTER];
    }

    public function getDefaultDataTrackerAndProviderReadConnection(): string
    {
        // Master connection must be set, so we'll take it as default read connection.
        $readConnection = $this->getDefaultDataTrackerAndProviderWriteConnection();

        $connections = $this->getConfiguration()->getArray(self::OPTION_DEFAULT_DATA_TRACKER_AND_PROVIDER_CONNECTION);

        if (isset($connections[ConnectionType::SLAVE]) && is_array($connections[ConnectionType::SLAVE])) {
            $slaveConnections = $connections[ConnectionType::SLAVE];

            // Pick random slave connection.
            $readConnection = (string) $slaveConnections[array_rand($slaveConnections)];
        }

        return $readConnection;
    }

    public function getAdditionalTrackerClasses(): array
    {
        return $this->getConfiguration()->getArray(self::OPTION_ADDITIONAL_TRACKERS);
    }

    public function getAdditionalTrackerConnectionParameters(string $class): array
    {
        $additionalTrackers = $this->getAdditionalTrackerClasses();

        if (! isset($additionalTrackers[$class])) {
            throw new InvalidConfigurationException(
                sprintf('Tracker class %s not set.', $class)
            );
        }

        return $this->getConnectionParameters((string)$additionalTrackers[$class]);
    }

    public function getConnectionsAndParameters(): array
    {
        return $this->getConfiguration()->getArray(self::OPTION_CONNECTIONS_AND_PARAMETERS);
    }

    public function getConnectionParameters(string $connectionKey): array
    {
        $connections = $this->getConnectionsAndParameters();

        if (! isset($connections[$connectionKey]) || ! is_array($connections[$connectionKey])) {
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
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
            try {
                $this->validateJobsStoreConnection();
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
            $this->validateDefaultDataTrackerAndProviderConnection();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }

        try {
            $this->validateAdditionalTrackers();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }

        if (! empty($errors)) {
            $message = sprintf('Module configuration validation failed with errors: %s', implode(' ', $errors));
            throw new InvalidConfigurationException($message);
        }
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function validateAccountingProcessingType(): void
    {
        // Only defined accounting processing types are allowed.
        if (! in_array($this->getAccountingProcessingType(), AccountingProcessingType::VALID_OPTIONS)) {
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
        if (! class_exists($jobsStore) || ! is_subclass_of($jobsStore, JobsStoreInterface::class)) {
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
    protected function validateJobsStoreConnection(): void
    {
        $jobsStoreConnectionKey = $this->getJobsStoreConnectionKey();

        $connectionsAndParameters = $this->getConnectionsAndParameters();

        if (! array_key_exists($jobsStoreConnectionKey, $connectionsAndParameters)) {
            $message = sprintf(
                'Connection parameters for jobs store connection key \'%s\' not set.',
                $jobsStoreConnectionKey
            );

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

        if (! is_subclass_of($defaultDataTrackerAndProviderClass, AuthenticationDataTrackerInterface::class)) {
            $errors[] = sprintf(
                'Default authentication data tracker and provider class \'%s\' does not implement interface \'%s\'.',
                $defaultDataTrackerAndProviderClass,
                AuthenticationDataTrackerInterface::class
            );
        }

        if (! is_subclass_of($defaultDataTrackerAndProviderClass, AuthenticationDataProviderInterface::class)) {
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
    protected function validateDefaultDataTrackerAndProviderConnection(): void
    {
        $errors = [];

        // Default data tracker and provider must have at least master connection set.
        $defaultDataTrackerAndProviderConnection = $this->getDefaultDataTrackerAndProviderConnection();

        if (! isset($defaultDataTrackerAndProviderConnection[ConnectionType::MASTER])) {
            $errors[] = 'Default data tracker and provider master connection key not set.';
        }

        $connectionsAndParameters = $this->getConnectionsAndParameters();

        // Master connection parameters must exist.
        $defaultDataTrackerAndProviderMasterConnectionKey =
            (string) ($defaultDataTrackerAndProviderConnection[ConnectionType::MASTER] ?? '');

        if (! array_key_exists($defaultDataTrackerAndProviderMasterConnectionKey, $connectionsAndParameters)) {
            $errors[] = sprintf(
                'Default data tracker and provider master connection key \'%s\' parameters not set.',
                $defaultDataTrackerAndProviderMasterConnectionKey
            );
        }

        // If default data tracker and provider slave connections are set, validate them.
        if (isset($defaultDataTrackerAndProviderConnection[ConnectionType::SLAVE])) {
            if (! is_array($defaultDataTrackerAndProviderConnection[ConnectionType::SLAVE])) {
                $errors[] = 'Default data tracker and provider slave connections must be defined in array.';
            } else {
                $defaultDataTrackerAndProviderSlaveConnections =
                    $defaultDataTrackerAndProviderConnection[ConnectionType::SLAVE];

                /** @var string $slaveConnectionKey */
                foreach ($defaultDataTrackerAndProviderSlaveConnections as $slaveConnectionKey) {
                    /** @psalm-suppress DocblockTypeContradiction */
                    if (! is_string($slaveConnectionKey)) {
                        $errors[] = 'Default data tracker and provider slave connection key must be string.';
                    } elseif (! array_key_exists($slaveConnectionKey, $connectionsAndParameters)) {
                        $errors[] = sprintf(
                            'Default data tracker and provider slave connection key \'%s\' parameters not set.',
                            $slaveConnectionKey
                        );
                    }
                }
            }
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

        $connectionsAndParameters = $this->getConnectionsAndParameters();

        // Validate additional trackers
        /**
         * @var string $trackerClass
         * @var string $trackerConnectionKey
         */
        foreach ($this->getAdditionalTrackerClasses() as $trackerClass => $trackerConnectionKey) {
            /** @psalm-suppress DocblockTypeContradiction */
            if (! is_string($trackerClass)) {
                $errors[] = 'The key in additional trackers array must be class string.';
            } elseif (! is_subclass_of($trackerClass, AuthenticationDataTrackerInterface::class)) {
                $errors[] = sprintf(
                    'Tracker class \'%s\' does not implement interface \'%s\'.',
                    $trackerClass,
                    AuthenticationDataTrackerInterface::class
                );
            }

            /** @psalm-suppress DocblockTypeContradiction */
            if (! is_string($trackerConnectionKey)) {
                $errors[] = 'The value for each additional tracker must be connection key string.';
            } elseif (! array_key_exists($trackerConnectionKey, $connectionsAndParameters)) {
                $errors[] = sprintf(
                    'Connection parameters for key \'%s\' used by tracker class \'%s\' not set.',
                    $trackerConnectionKey,
                    $trackerClass
                );
            }
        }

        if (!empty($errors)) {
            throw new InvalidConfigurationException(implode(' ', $errors));
        }
    }
}
