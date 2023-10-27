<?php

declare(strict_types=1);

use SimpleSAML\Module\accounting\Data\Providers;
use SimpleSAML\Module\accounting\Data\Stores;
use SimpleSAML\Module\accounting\Data\Trackers;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use \SimpleSAML\Module\accounting\Services\Serializers;

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
     * Default authentication source which will be used when authenticating users in SimpleSAMLphp Profile Page.
     */
    ModuleConfiguration::OPTION_DEFAULT_AUTHENTICATION_SOURCE => 'default-sp',

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
         * PhpRedis class Redis jobs store. Expects class Redis compatible connection to be set bellow.
         * Note: PhpRedis must be installed: https://github.com/phpredis/phpredis#installation
         */
        //Stores\Jobs\PhpRedis\RedisStore::class,

    /**
     * Providers
     *
     * VersionedDataProvider classes are used to fetch data about users in order to show it in users profile page UI.
     * Each provider can also include tracking capability, which will be triggered / used automatically.
     *
     * Connected services provider is a class which will be used to provide summary data about services that user
     * has authenticated at, including authentication count for particular service, and the first and last
     * authentication time. For OIDC services (Relying Parties, RPs), user can also revoke active tokens.
     *
     * Given class must implement interface Providers\Interfaces\ConnectedServicesInterface.
     *
     * This option can be set to null, meaning no connected services tracking will take place.
     */
    ModuleConfiguration::OPTION_PROVIDER_FOR_CONNECTED_SERVICES =>
        /**
         * Default connected services provider which expects Doctrine DBAL compatible connection to be set below.
         * CurrentDataProvider only gathers current (latest information) about the service (there is no
         * versioning, so it's faster). VersionedDataProvider keeps track of any changes in data about
         * the service.
         */
        Providers\ConnectedServices\DoctrineDbal\CurrentDataProvider::class,
        //Providers\ConnectedServices\DoctrineDbal\VersionedDataProvider::class,

    /**
     * Activity provider is a class which will be used to provide list of authentication events which includes info
     * about the services, user data sent to the service, and the time of authentication.
     *
     * Given class must implement interface Providers\Interfaces\ActivityInterfaceData.
     *
     * This option can be set to null, meaning no activity tracking will take place.
     */
    ModuleConfiguration::OPTION_PROVIDER_FOR_ACTIVITY =>
        /**
         * Default activity provider which expects Doctrine DBAL compatible connection to be set below.
         * CurrentDataProvider only gathers current (latest information) about the service (there is no
         * versioning, so it's faster). VersionedDataProvider keeps track of any changes in data about
         * the service.
         */
        Providers\Activity\DoctrineDbal\CurrentDataProvider::class,
        //Providers\Activity\DoctrineDbal\VersionedDataProvider::class,

    /**
     * Trackers
     *
     * List of additional tracker classes to be used for accounting (processing and persisting authentication data).
     * These classes must implement Trackers\Interfaces\DataTrackerInterface
     */
    ModuleConfiguration::OPTION_ADDITIONAL_TRACKERS => [
        // some-tracker-class
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
         * Jobs store connection keys.
         */
        Stores\Jobs\DoctrineDbal\Store::class => 'doctrine_dbal_pdo_mysql',
        Stores\Jobs\PhpRedis\RedisStore::class => 'phpredis_class_redis',
        /**
         * Data provider connection keys.
         */
        Providers\ConnectedServices\DoctrineDbal\VersionedDataProvider::class => [
            ModuleConfiguration\ConnectionType::MASTER => 'doctrine_dbal_pdo_mysql',
            ModuleConfiguration\ConnectionType::SLAVE => [
                'doctrine_dbal_pdo_mysql',
            ],
        ],
        Providers\ConnectedServices\DoctrineDbal\CurrentDataProvider::class => [
            ModuleConfiguration\ConnectionType::MASTER => 'doctrine_dbal_pdo_mysql',
            ModuleConfiguration\ConnectionType::SLAVE => [
                'doctrine_dbal_pdo_mysql',
            ],
        ],
        Providers\Activity\DoctrineDbal\CurrentDataProvider::class => [
            ModuleConfiguration\ConnectionType::MASTER => 'doctrine_dbal_pdo_mysql',
            ModuleConfiguration\ConnectionType::SLAVE => [
                'doctrine_dbal_pdo_mysql',
            ],
        ],
        Providers\Activity\DoctrineDbal\VersionedDataProvider::class => [
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
            'driver' => 'pdo_mysql', // (string): The built-in driver implementation to use.
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
            'driver' => 'pdo_sqlite', // (string): The built-in driver implementation to use.
            'path' => '/path/to/db.sqlite', // (string): The filesystem path to the database file.
            // Mutually exclusive with memory. path takes precedence.
            'memory' => false, // (boolean): True if the SQLite database should be in-memory (non-persistent).
            // Mutually exclusive with path. path takes precedence.
            //'url' => 'sqlite:////path/to/db.sqlite // ...alternative way of providing path parameter.
            //'url' => 'sqlite:///:memory:' // ...alternative way of providing memory parameter.
            // Additional parameters not originally available in Doctrine DBAL
            'table_prefix' => '', // (string): Prefix for each table.
        ],
        /**
         * Example for PhpRedis class Redis (https://github.com/phpredis/phpredis#class-redis).
         */
        'phpredis_class_redis' => [
            'host' => '127.0.0.1', // (string): can be a host, or the path to a unix domain socket.
            'port' => 6379, // (int): default port is 6379, should be -1 for unix domain socket.
            'connectTimeout' => 1, // (float): value in seconds (default is 0 meaning unlimited).
            //'retryInterval' => 500, // (int): value in milliseconds (optional, default 0)
            //'readTimeout' => 0, // (float): value in seconds (default is 0 meaning unlimited)
            'auth' => ['phpredis', 'phpredis'], // (mixed): authentication information
            'keyPrefix' => 'ssp_accounting:'
        ],
    ],

    /**
     * Job runner fine-grained configuration options.
     *
     * Maximum execution time for the job runner. You can use this option to limit job runner activity by combining
     * when the job runner will run (using cron configuration) and how long the job runner will be active
     * (execution time). This can be false, meaning it will run indefinitely, or can be set as a duration
     * for DateInterval, examples being below. Note that when the job runner is run using Cron user
     * interface in SimpleSAMLphp, the duration will be taken from the 'max_execution_time' ini
     * setting, and will override this setting if ini setting is shorter.
     * @see https://www.php.net/manual/en/dateinterval.construct.php
     */
    ModuleConfiguration::OPTION_JOB_RUNNER_MAXIMUM_EXECUTION_TIME => false,
    //ModuleConfiguration::OPTION_JOB_RUNNER_MAXIMUM_EXECUTION_TIME => 'PT9M', // 9 minutes
    //ModuleConfiguration::OPTION_JOB_RUNNER_MAXIMUM_EXECUTION_TIME => 'PT59M', // 59 minutes
    //ModuleConfiguration::OPTION_JOB_RUNNER_MAXIMUM_EXECUTION_TIME => 'P1D', // 1 day

    /**
     * Number of processed jobs after which the job runner should take a 1-second pause.
     *
     * This option was introduced so that the job runner can act in a more resource friendly fashion when facing
     * backend store. If the value is false, there will be no pause.
     */
    ModuleConfiguration::OPTION_JOB_RUNNER_SHOULD_PAUSE_AFTER_NUMBER_OF_JOBS_PROCESSED => 10,

    /**
     * VersionedDataTracker data retention policy.
     *
     * Determines how long the tracked data will be stored. If null, data will be stored indefinitely. Otherwise, it
     * can be set as a duration for DateInterval, examples being below. For this to work, a cron tag must also
     * be configured.
     */
    ModuleConfiguration::OPTION_TRACKER_DATA_RETENTION_POLICY => null,
    //ModuleConfiguration::OPTION_TRACKER_DATA_RETENTION_POLICY => 'P30D', // 30 days
    //ModuleConfiguration::OPTION_TRACKER_DATA_RETENTION_POLICY => 'P6M', // 6 months
    //ModuleConfiguration::OPTION_TRACKER_DATA_RETENTION_POLICY => 'P1Y', // 1 year


    /**
     * Cron tags.
     *
     * Job runner tag designates the cron tag to use when running accounting jobs. Make sure to add this tag to
     * the cron module configuration in case of the 'asynchronous' accounting processing type.
     */
    ModuleConfiguration::OPTION_CRON_TAG_FOR_JOB_RUNNER => 'accounting_job_runner',

    /**
     * VersionedDataTracker data retention policy tag designates the cron tag to use for enforcing data retention
     * policy. Make sure to add this tag to the cron module configuration if data retention policy is different
     * from null.
     */
    ModuleConfiguration::OPTION_CRON_TAG_FOR_TRACKER_DATA_RETENTION_POLICY =>
        'accounting_tracker_data_retention_policy',

    /**
     * Enable or disable 'action buttons'. Action buttons are displayed on 'Personal data' page, and can be used to
     * provide, for example, links to relevant endpoint like to change a password, send email to support, etc.
     *
     * Note that you should override the action buttons Twig template using standard SimpleSAMLphp custom theming
     * features: https://simplesamlphp.org/docs/stable/simplesamlphp-theming
     *
     * The path to the action buttons template file is: modules/accounting/templates/user/includes/_action-buttons.twig.
     * You can check the source of that file to see a sample dropdown, and a comment about the available variables.
     *
     * So, when creating a custom theme action buttons file, place it in:
     * modules/{mymodule}/themes/{fancytheme}/accounting/user/includes/_action-buttons.twig
     */
    ModuleConfiguration::OPTION_ACTION_BUTTONS_ENABLED => false,

	/**
	 * Serializer class which will be used to serialize data to make it storable. For example, this will be
	 * used for when storing authentication events as jobs.
	 * Class must implement SimpleSAML\Module\accounting\Interfaces\SerializerInterface
	 */
	ModuleConfiguration::OPTION_SERIALIZER =>
		Serializers\PhpSerializer::class,
		//Serializers\JsonSerializer::class,
];
