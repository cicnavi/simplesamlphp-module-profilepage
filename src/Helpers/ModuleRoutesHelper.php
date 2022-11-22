<?php

namespace SimpleSAML\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Utils\HTTP;

class ModuleRoutesHelper
{
    public const PATH_ADMIN_CONFIGURATION_STATUS = 'admin/configuration/status';

    public const PATH_USER_PERSONAL_DATA = 'user/personal-data';

    protected HTTP $sspHttpUtils;

    public function __construct(HTTP $sspHttpUtils = null)
    {
        $this->sspHttpUtils = $sspHttpUtils ?? new HTTP();
    }

    public function getUrl(string $path, array $parameters = []): string
    {
        $url = $this->sspHttpUtils->getBaseURL() . 'module.php/' . ModuleConfiguration::MODULE_NAME . '/' . $path;

        if (!empty($parameters)) {
            $url = $this->sspHttpUtils->addURLParameters($url, $parameters);
        }

        return $url;
    }
}
