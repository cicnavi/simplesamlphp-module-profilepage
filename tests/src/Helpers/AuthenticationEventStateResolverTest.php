<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Entities\Authentication\Event\State\Oidc;
use SimpleSAML\Module\accounting\Entities\Authentication\Event\State\Saml2;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractState;
use SimpleSAML\Module\accounting\Exceptions\StateException;
use SimpleSAML\Module\accounting\Helpers\AuthenticationEventStateResolver;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\AuthenticationEventStateResolver
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Event\State\Oidc
 * @uses \SimpleSAML\Module\accounting\Services\HelpersManager
 * @uses \SimpleSAML\Module\accounting\Helpers\Network
 */
class AuthenticationEventStateResolverTest extends TestCase
{
    public function testCanResolveState(): void
    {
        $resolver = new AuthenticationEventStateResolver();

        $this->assertInstanceOf(Saml2::class, $resolver->fromStateArray(StateArrays::SAML2_FULL));
        $this->assertInstanceOf(Oidc::class, $resolver->fromStateArray(StateArrays::OIDC_FULL));
    }

    public function testThrowsIfAttributesNotSet(): void
    {
        $resolver = new AuthenticationEventStateResolver();

        $stateArray = StateArrays::SAML2_FULL;
        unset($stateArray[AbstractState::KEY_ATTRIBUTES]);

        $this->expectException(StateException::class);

        $resolver->fromStateArray($stateArray);
    }

    public function testThrowsForInvalidStateArray(): void
    {
        $resolver = new AuthenticationEventStateResolver();

        $stateArray = StateArrays::SAML2_FULL;
        unset($stateArray[Saml2::KEY_IDENTITY_PROVIDER_METADATA]);

        $this->expectException(StateException::class);

        $resolver->fromStateArray($stateArray);
    }
}
