<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\Module\accounting\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Utils\HTTP;

class ModuleRoutes
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
        try {
            $url = $this->sspHttpUtils->getBaseURL() . 'module.php/' . ModuleConfiguration::MODULE_NAME . '/' . $path;
            // @codeCoverageIgnoreStart
            // SSP dumps some exception context data when simulating exception, so will ignore coverage for this...
        } catch (CriticalConfigurationError $exception) {
            $message = \sprintf('Could not load SimpleSAMLphp base URL. Error was: %s', $exception->getMessage());
            throw new InvalidConfigurationException($message, $exception->getCode(), $exception);
            // @codeCoverageIgnoreEnd
        }

        if (!empty($parameters)) {
            $url = $this->sspHttpUtils->addURLParameters($url, $parameters);
        }

        return $url;
    }
}
