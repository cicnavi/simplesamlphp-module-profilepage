<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting;

use SimpleSAML\Configuration;
use SimpleSAML\Module\accounting\Exceptions\ModuleConfiguration\InvalidConfigurationException;
use SimpleSAML\Module\accounting\ModuleConfiguration\AccountingProcessingType;

class ModuleConfiguration
{
    /**
     * Default file name for module configuration. Can be overridden, for example, for testing purposes.
     */
    public const FILE_NAME = 'module_accounting.php';

    public const OPTION_USER_ID_ATTRIBUTE = 'user_id_attribute';
    public const OPTION_ACCOUNTING_PROCESSING_TYPE = 'accounting_processing_type';
    public const OPTION_JOBS_STORE = 'jobs_store';
    public const OPTION_CONNECTION_SETTINGS = 'store_connection_settings';
    public const OPTION_STORE_TO_CONNECTION_MAP = 'store_to_connection_map';

    /**
     * Contains configuration from module configuration file.
     */
    protected Configuration $configuration;

    /**
     * @throws \Exception
     */
    public function __construct(string $fileName = null)
    {
        $fileName = $fileName ?? self::FILE_NAME;

        $this->configuration = Configuration::getConfig($fileName);
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

    /**
     * Get underlying SimpleSAMLphp Configuration instance.
     *
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
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
        return $this->getConfiguration()->getArray(self::OPTION_STORE_TO_CONNECTION_MAP);
    }

    public function getAllConnectionsAndTheirSettings(): array
    {
        return $this->getConfiguration()->getArray(self::OPTION_CONNECTION_SETTINGS);
    }

    public function getConnectionSettins(string $connection): array
    {
        $connections = $this->getAllConnectionsAndTheirSettings();

        if (! is_array($connections[$connection])) {
            throw new InvalidConfigurationException(
                sprintf('Settings for connection %s not set', $connection)
            );
        }

        return $connections[$connection];
    }
}
