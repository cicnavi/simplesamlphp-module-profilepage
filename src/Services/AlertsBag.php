<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services;

use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\AlertsBag\Alert;
use SimpleSAML\Session;

class AlertsBag
{
    protected Session $sspSession;

    public const SESSION_KEY = 'alerts';

    public function __construct(Session $session)
    {
        $this->sspSession = $session;
    }

    public function isNotEmpty(): bool
    {
        return ! empty($this->getAll(false));
    }

    /**
     * @throws Exception
     */
    public function getAll(bool $reinitialize = true): array
    {
        $alerts = $this->sspSession->getData(ModuleConfiguration::MODULE_NAME, self::SESSION_KEY) ?? [];

        if (! is_array($alerts)) {
            throw new Exception('Unexpected value type.');
        }

        if ($reinitialize) {
            $this->sspSession->setData(ModuleConfiguration::MODULE_NAME, self::SESSION_KEY, null);
        }

        return $alerts;
    }

    public function put(Alert $alert): void
    {
        $this->sspSession->setData(
            ModuleConfiguration::MODULE_NAME,
            self::SESSION_KEY,
            array_merge($this->getAll(false), [$alert])
        );
    }
}
