<?php

namespace SimpleSAML\Module\accounting\Stores\Connections\Pdo\MySql;

use SimpleSAML\Module\accounting\Stores\Connections\Pdo\PdoConnection;
use SimpleSAML\Module\accounting\Stores\Jobs\Pdo\MySql\Migrations\Migration202214061323CreateJobsMigrationTable;

class MySqlPdoMigrator
{
    protected PdoConnection $pdoConnection;

    public const TABLE_NAME = 'migrations';

    public function __construct(PdoConnection $pdoConnection)
    {
        $this->pdoConnection = $pdoConnection;
    }

    public function getImplementedMigrations(string $scope): array
    {
        $this->ensureMigrationTableExistence();

        $tableName = $this->getTableName();

        $sql = <<<SQL
            SELECT version 
            FROM $tableName
            WHERE scope = :scope
        SQL;

        $pdoStatement = $this->pdoConnection->getPdo()->prepare($sql);
        $pdoStatement->execute([':scope' => $scope]);
        $result = $pdoStatement->fetchAll(\PDO::FETCH_COLUMN);

        if (! is_array($result)) {
            return [];
        }

        return $result;
    }

    public function getTableName(): string
    {
        return $this->pdoConnection->getTablePrefix() . self::TABLE_NAME;
    }

    protected function ensureMigrationTableExistence(): void
    {
        $tableName = $this->getTableName();

        $migrationTableSql = <<<SQL
            CREATE TABLE IF NOT EXISTS `$tableName` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `scope` varchar(256) NOT NULL,
              `version` varchar(256) NOT NULL,
              PRIMARY KEY (`id`)
            ) COMMENT='Default accounting migrations table';
        SQL;

        $this->pdoConnection->getPdo()->exec($migrationTableSql);
    }
}
