<?php

namespace SimpleSAML\Module\accounting;

use SimpleSAML\Configuration;
use SimpleSAML\Module\accounting\Exceptions\ModuleConfiguration\InvalidConfigurationNameException;

class ModuleConfiguration
{
    protected Configuration $configuration;

    public const FILE_NAME = 'module_accounting.php';

    /**
     * @throws \Exception
     */
    public function __construct(string $fileName = null)
    {
        $fileName = $fileName ?? self::FILE_NAME;

        $this->configuration = Configuration::getConfig($fileName);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getValue(string $name)
    {
        if (! $this->configuration->hasValue($name)) {
            throw new InvalidConfigurationNameException(sprintf('Config name does not exist (%s).', $name));
        }

        return $this->configuration->getValue($name);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }
}
