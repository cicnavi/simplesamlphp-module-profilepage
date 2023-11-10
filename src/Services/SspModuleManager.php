<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Services;

use Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\SspModule\Oidc;

class SspModuleManager
{
    protected LoggerInterface $logger;
    protected HelpersManager $helpersManager;

    protected static ?Oidc $oidc = null;

    public function __construct(
        LoggerInterface $logger = null,
        HelpersManager $helpersManager = null
    ) {
        $this->logger = $logger ?? new Logger();
        $this->helpersManager = $helpersManager ?? new HelpersManager();
    }

    /**
     * @throws Exception
     */
    public function getOidc(): Oidc
    {
        return self::$oidc ??= new Oidc(
            $this->logger,
            $this->helpersManager
        );
    }
}
