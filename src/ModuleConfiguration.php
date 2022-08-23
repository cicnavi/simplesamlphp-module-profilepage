<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting;

use Exception;
use SimpleSAML\Configuration;
use SimpleSAML\Module\accounting\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\accounting\ModuleConfiguration\AccountingProcessingType;
use SimpleSAML\Module\accounting\Stores\Interfaces\JobsStoreInterface;

class ModuleConfiguration
{
    /**
     * Default file name for module configuration. Can be overridden, for example, for testing purposes.
     */
    public const FILE_NAME = 'module_accounting.php';

    public const OPTION_USER_ID_ATTRIBUTE_NAME = 'user_id_attribute_name';
    public const OPTION_ACCOUNTING_PROCESSING_TYPE = 'accounting_processing_type';
    public const OPTION_JOBS_STORE_CLASS = 'jobs_store_class';
    public const OPTION_CONNECTIONS_AND_PARAMETERS = 'connections_and_parameters';
    public const OPTION_CLASS_TO_CONNECTION_MAP = 'class_to_connection_map';
    public const OPTION_ENABLED_TRACKERS = 'enabled_trackers';
    public const OPTION_AUTHENTICATION_DATA_PROVIDER_CLASS = 'authentication_data_provider_class';

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
        return $this->getConfiguration()->getString(self::OPTION_JOBS_STORE_CLASS);
    }

    public function getClassConnectionParameters(string $class): string
    {
        $connectionMap = $this->getClassToConnectionMap();

        if (! isset($connectionMap[$class])) {
            throw new InvalidConfigurationException(
                sprintf('Connection for class %s is not set.', $class)
            );
        }

        return (string) $connectionMap[$class];
    }

    public function getClassToConnectionMap(): array
    {
        return $this->getConfiguration()->getArray(self::OPTION_CLASS_TO_CONNECTION_MAP);
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

    protected function validate(): void
    {
        $errors = [];

        // Only defined accounting processing types are allowed.
        if (! in_array($this->getAccountingProcessingType(), AccountingProcessingType::VALID_OPTIONS)) {
            $errors[] = sprintf(
                'Accounting processing type is not valid; possible values are: %s.',
                implode(', ', AccountingProcessingType::VALID_OPTIONS)
            );
        }

        // If accounting processing type is async, check if proper jobs store class was provided.
        if ($this->getAccountingProcessingType() === AccountingProcessingType::VALUE_ASYNCHRONOUS) {
            // Jobs store class must implement JobsStoreInterface
            $jobsStore = $this->getJobsStoreClass();
            if (! class_exists($jobsStore) || ! is_subclass_of($jobsStore, JobsStoreInterface::class)) {
                $errors[] = sprintf(
                    'Provided jobs store class \'%s\' does not implement %s.',
                    $jobsStore,
                    JobsStoreInterface::class
                );
            }
        }

        if (! empty($errors)) {
            $message = sprintf('Module configuration validation failed with errors: %s', implode(' ', $errors));
            throw new InvalidConfigurationException($message);
        }
    }
}
