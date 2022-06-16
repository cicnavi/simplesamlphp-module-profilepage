<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Connections\DoctrineDbal;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use SimpleSAML\Module\accounting\Exceptions\ModuleConfiguration\InvalidConfigurationException;
use SimpleSAML\Module\accounting\Stores\Connections\Pdo\PdoConnection;
use SimpleSAML\Module\accounting\Stores\Interfaces\StoreConnectionInterface;

class Connection implements StoreConnectionInterface
{
    public const PARAMETER_TABLE_PREFIX = 'table_prefix';

    protected \Doctrine\DBAL\Connection $dbal;

    public function __construct(array $parameters)
    {
        try {
            $this->dbal = DriverManager::getConnection($parameters);
        } catch (Exception $e) {
            throw new InvalidConfigurationException('Could not initiate Doctrine DBAL connection with given parameters.');
        }

        $this->tablePrefix = $this->getTablePrefixFromParameters($parameters);
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix ?? '';
    }

    public function dbal(): \Doctrine\DBAL\Connection
    {
        return $this->dbal;
    }

    protected function getTablePrefixFromParameters(array $settings): ?string
    {
        if (! isset($settings[self::PARAMETER_TABLE_PREFIX])) {
            return null;
        }

        if (! is_string($settings[self::PARAMETER_TABLE_PREFIX])) {
            throw new InvalidConfigurationException('Connection table prefix must be string (if set).');
        }

        return $settings[self::PARAMETER_TABLE_PREFIX];
    }

}
