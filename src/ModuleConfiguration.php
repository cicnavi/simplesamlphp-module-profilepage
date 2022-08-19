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

    public const OPTION_USER_ID_ATTRIBUTE = 'user_id_attribute';
    public const OPTION_ACCOUNTING_PROCESSING_TYPE = 'accounting_processing_type';
    public const OPTION_JOBS_STORE = 'jobs_store';
    public const OPTION_ALL_STORE_CONNECTIONS_AND_PARAMETERS = 'all_store_connection_and_parameters';
    public const OPTION_STORE_TO_CONNECTION_KEY_MAP = 'store_to_connection_key_map';

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

    public function getUserIdAttribute(): string
    {
        return $this->getConfiguration()->getString(self::OPTION_USER_ID_ATTRIBUTE);
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

    public function getJobsStore(): string
    {
        return $this->getConfiguration()->getString(self::OPTION_JOBS_STORE);
    }

    public function getStoreConnection(string $store): string
    {
        $connectionMap = $this->getStoreToConnectionMap();

        if (! isset($connectionMap[$store])) {
            throw new InvalidConfigurationException(
                sprintf('Connection for store %s is not set.', $store)
            );
        }

        return (string) $connectionMap[$store];
    }

    public function getStoreToConnectionMap(): array
    {
        return $this->getConfiguration()->getArray(self::OPTION_STORE_TO_CONNECTION_KEY_MAP);
    }

    public function getAllStoreConnectionsAndParameters(): array
    {
        return $this->getConfiguration()->getArray(self::OPTION_ALL_STORE_CONNECTIONS_AND_PARAMETERS);
    }

    public function getStoreConnectionParameters(string $connection): array
    {
        $connections = $this->getAllStoreConnectionsAndParameters();

        if (! isset($connections[$connection]) || ! is_array($connections[$connection])) {
            throw new InvalidConfigurationException(
                sprintf('Settings for connection %s not set', $connection)
            );
        }

        return $connections[$connection];
    }

    public function getModuleSourceDirectory(): string
    {
        return __DIR__;
    }

    public function getModuleRootDirectory(): string
    {
        return dirname(__DIR__);
    }

    protected function validate()
    {
        $errors = [];

        // Only defined accounting processing types are allowed.
        if (! in_array($this->getAccountingProcessingType(), AccountingProcessingType::VALID_OPTIONS)) {
            $errors[] = sprintf(
                'Accounting processing type is not valid. Possible values are: %s.',
                implode(', ', AccountingProcessingType::VALID_OPTIONS)
            );
        }

        // Jobs store class must implement JobsStoreInterface
        $jobsStore = $this->getConfiguration()->getString(self::OPTION_JOBS_STORE);
        if (! class_exists($jobsStore) || ! is_subclass_of($jobsStore, JobsStoreInterface::class)) {
            throw new InvalidConfigurationException('Provided jobs store class does not implement JobsStoreInterface.');
        }

        if (! empty($errors)) {
            $message = sprintf('Module configuration validation failed. Errors were: %s.', implode('; '));
            throw new InvalidConfigurationException($message);
        }
    }
}
