<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services;

use SimpleSAML\Module\accounting\Helpers\DateTimeHelper;
use SimpleSAML\Module\accounting\Helpers\EnvironmentHelper;
use SimpleSAML\Module\accounting\Helpers\RandomHelper;

class HelpersManager
{
    protected static ?DateTimeHelper $dateTimeHelper;
    protected static ?EnvironmentHelper $environmentHelper;
    protected static ?RandomHelper $randomHelper;

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
}
