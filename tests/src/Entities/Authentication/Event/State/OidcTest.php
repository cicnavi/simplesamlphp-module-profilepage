<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities\Authentication\Event\State;

use PHPUnit\Framework\MockObject\Stub;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event\State;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Entities\Authentication\Protocol\Oidc;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\profilepage\Helpers\Network;
use SimpleSAML\Module\profilepage\Services\HelpersManager;
use SimpleSAML\Test\Module\profilepage\Constants\StateArrays;
use SimpleSAML\Module\profilepage\Entities\Providers\Identity;
use SimpleSAML\Module\profilepage\Entities\Providers\Service;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\Authentication\Event\State\Oidc
 * @uses \SimpleSAML\Module\profilepage\Entities\Bases\AbstractState
 */
class OidcTest extends TestCase
{
    protected const IP_ADDRESS = '123.123.123.123';
    /**
     * @var Stub
     */
    protected $networkHelperStub;
    /**
     * @var Stub
     */
    protected $helpersManagerStub;

    public function setUp(): void
    {
        $this->networkHelperStub = $this->createStub(Network::class);
        $this->networkHelperStub->method('resolveClientIpAddress')->willReturn(self::IP_ADDRESS);
        $this->helpersManagerStub = $this->createStub(HelpersManager::class);
        $this->helpersManagerStub->method('getNetwork')->willReturn($this->networkHelperStub);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            State\Oidc::class,
            new State\Oidc(StateArrays::OIDC_FULL, null, $this->helpersManagerStub)
        );
    }

    public function testCanResolveIdentityProviderMetadata(): void
    {
        $state = new State\Oidc(StateArrays::OIDC_FULL, null, $this->helpersManagerStub);

        $this->assertSame(
            StateArrays::OIDC_FULL[State\Oidc::KEY_OIDC][State\Oidc::KEY_OPEN_ID_PROVIDER_METADATA],
            $state->getIdentityProviderMetadata()
        );

        $this->assertSame(
            StateArrays::OIDC_FULL[State\Oidc::KEY_OIDC][State\Oidc::KEY_OPEN_ID_PROVIDER_METADATA]
            [Identity\Oidc::METADATA_KEY_ENTITY_ID],
            $state->getIdentityProviderEntityId()
        );
    }

    public function testCanResolveServiceProviderMetadata(): void
    {
        $state = new State\Oidc(StateArrays::OIDC_FULL, null, $this->helpersManagerStub);

        $this->assertSame(
            StateArrays::OIDC_FULL[State\Oidc::KEY_OIDC][State\Oidc::KEY_OPEN_ID_PROVIDER_METADATA],
            $state->getIdentityProviderMetadata()
        );

        $this->assertSame(
            StateArrays::OIDC_FULL[State\Oidc::KEY_OIDC][State\Oidc::KEY_OPEN_ID_PROVIDER_METADATA]
            [Identity\Oidc::METADATA_KEY_ENTITY_ID],
            $state->getIdentityProviderEntityId()
        );
    }

    public function testThrowsIfOidcMetadataNotAvailable(): void
    {
        $stateArray = StateArrays::OIDC_FULL;
        unset($stateArray[State\Oidc::KEY_OIDC]);

        $this->expectException(UnexpectedValueException::class);

        (new State\Oidc($stateArray, null, $this->helpersManagerStub));
    }

    public function testThrowsIfIdentityProviderMetadataNotAvailable(): void
    {
        $stateArray = StateArrays::OIDC_FULL;
        unset($stateArray[State\Oidc::KEY_OIDC][State\Oidc::KEY_OPEN_ID_PROVIDER_METADATA]);

        $this->expectException(UnexpectedValueException::class);

        (new State\Oidc($stateArray, null, $this->helpersManagerStub));
    }

    public function testThrowsIfIdentityProviderEntityIdNotAvailable(): void
    {
        $stateArray = StateArrays::OIDC_FULL;
        unset(
            $stateArray[State\Oidc::KEY_OIDC][State\Oidc::KEY_OPEN_ID_PROVIDER_METADATA]
                [Identity\Oidc::METADATA_KEY_ENTITY_ID]
        );

        $this->expectException(UnexpectedValueException::class);

        (new State\Oidc($stateArray, null, $this->helpersManagerStub));
    }

    public function testThrowsIfServiceProviderMetadataNotAvailable(): void
    {
        $stateArray = StateArrays::OIDC_FULL;
        unset($stateArray[State\Oidc::KEY_OIDC][State\Oidc::KEY_RELYING_PARTY_METADATA]);

        $this->expectException(UnexpectedValueException::class);

        (new State\Oidc($stateArray, null, $this->helpersManagerStub));
    }

    public function testThrowsIfServiceProviderEntityIdNotAvailable(): void
    {
        $stateArray = StateArrays::OIDC_FULL;
        unset(
            $stateArray[State\Oidc::KEY_OIDC][State\Oidc::KEY_RELYING_PARTY_METADATA]
            [Service\Oidc::METADATA_KEY_ENTITY_ID]
        );

        $this->expectException(UnexpectedValueException::class);

        (new State\Oidc($stateArray, null, $this->helpersManagerStub));
    }

    public function testCanGetAuthenticationProtocol(): void
    {
        $this->assertInstanceOf(
            Oidc::class,
            (new State\Oidc(StateArrays::OIDC_FULL, null, $this->helpersManagerStub))
                ->getAuthenticationProtocol()
        );
    }
}
