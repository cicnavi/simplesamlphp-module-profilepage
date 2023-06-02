<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services;

use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Session;

class CsrfToken
{
    protected Session $session;

    public const KEY = 'ssp-' . ModuleConfiguration::MODULE_NAME . '-csrf-token';
    protected HelpersManager $helpersManager;

    public function __construct(
        Session $session = null,
        HelpersManager $helpersManager = null
    ) {
        $this->session = $session ?? Session::getSessionFromRequest();
        $this->helpersManager = $helpersManager ?? new HelpersManager();

        if ($this->get() === null) {
            $this->set();
        }
    }

    protected function set(?string $token = null): void
    {
        $this->session->setData(
            ModuleConfiguration::MODULE_NAME,
            self::KEY,
            $token ?? $this->helpersManager->getRandom()->getString()
        );
    }

    public function get(): ?string
    {
        $token = $this->session->getData(ModuleConfiguration::MODULE_NAME, self::KEY);

        if ($token !== null) {
            return (string) $token;
        }

        return null;
    }

    public function validate(string $token): bool
    {
        $isValid = $token === $this->get();

        $this->set();

        return $isValid;
    }
}
