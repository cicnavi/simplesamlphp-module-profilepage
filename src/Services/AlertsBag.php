<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Services;

use SimpleSAML\Module\profilepage\Exceptions\Exception;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\AlertsBag\Alert;
use SimpleSAML\Session;

class AlertsBag
{
    final public const SESSION_KEY = 'alerts';

    public function __construct(protected Session $sspSession)
    {
    }

    /**
     * @throws Exception
     */
    public function isNotEmpty(): bool
    {
        return ! empty($this->getAll(false));
    }

    /**
     * @throws Exception
     * @throws \Exception
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

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function put(Alert $alert): void
    {
        $this->sspSession->setData(
            ModuleConfiguration::MODULE_NAME,
            self::SESSION_KEY,
            array_merge($this->getAll(false), [$alert])
        );
    }
}
