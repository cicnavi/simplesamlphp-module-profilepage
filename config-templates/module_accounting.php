<?php

declare(strict_types=1);

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores;

$config = [
    /**
     * User ID attribute, one that is always available and that is unique to all users.
     * If this attribute is not available, accounting will not be performed for that user.
     *
     * Examples:
     * urn:oasis:names:tc:SAML:attribute:subject-id
     * eduPersonTargetedID
     * eduPersonPrincipalName
     * eduPersonUniqueID
     */
    ModuleConfiguration::OPTION_USER_ID_ATTRIBUTE_NAME => 'urn:oasis:names:tc:SAML:attribute:subject-id',

    /**
     * Accounting processing type. There are two possible types: 'synchronous' and 'asynchronous'.
     * - 'synchronous': accounting processing will be performed during authentication itself (slower)
     * - 'asynchronous': for each authentication event a new job will be created for later processing (faster,
     *   but requires setting up job storage and a cron entry).
     */
    ModuleConfiguration::OPTION_ACCOUNTING_PROCESSING_TYPE =>
        ModuleConfiguration\AccountingProcessingType::VALUE_SYNCHRONOUS,

    /**
     * Jobs store class. In case of case the 'asynchronous' accounting processing type was set, this determines
     * which class will be used to store jobs. The class must implement Stores\Interfaces\JobsStoreInterface.
     */
    ModuleConfiguration::OPTION_JOBS_STORE_CLASS => Stores\Jobs\DoctrineDbal\Store::class,

    /**
     * Jobs store connection key. Determines which connection will be used for job persistence. The key must
     * be present in the "connections and their parameters" configuration option below.
     */
    ModuleConfiguration::OPTION_JOBS_STORE_CONNECTION_KEY => 'doctrine_dbal_pdo_mysql',

    /**
     * Store connection for particular store. Can be used to set different connections for different stores.
     * TODO mivanci convert this to tracker to connection key map
     */
    ModuleConfiguration::OPTION_STORE_TO_CONNECTION_KEY_MAP => [
        Stores\Jobs\DoctrineDbal\Store::class => 'doctrine_dbal_pdo_mysql',
    ],

    /**
     * Connections and their parameters.
     *
     * Any compatible Doctrine DBAL implementation can be used:
     * https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
     * There are additional parameters for: table prefix.
     * Examples for mysql and sqlite are provided below.
     */
    ModuleConfiguration::OPTION_ALL_STORE_CONNECTIONS_AND_PARAMETERS => [
        'doctrine_dbal_pdo_mysql' => [
            'driver' => 'pdo_mysql', // (string): The built-in driver implementation to use
            'user' => 'user', // (string): Username to use when connecting to the database.
            'password' => 'password', // (string): Password to use when connecting to the database.
            'host' => 'host', // (string): Hostname of the database to connect to.
            'port' => 3306, // (integer): Port of the database to connect to.
            'dbname' => 'dbname', // (string): Name of the database/schema to connect to.
            //'unix_socket' => 'unix_socet', // (string): Name of the socket used to connect to the database.
            'charset' => 'utf8', // (string): The charset used when connecting to the database.
            //'url' => 'mysql://user:secret@localhost/mydb?charset=utf8', // ...alternative way of providing parameters.
            // Additional parameters not originally available in Doctrine DBAL
            'table_prefix' => '', // (string): Prefix for each table.
        ],
        'doctrine_dbal_pdo_sqlite' => [
            'driver' => 'pdo_sqlite', // (string): The built-in driver implementation to use
            'path' => '/path/to/db.sqlite', // (string): The filesystem path to the database file.
            // Mutually exclusive with memory. path takes precedence.
            'memory' => false, // (boolean): True if the SQLite database should be in-memory (non-persistent).
            // Mutually exclusive with path. path takes precedence.
            //'url' => 'sqlite:////path/to/db.sqlite // ...alternative way of providing path parameter.
            //'url' => 'sqlite:///:memory:' // ...alternative way of providing memory parameter.
            // Additional parameters not originally available in Doctrine DBAL
            'table_prefix' => '', // (string): Prefix for each table.
        ],
    ],
];
