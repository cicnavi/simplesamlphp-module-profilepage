<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services;

use SimpleSAML\Module\accounting\Helpers\ArrayHelper;
use SimpleSAML\Module\accounting\Helpers\AttributesHelper;
use SimpleSAML\Module\accounting\Helpers\DateTimeHelper;
use SimpleSAML\Module\accounting\Helpers\EnvironmentHelper;
use SimpleSAML\Module\accounting\Helpers\FilesystemHelper;
use SimpleSAML\Module\accounting\Helpers\HashHelper;
use SimpleSAML\Module\accounting\Helpers\InstanceBuilderUsingModuleConfigurationHelper;
use SimpleSAML\Module\accounting\Helpers\NetworkHelper;
use SimpleSAML\Module\accounting\Helpers\RandomHelper;
use SimpleSAML\Module\accounting\Helpers\ModuleRoutesHelper;

class HelpersManager
{
    protected static ?DateTimeHelper $dateTimeHelper;
    protected static ?EnvironmentHelper $environmentHelper;
    protected static ?RandomHelper $randomHelper;
    protected static ?ModuleRoutesHelper $routesHelper;
    protected static ?ArrayHelper $arrayHelper;
    protected static ?HashHelper $hashHelper;
    protected static ?AttributesHelper $attributesHelper;
    protected static ?FilesystemHelper $filesystemHelper;
    protected static ?InstanceBuilderUsingModuleConfigurationHelper $instanceBuilderHelper;
    protected static ?NetworkHelper $networkHelper;

    public function getDateTimeHelper(): DateTimeHelper
    {
        return self::$dateTimeHelper ??= new DateTimeHelper();
    }

    public function getEnvironmentHelper(): EnvironmentHelper
    {
        return self::$environmentHelper ??= new EnvironmentHelper();
    }

    public function getRandomHelper(): RandomHelper
    {
        return self::$randomHelper ??= new RandomHelper();
    }

    public function getModuleRoutesHelper(): ModuleRoutesHelper
    {
        return self::$routesHelper ??= new ModuleRoutesHelper();
    }

    public function getArrayHelper(): ArrayHelper
    {
        return self::$arrayHelper ??= new ArrayHelper();
    }

    public function getHashHelper(): HashHelper
    {
        return self::$hashHelper ??= new HashHelper($this->getArrayHelper());
    }

    public function getAttributesHelper(): AttributesHelper
    {
        return self::$attributesHelper ??= new AttributesHelper();
    }

    public function getFilesystemHelper(): FilesystemHelper
    {
        return self::$filesystemHelper ??= new FilesystemHelper();
    }

    public function getInstanceBuilderUsingModuleConfigurationHelper(): InstanceBuilderUsingModuleConfigurationHelper
    {
        return self::$instanceBuilderHelper ??= new InstanceBuilderUsingModuleConfigurationHelper();
    }

    public function getNetworkHelper(): NetworkHelper
    {
        return self::$networkHelper ??= new NetworkHelper();
    }
}
