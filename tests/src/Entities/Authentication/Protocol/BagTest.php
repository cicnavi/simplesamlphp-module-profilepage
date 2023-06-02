<?php

namespace SimpleSAML\Test\Module\accounting\Entities\Authentication\Protocol;

use SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Bag;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Oidc;
use SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Saml2;
use SimpleSAML\Module\accounting\Entities\Interfaces\AuthenticationProtocolInterface;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Bag
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Saml2
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Oidc
 */
class BagTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $bag = new Bag();

        $this->assertInstanceOf(Bag::class, $bag);
    }

    public function testCanGetById(): void
    {
        $bag = new Bag();

        $this->assertInstanceOf(Saml2::class, $bag->getById(Saml2::ID));
        $this->assertInstanceOf(Oidc::class, $bag->getById(Oidc::ID));
    }

    public function testCagGetAll(): void
    {
        $bag = new Bag();

        foreach ($bag->getAll() as $protocol) {
            $this->assertInstanceOf(AuthenticationProtocolInterface::class, $protocol);
        }
    }
}
