<?php

declare(strict_types=1);

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores;

$config = [
    ModuleConfiguration::OPTION_USER_ID_ATTRIBUTE_NAME => 'urn:oasis:names:tc:SAML:attribute:subject-id',

    ModuleConfiguration::OPTION_ACCOUNTING_PROCESSING_TYPE => 'invalid',

    ModuleConfiguration::OPTION_JOBS_STORE => 'invalid',

    ModuleConfiguration::OPTION_JOBS_STORE_CONNECTION => 'invalid',

    ModuleConfiguration::OPTION_DEFAULT_DATA_TRACKER_AND_PROVIDER => 'invalid',

    ModuleConfiguration::OPTION_DEFAULT_DATA_TRACKER_AND_PROVIDER_CONNECTION => [
        ModuleConfiguration\ConnectionType::MASTER => 'invalid',
        ModuleConfiguration\ConnectionType::SLAVE => [
            'invalid',
        ],
    ],

    ModuleConfiguration::OPTION_ADDITIONAL_TRACKERS => [
        'invalid',
    ],

    ModuleConfiguration::OPTION_ADDITIONAL_TRACKERS_TO_CONNECTION_MAP => [
        Stores\Jobs\DoctrineDbal\Store::class => 'invalid',
    ],

    ModuleConfiguration::OPTION_CONNECTIONS_AND_PARAMETERS => [
        'doctrine_dbal_pdo_mysql' => [
            'driver' => 'pdo_mysql',
        ],
        'doctrine_dbal_pdo_sqlite' => [
            'driver' => 'pdo_sqlite',
        ],
    ],
];
