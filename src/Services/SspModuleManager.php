<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\SspModule\Oidc;

class SspModuleManager
{
    protected LoggerInterface $logger;
    protected HelpersManager $helpersManager;

    protected static ?Oidc $oidc;

    public function __construct(
        LoggerInterface $logger = null,
        HelpersManager $helpersManager = null
    ) {
        $this->logger = $logger ?? new Logger();
        $this->helpersManager = $helpersManager ?? new HelpersManager();
    }

    public function getOidc(): Oidc
    {
        return self::$oidc ??= new Oidc(
            $this->logger,
            $this->helpersManager
        );
    }
}
