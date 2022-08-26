<?php

declare(strict_types=1);

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Providers;
use SimpleSAML\Module\accounting\Stores;
use SimpleSAML\Module\accounting\Trackers;

$config = [

    ModuleConfiguration::OPTION_USER_ID_ATTRIBUTE_NAME => 'urn:oasis:names:tc:SAML:attribute:subject-id',

    ModuleConfiguration::OPTION_ACCOUNTING_PROCESSING_TYPE =>
        ModuleConfiguration\AccountingProcessingType::VALUE_SYNCHRONOUS,


    ModuleConfiguration::OPTION_JOBS_STORE_CLASS => Stores\Jobs\DoctrineDbal\Store::class,

    ModuleConfiguration::OPTION_ENABLED_TRACKERS => [
        Trackers\DoctrineDbal\Versioned\Authentication::class,
    ],

    ModuleConfiguration::OPTION_AUTHENTICATION_DATA_PROVIDER_CLASS =>
        Trackers\DoctrineDbal\Versioned\Authentication::class,

    ModuleConfiguration::OPTION_CLASS_TO_CONNECTION_MAP => [
        Stores\Jobs\DoctrineDbal\Store::class => 'doctrine_dbal_pdo_sqlite',
        Trackers\DoctrineDbal\Versioned\Authentication::class => 'doctrine_dbal_pdo_sqlite',
    ],

    ModuleConfiguration::OPTION_CONNECTIONS_AND_PARAMETERS => [
        'doctrine_dbal_pdo_sqlite' => [
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'table_prefix' => '',
        ],
    ],
];
