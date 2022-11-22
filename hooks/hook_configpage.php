<?php

declare(strict_types=1);

use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\accounting\Helpers\ModuleRoutesHelper;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\XHTML\Template;

function accounting_hook_configpage(Template &$template): void
{
    $moduleRoutesHelper = new ModuleRoutesHelper();

    $dataLinksKey = 'links';

    if (!isset($template->data[$dataLinksKey]) || !is_array($template->data[$dataLinksKey])) {
        return;
    }

    $template->data[$dataLinksKey][] = [
        'href' => $moduleRoutesHelper->getUrl(ModuleRoutesHelper::PATH_ADMIN_CONFIGURATION_STATUS),
        'text' => Translate::noop('Accounting configuration status'),
    ];

    $template->getLocalization()->addModuleDomain(ModuleConfiguration::MODULE_NAME);
}
