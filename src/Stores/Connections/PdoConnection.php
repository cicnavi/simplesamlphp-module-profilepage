<?php

namespace SimpleSAML\Module\accounting\Stores\Connections;

use SimpleSAML\Module\accounting\Exceptions\ModuleConfiguration\InvalidConfigurationException;
use SimpleSAML\Module\accounting\Stores\Interfaces\StoreConnectionInterface;

class PdoConnection implements StoreConnectionInterface
{
    public const OPTION_DSN = 'dsn';
    public const OPTION_USERNAME = 'username';
    public const OPTION_PASSWORD = 'password';
    public const OPTION_DRIVER_OPTIONS = 'options';
    public const OPTION_TABLE_PREFIX = 'table_prefix';

    protected string $dsn;
    protected ?string $username;
    protected ?string $password;
    protected ?array $options;

    protected \PDO $db;

    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        ?array $options = null
    ) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;

        try {
            $this->db = new \PDO($this->dsn, $this->username, $this->password, $this->options);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $exception) {
            throw new InvalidConfigurationException("PdoConnection error: " . $exception->getMessage());
        }
    }
}
