<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services;

use SimpleSAML\Module\accounting\Helpers\DateTimeHelper;
use SimpleSAML\Module\accounting\Helpers\EnvironmentHelper;
use SimpleSAML\Module\accounting\Helpers\RandomHelper;
use SimpleSAML\Module\accounting\Helpers\ModuleRoutesHelper;

class HelpersManager
{
    protected static ?DateTimeHelper $dateTimeHelper;
    protected static ?EnvironmentHelper $environmentHelper;
    protected static ?RandomHelper $randomHelper;
    protected static ?ModuleRoutesHelper $routesHelper;

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
}
