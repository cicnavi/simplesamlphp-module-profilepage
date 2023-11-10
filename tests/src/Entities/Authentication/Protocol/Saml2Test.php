<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities\Authentication\Protocol;

use SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Saml2;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Saml2
 */
class Saml2Test extends TestCase
{
    public function testCanGetDesignation(): void
    {
        // This should never change.
        $this->assertSame('SAML2', (new Saml2())->getDesignation());
    }

    public function testCanGetId(): void
    {
        // This should never change.
        $this->assertSame(1, (new Saml2())->getId());
    }
}
