<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Helpers;

use SimpleSAML\Module\profilepage\Helpers\Environment;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\profilepage\Helpers\Environment
 */
class EnvironmentTest extends TestCase
{
    public function testConfirmIsCli(): void
    {
        $this->assertTrue((new Environment())->isCli());
    }
}
