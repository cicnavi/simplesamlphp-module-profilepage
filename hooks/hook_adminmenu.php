<?php

declare(strict_types=1);

use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\profilepage\Helpers\Routes;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\XHTML\Template;

/** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection Reference is actually used by SimpleSAMLphp */
function profilepage_hook_adminmenu(Template &$template): void
{
    $menuKey = 'menu';

    $moduleRoutesHelper = new Routes();

    $profilePageEntry = [
        ModuleConfiguration::MODULE_NAME => [
            'url' => $moduleRoutesHelper->getUrl(Routes::PATH_USER_PERSONAL_DATA),
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