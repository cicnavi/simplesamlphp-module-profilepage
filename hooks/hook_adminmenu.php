<?php

declare(strict_types=1);

use SimpleSAML\Locale\Translate;
use SimpleSAML\Module;
use SimpleSAML\Module\accounting\ModuleConfiguration;

function accounting_hook_adminmenu(\SimpleSAML\XHTML\Template &$template): void
{
    $moduleName = ModuleConfiguration::MODULE_NAME;
    $menuKey = 'menu';

    // TODO mivanci check is setup is needed. If yes, only put link to setup page.
    // TODO mivanci create and use routes helper

    $profilePageEntry = [
        $moduleName => [
            'url' => Module::getModuleURL("$moduleName/user/personal-data"),
            'name' => Translate::noop('Profile Page'),
        ],
    ];

    // Use array_splice to put our entry before the "Log out" entry.
    array_splice($template->data[$menuKey], -1, 0, $profilePageEntry);
}