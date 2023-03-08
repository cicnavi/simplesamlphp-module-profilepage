<?php

declare(strict_types=1);

use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\accounting\Helpers\ModuleRoutes;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\XHTML\Template;

/** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection Reference is actually used by SimpleSAMLphp */
function accounting_hook_adminmenu(Template &$template): void
{
    $menuKey = 'menu';

    $moduleRoutesHelper = new ModuleRoutes();

    $profilePageEntry = [
        ModuleConfiguration::MODULE_NAME => [
            'url' => $moduleRoutesHelper->getUrl(ModuleRoutes::PATH_USER_PERSONAL_DATA),
            'name' => Translate::noop('Profile Page'),
        ],
    ];

    if (!isset($template->data[$menuKey]) || !is_array($template->data[$menuKey])) {
        return;
    }

    // Use array_splice to put our entry before the "Log out" entry.
    array_splice($template->data[$menuKey], -1, 0, $profilePageEntry);

    $template->getLocalization()->addModuleDomain(ModuleConfiguration::MODULE_NAME);
}