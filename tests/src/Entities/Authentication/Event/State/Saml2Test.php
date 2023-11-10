<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities\Authentication\Event\State;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event\State\Saml2;
use SimpleSAML\Module\profilepage\Entities\Bases\AbstractState;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;
use SimpleSAML\Test\Module\profilepage\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\Authentication\Event\State\Saml2
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractState
 * @uses \SimpleSAML\Module\profilepage\Helpers\Network
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 */
class Saml2Test extends TestCase
{
    public function testCanInitializeValidState(): void
    {
        $state = new Saml2(StateArrays::SAML2_FULL);

        $this->assertSame($state->getIdentityProviderEntityId(), StateArrays::SAML2_FULL['Source']['entityid']);
    }

    public function testCanResolveIdpEntityId(): void
    {
        $stateArray = StateArrays::SAML2_FULL;
        $state = new Saml2($stateArray);
        $this->assertSame($state->getIdentityProviderEntityId(), StateArrays::SAML2_FULL['IdPMetadata']['entityid']);

        $this->expectException(UnexpectedValueException::class);
        unset($stateArray['IdPMetadata']['entityid']);
        new Saml2($stateArray);
    }

    public function testCanResolveSpEntityId(): void
    {
        $stateArray = StateArrays::SAML2_FULL;
        $state = new Saml2($stateArray);
        $this->assertSame($state->getServiceProviderEntityId(), StateArrays::SAML2_FULL['SPMetadata']['entityid']);

        $this->expectException(UnexpectedValueException::class);
        unset($stateArray['SPMetadata']['entityid']);
        new Saml2($stateArray);
    }

    public function testCanResolveAttributes(): void
    {
        $state = new Saml2(StateArrays::SAML2_FULL);
        $this->assertSame($state->getAttributes(), StateArrays::SAML2_FULL['Attributes']);
    }

    public function testCanResolveAccountedClientIpAddress(): void
    {
        $stateArray = StateArrays::SAML2_FULL;

        $state = new Saml2($stateArray);
        $this->assertNull($state->getClientIpAddress());

        $sampleIp = '123.123.123.123';
        $stateArray[AbstractState::KEY_ACCOUNTING][AbstractState::ACCOUNTING_KEY_CLIENT_IP_ADDRESS] = $sampleIp;

        $state = new Saml2($stateArray);
        $this->assertSame($sampleIp, $state->getClientIpAddress());
    }

    public function testReturnsNullIfAuthnInstantNotPresent(): void
    {
        $stateArray = StateArrays::SAML2_FULL;

        unset($stateArray['AuthnInstant']);

        $state = new Saml2($stateArray);

        $this->assertNull($state->getAuthenticationInstant());
    }

    public function testThrowsOnMissingSourceEntityId(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $stateArray = StateArrays::SAML2_FULL;

        unset($stateArray['Source'], $stateArray['IdPMetadata']);

        (new Saml2($stateArray));
    }

    public function testUseSpMetadataForEntityIdIfDestinationNotAvailable(): void
    {
        $stateArray = StateArrays::SAML2_FULL;

        unset($stateArray['Destination']);

        $state = new Saml2($stateArray);

        $this->assertSame($state->getServiceProviderEntityId(), StateArrays::SAML2_FULL['SPMetadata']['entityid']);
    }

    public function testThrowsOnMissingDestinationEntityId(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $stateArray = StateArrays::SAML2_FULL;

        unset($stateArray['Destination'], $stateArray['SPMetadata']);

        (new Saml2($stateArray));
    }

    public function testThrowsOnInvalidAuthnInstantValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $stateArray = StateArrays::SAML2_FULL;
        $stateArray['AuthnInstant'] = 'invalid';

        new Saml2($stateArray);
    }

    public function testThrowsOnMissingAttributes(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $stateArray = StateArrays::SAML2_FULL;

        unset($stateArray['Attributes']);

        (new Saml2($stateArray));
    }

    public function testCanGetAttributeValue(): void
    {
        $state = new Saml2(StateArrays::SAML2_FULL);

        $this->assertSame(
            StateArrays::SAML2_FULL['Attributes']['hrEduPersonUniqueID'][0],
            $state->getFirstAttributeValue('hrEduPersonUniqueID')
        );

        $this->assertNull($state->getFirstAttributeValue('non-existent'));
    }

    public function testCanResolveIdpMetadataArray(): void
    {
        // Metadata from 'IdPMetadata'
        $sampleState = StateArrays::SAML2_FULL;
        $state = new Saml2($sampleState);
        $this->assertEquals($sampleState['IdPMetadata'], $state->getIdentityProviderMetadata());

        // Fallback metadata from 'Source'
        unset($sampleState['IdPMetadata']);
        $state = new Saml2($sampleState);
        $this->assertEquals($sampleState['Source'], $state->getIdentityProviderMetadata());

        // Throws on no IdP metadata
        $this->expectException(UnexpectedValueException::class);
        unset($sampleState['Source']);
        new Saml2($sampleState);
    }

    public function testCanResolveSpMetadataArray(): void
    {
        // Metadata from 'IdPMetadata'
        $sampleState = StateArrays::SAML2_FULL;
        $state = new Saml2($sampleState);
        $this->assertEquals($sampleState['SPMetadata'], $state->getServiceProviderMetadata());

        // Fallback metadata from 'Destination'
        unset($sampleState['SPMetadata']);
        $state = new Saml2($sampleState);
        $this->assertEquals($sampleState['Destination'], $state->getServiceProviderMetadata());

        // Throws on no SP metadata
        $this->expectException(UnexpectedValueException::class);
        unset($sampleState['Destination']);
        new Saml2($sampleState);
    }

    public function testCanGetCreatedAt(): void
    {
        $state = new Saml2(StateArrays::SAML2_FULL);
        $this->assertInstanceOf(DateTimeImmutable::class, $state->getCreatedAt());
    }

    public function testCanGetAuthenticationProtocol(): void
    {
        $this->assertInstanceOf(
            \SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Saml2::class,
            (new Saml2(StateArrays::SAML2_FULL))->getAuthenticationProtocol()
        );
    }
}
