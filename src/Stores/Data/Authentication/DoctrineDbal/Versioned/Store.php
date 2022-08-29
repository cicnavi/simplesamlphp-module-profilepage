<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractStore;
use SimpleSAML\Module\accounting\Stores\Connections\DoctrineDbal\Factory;
use SimpleSAML\Module\accounting\Stores\Interfaces\DataStoreInterface;

class Store extends AbstractStore implements DataStoreInterface
{
    /**
     * @throws StoreException
     */
    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        Factory $connectionFactory,
        string $class = null
    ) {
        parent::__construct($moduleConfiguration, $logger, $connectionFactory, $class);
    }

    /**
     * Build store instance.
     * @throws StoreException
     */
    public static function build(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        string $class = null
    ): self {
        return new self(
            $moduleConfiguration,
            $logger,
            new Factory($moduleConfiguration, $logger),
            $class
        );
    }
}
