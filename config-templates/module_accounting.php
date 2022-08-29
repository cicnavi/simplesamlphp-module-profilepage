<?php

declare(strict_types=1);

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Providers;
use SimpleSAML\Module\accounting\Stores;
use SimpleSAML\Module\accounting\Trackers;

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
     */
    ModuleConfiguration::OPTION_ACCOUNTING_PROCESSING_TYPE =>
        /**
         * Synchronous option, meaning accounting processing will be performed during authentication itself
         * (slower authentication).
         */
        ModuleConfiguration\AccountingProcessingType::VALUE_SYNCHRONOUS,
        /**
         * Asynchronous option, meaning for each authentication event a new job will be created for later processing
         * (faster authentication, but requires setting up job storage and a cron entry).
         */
        //ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS,

    /**
     * Jobs store class. In case of the 'asynchronous' accounting processing type, this determines which class
     * will be used to store jobs. The class must implement Stores\Interfaces\JobsStoreInterface.
     */
    ModuleConfiguration::OPTION_JOBS_STORE =>
        /**
         * Default jobs store class which expects Doctrine DBAL compatible connection to be set below.
         */
        Stores\Jobs\DoctrineDbal\Store::class,

    /**
     * Default data tracker and provider to be used for accounting and as a source for data display in SSP UI.
     * This class must implement Trackers\Interfaces\AuthenticationDataTrackerInterface and
     * Providers\Interfaces\AuthenticationDataProviderInterface
     */
    ModuleConfiguration::OPTION_DEFAULT_DATA_TRACKER_AND_PROVIDER =>
        /**
         * Track each authentication event for idp / sp / user combination, and any change in idp / sp metadata or
         * released user attributes. Each authentication event record will have data used and released at the
         * time of the authentication event (versioned idp / sp / user data). This tracker can also be
         * used as an authentication data provider. It expects Doctrine DBAL compatible connection
         * to be set below. Internally it uses store class
         * Stores\Data\DoctrineDbal\DoctrineDbal\Versioned\Store::class.
         */
        Trackers\Authentication\DoctrineDbal\Versioned\Tracker::class,


    /**
     * Additional trackers to run besides default data tracker. These trackers will typically only process and
     * persist authentication data to proper data store, and won't be used to display data in SSP UI.
     * These tracker classes must implement Trackers\Interfaces\AuthenticationDataTrackerInterface.
     */
    ModuleConfiguration::OPTION_ADDITIONAL_TRACKERS => [
        // TODO mivanci at least one more tracker and its connection
        // tracker-class
    ],

    /**
     * Map of classes (stores, trackers, providers, ...) and connection keys, which defines which connections will
     * be used. Value for connection key can be string, or it can be an array with two connection types as keys:
     * master or slave. Master connection is single connection which will be used to write data to, and it
     * must be set. If no slave connections are set, master will also be used to read data from. Slave
     * connections are defined as array of strings. If slave connections are set, random one will
     * be picked to read data from.
     */
    ModuleConfiguration::OPTION_CLASS_TO_CONNECTION_MAP => [
        /**
         * Connection key to be used by jobs store class.
         */
        Stores\Jobs\DoctrineDbal\Store::class => 'doctrine_dbal_pdo_mysql',
        /**
         * Connection key to be used by this data tracker and provider.
         */
        Trackers\Authentication\DoctrineDbal\Versioned\Tracker::class => [
            ModuleConfiguration\ConnectionType::MASTER => 'doctrine_dbal_pdo_mysql',
            ModuleConfiguration\ConnectionType::SLAVE => [
                'doctrine_dbal_pdo_mysql',
            ],
        ],
    ],

    /**
     * Connections and their parameters.
     */
    ModuleConfiguration::OPTION_CONNECTIONS_AND_PARAMETERS => [
        /**
         * Examples for Doctrine DBAL compatible mysql and sqlite connection parameters are provided below (more info
         * on https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html).
         * There are additional parameters for: table prefix.
         */
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
