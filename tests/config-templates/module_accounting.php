<?php

declare(strict_types=1);

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Providers;
use SimpleSAML\Module\accounting\Stores;
use SimpleSAML\Module\accounting\Trackers;

$config = [

    ModuleConfiguration::OPTION_USER_ID_ATTRIBUTE_NAME => 'urn:oasis:names:tc:SAML:attribute:subject-id',

    ModuleConfiguration::OPTION_DEFAULT_AUTHENTICATION_SOURCE => 'default-sp',

    ModuleConfiguration::OPTION_ACCOUNTING_PROCESSING_TYPE =>
        ModuleConfiguration\AccountingProcessingType::VALUE_SYNCHRONOUS,

    ModuleConfiguration::OPTION_JOBS_STORE => Stores\Jobs\DoctrineDbal\Store::class,

    ModuleConfiguration::OPTION_DEFAULT_DATA_TRACKER_AND_PROVIDER =>
        Trackers\Authentication\DoctrineDbal\Versioned\Tracker::class,

    ModuleConfiguration::OPTION_ADDITIONAL_TRACKERS => [
        //
    ],

    ModuleConfiguration::OPTION_CLASS_TO_CONNECTION_MAP => [
        Stores\Jobs\DoctrineDbal\Store::class => 'doctrine_dbal_pdo_sqlite',
        Trackers\Authentication\DoctrineDbal\Versioned\Tracker::class => [
            ModuleConfiguration\ConnectionType::MASTER => 'doctrine_dbal_pdo_sqlite',
            ModuleConfiguration\ConnectionType::SLAVE => [
                'doctrine_dbal_pdo_sqlite_slave',
            ],
        ],
    ],

    ModuleConfiguration::OPTION_CONNECTIONS_AND_PARAMETERS => [
        'doctrine_dbal_pdo_sqlite' => [
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'table_prefix' => '',
        ],
        'doctrine_dbal_pdo_sqlite_slave' => [
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'table_prefix' => '',
        ],
    ],

    ModuleConfiguration::OPTION_JOB_RUNNER_MAXIMUM_EXECUTION_TIME => null,

    ModuleConfiguration::OPTION_JOB_RUNNER_SHOULD_PAUSE_AFTER_NUMBER_OF_JOBS_PROCESSED => 10,

    ModuleConfiguration::OPTION_TRACKER_DATA_RETENTION_POLICY => null,

    ModuleConfiguration::OPTION_CRON_TAG_FOR_TRACKER_DATA_RETENTION_POLICY =>
        'accounting_tracker_data_retention_policy',

    ModuleConfiguration::OPTION_CRON_TAG_FOR_JOB_RUNNER => 'accounting_job_runner',
];
