<?php

declare(strict_types=1);

use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\profilepage\Helpers\Routes;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\XHTML\Template;

/** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection Reference is used by SimpleSAMLphp */
function profilepage_hook_configpage(Template &$template): void
{
    $moduleRoutesHelper = new Routes();

    $dataLinksKey = 'links';

    if (!isset($template->data[$dataLinksKey]) || !is_array($template->data[$dataLinksKey])) {
        return;
    }

    $template->data[$dataLinksKey][] = [
        'href' => $moduleRoutesHelper->getUrl(Routes::PATH_ADMIN_CONFIGURATION_STATUS),
        'text' => Translate::noop('Profile Page configuration status'),
    ];

    $template->getLocalization()->addModuleDomain(ModuleConfiguration::MODULE_NAME);
}
