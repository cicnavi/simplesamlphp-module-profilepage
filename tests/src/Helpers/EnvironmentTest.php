<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Helpers\Environment;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\Environment
 */
class EnvironmentTest extends TestCase
{
    public function testConfirmIsCli(): void
    {
        $this->assertTrue((new Environment())->isCli());
    }
}
