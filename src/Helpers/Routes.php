<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Helpers;

use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\Module\profilepage\Exceptions\InvalidConfigurationException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Utils\HTTP;

use function sprintf;

class Routes
{
    public const PATH_ADMIN_CONFIGURATION_STATUS = 'admin/configuration/status';

    public const PATH_USER_PERSONAL_DATA = 'user/personal-data';
    public const PATH_USER_OIDC_TOKENS = 'user/oidc-tokens';
    public const QUERY_REDIRECT_TO_PATH = 'redirectTo';
    public const PATH_LOGGED_OUT = 'logged-out';

    protected HTTP $sspHttpUtils;
    protected Arr $arr;

    public function __construct(HTTP $sspHttpUtils = null, Arr $arr = null)
    {
        $this->sspHttpUtils = $sspHttpUtils ?? new HTTP();
        $this->arr = $arr ?? new Arr();
    }

    public function getUrl(string $path, array $queryParameters = [], array $fragmentParameters = []): string
    {
        try {
            $url = $this->sspHttpUtils->getBaseURL() . 'module.php/' . ModuleConfiguration::MODULE_NAME . '/' . $path;
            // @codeCoverageIgnoreStart
            // SSP dumps some exception context data when simulating exception, so will ignore coverage for this...
        } catch (CriticalConfigurationError $exception) {
            $message = sprintf('Could not load SimpleSAMLphp base URL. Error was: %s', $exception->getMessage());
            throw new InvalidConfigurationException($message, $exception->getCode(), $exception);
            // @codeCoverageIgnoreEnd
        }

        if (!empty($queryParameters)) {
            $url = $this->sspHttpUtils->addURLParameters($url, $queryParameters);
        }

        // Let's assume there are no current fragments in the URL. If the fragment array is not associative,
        // simply append value(s). Otherwise, create key-value fragment pairs.
        if (!empty($fragmentParameters)) {
            /** @psalm-suppress MixedArgumentTypeCoercion */
            $url .= '#' . implode(
                '&',
                (
                    ! $this->arr->isAssociative($fragmentParameters) ?
                    $fragmentParameters :
                    array_map(
                        fn($key, string $value): string => $key . '=' . $value,
                        array_keys($fragmentParameters),
                        $fragmentParameters
                    )
                )
            );
        }

        return $url;
    }
}
