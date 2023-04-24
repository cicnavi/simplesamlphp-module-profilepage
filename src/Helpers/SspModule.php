<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Helpers;

use SimpleSAML\Module;

class SspModule
{
    /**
     * @throws \Exception
     */
    public function isEnabled(string $moduleName): bool
    {
        try {
            return Module::isModuleEnabled($moduleName);
        } catch (\Throwable $exception) {
            $message = sprintf('Could not check if module %s is enabled', $moduleName);
            throw new Module\accounting\Exceptions\InvalidConfigurationException(
                $message,
                (int) $exception->getCode(),
                $exception
            );
        }
    }
}
