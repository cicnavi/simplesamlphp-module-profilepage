<?php

namespace SimpleSAML\Module\accounting\Trackers\Builders;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfigurationHelper;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Trackers\Interfaces\AuthenticationDataTrackerInterface;
use Throwable;

class AuthenticationDataTrackerBuilder
{
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;

    // TODO mivanci move to abstract parent for all builders
    public function __construct(ModuleConfiguration $moduleConfiguration, LoggerInterface $logger)
    {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    public function build(string $class): AuthenticationDataTrackerInterface
    {
        try {
            // Make sure that the class implements proper interface
            if (!is_subclass_of($class, AuthenticationDataTrackerInterface::class)) {
                $message = sprintf(
                    'Class %s does not implement interface %s.',
                    $class,
                    AuthenticationDataTrackerInterface::class
                );
                throw new UnexpectedValueException($message);
            }

            // Build...
            /** @var AuthenticationDataTrackerInterface $store */
            $store = InstanceBuilderUsingModuleConfigurationHelper::build(
                $class,
                $this->moduleConfiguration,
                $this->logger
            );
        } catch (Throwable $exception) {
            $message = sprintf('Error building instance for class %s. Error was: %s', $class, $exception->getMessage());
            throw new Exception($message, (int)$exception->getCode(), $exception);
        }

        return $store;
    }
}
