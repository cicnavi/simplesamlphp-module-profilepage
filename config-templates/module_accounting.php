<?php

declare(strict_types=1);

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores;
use SimpleSAML\Module\accounting\Trackers;
use SimpleSAML\Module\accounting\Providers;

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
     * - 'synchronous': accounting processing will be performed during authentication itself (slower authentication)
     * - 'asynchronous': for each authentication event a new job will be created for later processing (faster
     *   authentication, but requires setting up job storage and a cron entry).
     */
    ModuleConfiguration::OPTION_ACCOUNTING_PROCESSING_TYPE =>
        ModuleConfiguration\AccountingProcessingType::VALUE_SYNCHRONOUS,

    /**
     * Jobs store class. In case of the 'asynchronous' accounting processing type, this determines which class
     * will be used to store jobs. The class must implement Stores\Interfaces\JobsStoreInterface. The default
     * class Stores\Jobs\DoctrineDbal\Store expects Doctrine DBAL compatible connection to be set in
     * "class-to-connection map" setting.
     */
    ModuleConfiguration::OPTION_JOBS_STORE_CLASS => Stores\Jobs\DoctrineDbal\Store::class,

    /**
     * Enabled tracker classes. Trackers will process and persist authentication data to proper data store.
     * Tracker classes must implement Trackers\Interfaces\TrackerInterface.
     */
    ModuleConfiguration::OPTION_ENABLED_TRACKERS => [
        /**
         * Track each authentication event for idp / sp / user combination, and any change in idp / sp metadata or
         * released user attributes. Each authentication event record will have data used and released at the
         * time of the authentication event (versioned data). It can also be used as an authentication data
         * provider. This tracker expects Doctrine DBAL compatible connection to be set in
         * "class-to-connection map" setting.
         */
        Trackers\DoctrineDbal\VersionedAuthentication::class,
    ],

    /**
     * Class which will be used as authentication data provider. This means it will serve as data source
     * for accounted authentication data when presented in UI. This class must implement
     * Providers\Interfaces\AuthenticationDataProviderInterface.
     */
    ModuleConfiguration::OPTION_AUTHENTICATION_DATA_PROVIDER_CLASS =>
        /**
         * In addition to tracking data, this class can also serve as authentication data provider. This tracker
         * expects Doctrine DBAL compatible connection to be set in "class-to-connection map" setting.
         */
        Trackers\DoctrineDbal\VersionedAuthentication::class,

    /**
     * Class-to-connection map. Can be used to set different connections for different classes (stores, trackers,
     * data providers...).
     */
    ModuleConfiguration::OPTION_CLASS_TO_CONNECTION_MAP => [
        Stores\Jobs\DoctrineDbal\Store::class => 'doctrine_dbal_pdo_mysql',
        Trackers\DoctrineDbal\VersionedAuthentication::class => 'doctrine_dbal_pdo_mysql',
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
