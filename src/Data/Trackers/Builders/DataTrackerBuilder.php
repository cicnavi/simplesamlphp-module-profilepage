<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Trackers\Builders;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Data\Trackers\Interfaces\DataTrackerInterface;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use Throwable;

class DataTrackerBuilder
{
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;
    protected HelpersManager $helpersManager;

    public function __construct(
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger,
        HelpersManager $helpersManager
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;
        $this->helpersManager = $helpersManager;
    }

    /**
     * @throws Exception
     */
    public function build(string $class): DataTrackerInterface
    {
        try {
            // Make sure that the class implements proper interface
            if (!is_subclass_of($class, DataTrackerInterface::class)) {
                $message = sprintf(
                    'Class %s does not implement interface %s.',
                    $class,
                    DataTrackerInterface::class
                );
                throw new UnexpectedValueException($message);
            }

            // Build...
            /** @var DataTrackerInterface $tracker */
            $tracker = $this->helpersManager->getInstanceBuilderUsingModuleConfiguration()->build(
                $class,
                $this->moduleConfiguration,
                $this->logger
            );
        } catch (Throwable $exception) {
            $message = sprintf('Error building instance for class %s. Error was: %s', $class, $exception->getMessage());
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }

        return $tracker;
    }
}
