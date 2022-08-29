<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Trackers\Authentication\DoctrineDbal\Versioned;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Providers\Interfaces\AuthenticationDataProviderInterface;
use SimpleSAML\Module\accounting\Trackers\Interfaces\AuthenticationDataTrackerInterface;

class Tracker implements AuthenticationDataTrackerInterface, AuthenticationDataProviderInterface
{
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger
    ): self {
        return new self();
    }

    public function process(Event $authenticationEvent): void
    {
        // TODO: Implement process() method.
    }
}
