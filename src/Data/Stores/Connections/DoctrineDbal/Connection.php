<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Connections\DoctrineDbal;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use SimpleSAML\Module\profilepage\Data\Stores\Interfaces\ConnectionInterface;
use SimpleSAML\Module\profilepage\Exceptions\InvalidConfigurationException;

class Connection implements ConnectionInterface
{
    final public const PARAMETER_TABLE_PREFIX = 'table_prefix';

    protected \Doctrine\DBAL\Connection $dbal;
    protected ?string $tablePrefix;

    public function __construct(array $parameters)
    {
        try {
            /** @psalm-suppress MixedArgumentTypeCoercion */
            $this->dbal = DriverManager::getConnection($parameters);
        } catch (Exception) {
            throw new InvalidConfigurationException(
                'Could not initiate Doctrine DBAL connection with given parameters.'
            );
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

    public function preparePrefixedTableName(string $tableName): string
    {
        return $this->getTablePrefix() . $tableName;
    }
}
