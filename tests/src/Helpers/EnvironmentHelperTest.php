<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Helpers\EnvironmentHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\EnvironmentHelper
 */
class EnvironmentHelperTest extends TestCase
{
   public function testConfirmIsCli(): void
   {
        $this->assertTrue((new EnvironmentHelper())->isCli());
   }
}
