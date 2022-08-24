<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Exceptions\StoreException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\Logger;
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
        Factory $factory,
        LoggerInterface $logger
    ) {
        parent::__construct($moduleConfiguration, $factory, $logger);
    }

    /**
     * @throws StoreException
     */
    public static function build(ModuleConfiguration $moduleConfiguration): self
    {
        return new self(
            $moduleConfiguration,
            new Factory($moduleConfiguration, new Logger()),
            new Logger()
        );
    }
}
