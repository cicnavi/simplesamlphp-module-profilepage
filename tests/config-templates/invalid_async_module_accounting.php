<?php

declare(strict_types=1);

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores;
use SimpleSAML\Module\accounting\Trackers;

$config = [
    ModuleConfiguration::OPTION_USER_ID_ATTRIBUTE_NAME => 'urn:oasis:names:tc:SAML:attribute:subject-id',

    ModuleConfiguration::OPTION_ACCOUNTING_PROCESSING_TYPE =>
        ModuleConfiguration\AccountingProcessingType::VALUE_ASYNCHRONOUS,

    ModuleConfiguration::OPTION_JOBS_STORE => 'invalid',

    ModuleConfiguration::OPTION_DEFAULT_DATA_TRACKER_AND_PROVIDER => 'invalid',

    ModuleConfiguration::OPTION_ADDITIONAL_TRACKERS => [
        'invalid',
    ],

    ModuleConfiguration::OPTION_CLASS_TO_CONNECTION_MAP => [
        'invalid'
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
