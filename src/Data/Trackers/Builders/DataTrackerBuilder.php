<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Trackers\Builders;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\profilepage\Data\Trackers\Interfaces\DataTrackerInterface;
use SimpleSAML\Module\profilepage\Exceptions\Exception;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\profilepage\ModuleConfiguration;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use Throwable;

class DataTrackerBuilder
{
    public function __construct(
        protected ModuleConfiguration $moduleConfiguration,
        protected LoggerInterface $logger,
        protected HelpersManager $helpersManager
    ) {
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
