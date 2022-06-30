<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Connections\Pdo;

use PDO;
use PDOException;
use SimpleSAML\Module\accounting\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\accounting\Stores\Interfaces\ConnectionInterface;

class PdoConnection implements ConnectionInterface
{
    public const OPTION_DSN = 'dsn';
    public const OPTION_USERNAME = 'username';
    public const OPTION_PASSWORD = 'password';
    public const OPTION_DRIVER_OPTIONS = 'driver_options';
    public const OPTION_TABLE_PREFIX = 'table_prefix';

    protected string $dsn;
    protected ?string $username;
    protected ?string $password;
    protected ?array $options;
    protected ?string $tablePrefix;

    protected PDO $pdo;

    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        ?array $options = null,
        ?string $tablePrefix = null
    ) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
        $this->tablePrefix = $tablePrefix;

        try {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->options);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            throw new InvalidConfigurationException("PdoConnection error: " . $exception->getMessage());
        }
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix ?? '';
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
