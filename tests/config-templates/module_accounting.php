<?php

declare(strict_types=1);

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Connections\PdoConnection;
use SimpleSAML\Module\accounting\Stores\Jobs\MySqlJobsStore;

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
    ModuleConfiguration::OPTION_JOBS_STORE => MySqlJobsStore::class,

    /**
     * Store connection for particular store. Can be used to set different connections for different stores.
     */
    ModuleConfiguration::OPTION_STORE_TO_CONNECTION_MAP => [
        MySqlJobsStore::class => PdoConnection::class,
    ],

    /**
     * Store connection settings.
     */
    ModuleConfiguration::OPTION_CONNECTION_SETTINGS => [
        PdoConnection::class => [
            PdoConnection::OPTION_DSN => 'mysql:host=localhost;dbname=accounting;charset=utf8',
            PdoConnection::OPTION_USERNAME => 'user',
            PdoConnection::OPTION_PASSWORD => 'pass',
            PdoConnection::OPTION_DRIVER_OPTIONS => [
                \PDO::ATTR_PERSISTENT => false,
            ],
            PdoConnection::OPTION_TABLE_PREFIX => '',
        ],
    ],
];
