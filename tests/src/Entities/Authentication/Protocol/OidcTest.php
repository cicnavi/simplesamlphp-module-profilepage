<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities\Authentication\Protocol;

use SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Oidc;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Oidc
 */
class OidcTest extends TestCase
{
    public function testCanGetDesignation(): void
    {
        // This should never change.
        $this->assertSame('OIDC', (new Oidc())->getDesignation());
    }

    public function testCanGetId(): void
    {
        $this->assertSame(2, (new Oidc())->getId());
    }
}
