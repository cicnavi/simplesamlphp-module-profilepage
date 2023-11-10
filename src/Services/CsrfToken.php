<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Services;

use Exception;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Session;

class CsrfToken
{
    protected Session $session;

    public const KEY = 'ssp-' . ModuleConfiguration::MODULE_NAME . '-csrf-token';
    protected HelpersManager $helpersManager;

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function validate(string $token): bool
    {
        $isValid = $token === $this->get();

        $this->set();

        return $isValid;
    }
}
