<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Helpers;

use SimpleSAML\Module\accounting\Entities\Authentication\Event\State;
use SimpleSAML\Module\accounting\Entities\Interfaces\IdentityProviderInterface;
use SimpleSAML\Module\accounting\Entities\Interfaces\ServiceProviderInterface;
use SimpleSAML\Module\accounting\Entities\Providers\Identity;
use SimpleSAML\Module\accounting\Entities\Providers\Service;
use SimpleSAML\Module\accounting\Exceptions\MetadataException;
use SimpleSAML\Module\accounting\Helpers\ProviderResolver;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Helpers\ProviderResolver
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider
 * @uses \SimpleSAML\Module\accounting\Entities\Providers\Identity\Saml2
 * @uses \SimpleSAML\Module\accounting\Entities\Providers\Identity\Oidc
 * @uses \SimpleSAML\Module\accounting\Entities\Providers\Service\Saml2
 * @uses \SimpleSAML\Module\accounting\Entities\Providers\Service\Oidc
 * @uses \SimpleSAML\Module\accounting\Entities\Providers\Bases\AbstractSaml2Provider
 * @uses \SimpleSAML\Module\accounting\Entities\Providers\Bases\AbstractOidcProvider
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Saml2
 * @uses \SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Oidc
 */
class ProviderResolverTest extends TestCase
{
    /**
     * @throws MetadataException
     */
    public function testCanResolveIdentityProvider(): void
    {
        $samlIdp = (new ProviderResolver())
            ->forIdentityFromMetadataArray(StateArrays::SAML2_FULL[State\Saml2::KEY_IDENTITY_PROVIDER_METADATA]);

        $this->assertInstanceOf(IdentityProviderInterface::class, $samlIdp);
        $this->assertInstanceOf(Identity\Saml2::class, $samlIdp);

        $oidcIdp = (new ProviderResolver())
            ->forIdentityFromMetadataArray(
                StateArrays::OIDC_FULL[State\Oidc::KEY_OIDC][State\Oidc::KEY_OPEN_ID_PROVIDER_METADATA]
            );

        $this->assertInstanceOf(IdentityProviderInterface::class, $oidcIdp);
        $this->assertInstanceOf(Identity\Oidc::class, $oidcIdp);
    }

    public function testThrowsIfCanNotResolveIdentityProvider(): void
    {
        $this->expectException(MetadataException::class);

        (new ProviderResolver())->forIdentityFromMetadataArray(['invalid' => 'metadata']);
    }

    /**
     * @throws MetadataException
     */
    public function testCanResolveServiceProvider(): void
    {
        $samlSp = (new ProviderResolver())
            ->forServiceFromMetadataArray(StateArrays::SAML2_FULL[State\Saml2::KEY_SERVICE_PROVIDER_METADATA]);

        $this->assertInstanceOf(ServiceProviderInterface::class, $samlSp);
        $this->assertInstanceOf(Service\Saml2::class, $samlSp);

        $oidcSp = (new ProviderResolver())
            ->forServiceFromMetadataArray(
                StateArrays::OIDC_FULL[State\Oidc::KEY_OIDC][State\Oidc::KEY_RELYING_PARTY_METADATA]
            );

        $this->assertInstanceOf(ServiceProviderInterface::class, $oidcSp);
        $this->assertInstanceOf(Service\Oidc::class, $oidcSp);
    }

    public function testThrowsIfCanNotResolveServiceProvider(): void
    {
        $this->expectException(MetadataException::class);

        (new ProviderResolver())->forServiceFromMetadataArray(['invalid' => 'metadata']);
    }
}
