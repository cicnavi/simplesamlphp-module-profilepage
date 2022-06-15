<?php

declare(strict_types=1);

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\Pdo\PdoConnection;
use SimpleSAML\Module\accounting\Stores\Jobs\Pdo\MySql\MySqlPdoJobsStore;

$config = [
    /**
     * User ID attribute, one that is always available and that is unique to all users.
     * If this attribute is not available, accounting will not be performed for that user.
     *
     * Examples:
     * urn:oasis:names:tc:SAML:attribute:subject-id
     * eduPersonUniqueId
     * eduPersonPrincipalName
     */
    ModuleConfiguration::OPTION_USER_ID_ATTRIBUTE => 'urn:oasis:names:tc:SAML:attribute:subject-id',

    /**
     * Accounting processing type. There are two possible types: 'synchronous' and 'asynchronous'.
     * - 'synchronous': accounting processing will be performed during authentication itself (slower)
     * - 'asynchronous': for each authentication event a new job will be created for later processing (faster,
     *   but requires setting up job storage and a cron entry).
     */
    ModuleConfiguration::OPTION_ACCOUNTING_PROCESSING_TYPE =>
        ModuleConfiguration\AccountingProcessingType::VALUE_SYNCHRONOUS,

    /**
     * Jobs store. Determines which of the available stores will be used to store jobs in case the 'asynchronous'
     * accounting processing type was set.
     */
    ModuleConfiguration::OPTION_JOBS_STORE => MySqlPdoJobsStore::class,

    /**
     * Store connection for particular store. Can be used to set different connections for different stores.
     */
    ModuleConfiguration::OPTION_STORE_TO_CONNECTION_KEY_MAP => [
        MySqlPdoJobsStore::class => 'mysql',
    ],

    /**
     * Store connections and their settings.
     */
    ModuleConfiguration::OPTION_ALL_STORE_CONNECTIONS_AND_SETTINGS => [
        'mysql' => [
            PdoConnection::OPTION_DSN => 'mysql:host=localhost;port=3306;dbname=accounting;charset=utf8',
            PdoConnection::OPTION_USERNAME => 'user',
            PdoConnection::OPTION_PASSWORD => 'pass',
            PdoConnection::OPTION_DRIVER_OPTIONS => [
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ],
            PdoConnection::OPTION_TABLE_PREFIX => '',
        ],
        'sqlite' => [
            PdoConnection::OPTION_DSN => 'sqlite:/path/to/db_file.sqlite3', // file database (folder must be writable)
            //PdoConnection::OPTION_DSN => 'sqlite::memory:', // in memory database
            //PdoConnection::OPTION_DSN => 'sqlite:', // temporary database
            PdoConnection::OPTION_DRIVER_OPTIONS => [
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ],
            PdoConnection::OPTION_TABLE_PREFIX => '',
        ],
    ],
];
