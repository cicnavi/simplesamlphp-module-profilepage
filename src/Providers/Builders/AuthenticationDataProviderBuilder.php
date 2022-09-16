<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Providers\Builders;

use Psr\Log\LoggerInterface;
use SimpleSAML\Module\accounting\Exceptions\Exception;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfigurationHelper;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Providers\Interfaces\AuthenticationDataProviderInterface;
use Throwable;

class AuthenticationDataProviderBuilder
{
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;

    public function __construct(ModuleConfiguration $moduleConfiguration, LoggerInterface $logger)
    {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    public function build(string $class): AuthenticationDataProviderInterface
    {
        try {
            // Make sure that the class implements proper interface
            if (!is_subclass_of($class, AuthenticationDataProviderInterface::class)) {
                $message = sprintf(
                    'Class %s does not implement interface %s.',
                    $class,
                    AuthenticationDataProviderInterface::class
                );
                throw new UnexpectedValueException($message);
            }

            // Build...
            /** @var AuthenticationDataProviderInterface $store */
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