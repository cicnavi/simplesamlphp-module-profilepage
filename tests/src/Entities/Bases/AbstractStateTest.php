<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities\Bases;

use SimpleSAML\Module\accounting\Entities\Bases\AbstractState;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Interfaces\AuthenticationProtocolInterface;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Helpers\Network;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Bases\AbstractState
 */
class AbstractStateTest extends TestCase
{
    protected const IP_ADDRESS = '123.123.123.123';
    /**
     * @var \PHPUnit\Framework\MockObject\Stub
     */
    protected $networkHelperStub;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub
     */
    protected $helpersManagerStub;

    public function setUp(): void
    {
        $this->networkHelperStub = $this->createStub(Network::class);
        $this->networkHelperStub->method('resolveClientIpAddress')->willReturn(self::IP_ADDRESS);
        $this->helpersManagerStub = $this->createStub(HelpersManager::class);
        $this->helpersManagerStub->method('getNetwork')->willReturn($this->networkHelperStub);
    }

    public function testCanGetAttributes(): void
    {
        $this->assertSame(
            StateArrays::SAML2_FULL[AbstractState::KEY_ATTRIBUTES],
            $this->getSampleInstance(StateArrays::SAML2_FULL, null, $this->helpersManagerStub)->getAttributes()
        );
    }

    public function testCanGetAttributeValue(): void
    {
        $attributeName = 'urn:oid:2.5.4.4';

        $this->assertSame(
            StateArrays::SAML2_FULL[AbstractState::KEY_ATTRIBUTES][$attributeName],
            $this->getSampleInstance(StateArrays::SAML2_FULL, null, $this->helpersManagerStub)
                ->getAttributeValue($attributeName)
        );
    }

    public function testReturnsNullForNonExistentAttribute(): void
    {
        $this->assertNull(
            $this->getSampleInstance(StateArrays::SAML2_FULL, null, $this->helpersManagerStub)
                ->getAttributeValue('invalid')
        );

        $this->assertNull(
            $this->getSampleInstance(StateArrays::SAML2_FULL, null, $this->helpersManagerStub)
                ->getFirstAttributeValue('invalid')
        );
    }

    public function testCanGetFirstAttributeValue(): void
    {
        $attributeName = 'urn:oid:2.5.4.4';

        $this->assertSame(
            StateArrays::SAML2_FULL[AbstractState::KEY_ATTRIBUTES][$attributeName][0],
            $this->getSampleInstance(StateArrays::SAML2_FULL, null, $this->helpersManagerStub)
                ->getFirstAttributeValue($attributeName)
        );
    }

    public function testThrowsIfNoAttributes(): void
    {
        $stateArray = StateArrays::SAML2_FULL;
        unset($stateArray[AbstractState::KEY_ATTRIBUTES]);

        $this->expectException(UnexpectedValueException::class);

        $this->getSampleInstance($stateArray, null, $this->helpersManagerStub);
    }

    public function testCanGetClientIpAddressFromNetwork(): void
    {
        $this->assertSame(
            self::IP_ADDRESS,
            $this->getSampleInstance(StateArrays::SAML2_FULL, null, $this->helpersManagerStub)->getClientIpAddress()
        );
    }

    public function testCanGetClientIpAddressFromState(): void
    {
        $networkHelperMock = $this->createMock(Network::class);
        $networkHelperMock->method('resolveClientIpAddress')
            ->with($this->isType('string'))
            ->will($this->returnArgument(0));

        $helpersManagerStub = $this->createStub(HelpersManager::class);
        $helpersManagerStub->method('getNetwork')->willReturn($networkHelperMock);

        $stateIp = '111.111.111.111';
        $stateArray = StateArrays::SAML2_FULL;
        $stateArray[AbstractState::KEY_ACCOUNTING][AbstractState::ACCOUNTING_KEY_CLIENT_IP_ADDRESS] = $stateIp;
        $this->assertSame(
            $stateIp,
            $this->getSampleInstance($stateArray, null, $helpersManagerStub)->getClientIpAddress()
        );
    }

    public function testCanGetAuthenticationInstant(): void
    {
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $this->getSampleInstance(StateArrays::SAML2_FULL, null, $this->helpersManagerStub)
                ->getAuthenticationInstant()
        );
    }

    public function testAuthenticationInstantIsNullIfNotInState(): void
    {
        $stateArray = StateArrays::SAML2_FULL;
        unset($stateArray[AbstractState::KEY_AUTHENTICATION_INSTANT]);

        $this->assertNull(
            $this->getSampleInstance($stateArray, null, $this->helpersManagerStub)->getAuthenticationInstant()
        );
    }

    public function testThrowsForInvalidAuthenticationInstant(): void
    {
        $stateArray = StateArrays::SAML2_FULL;
        $stateArray[AbstractState::KEY_AUTHENTICATION_INSTANT] = 'invalid';

        $this->expectException(UnexpectedValueException::class);

        $this->getSampleInstance($stateArray, null, $this->helpersManagerStub)->getAuthenticationInstant();
    }

    public function testCanGetResolvedProperties(): void
    {
        $instance = $this->getSampleInstance(StateArrays::SAML2_FULL, null, $this->helpersManagerStub);
        $this->assertIsString($instance->getIdentityProviderEntityId());
        $this->assertIsString($instance->getServiceProviderEntityId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $instance->getCreatedAt());
        $this->assertIsArray($instance->getIdentityProviderMetadata());
        $this->assertIsArray($instance->getServiceProviderMetadata());
    }

    protected function getSampleInstance(
        array $state,
        \DateTimeImmutable $createdAt = null,
        HelpersManager $helpersManager = null
    ): AbstractState {
        return new class ($state, $createdAt, $helpersManager) extends AbstractState {
            protected function resolveIdentityProviderMetadata(array $state): array
            {
                return []; // Abstract, will not test here.
            }

            protected function resolveIdentityProviderEntityId(): string
            {
                return ''; // Abstract, will not test here.
            }

            protected function resolveServiceProviderMetadata(array $state): array
            {
                return []; // Abstract, will not test here.
            }

            protected function resolveServiceProviderEntityId(): string
            {
                return ''; // Abstract, will not test here.
            }

            public function getAuthenticationProtocol(): AuthenticationProtocolInterface
            {
                // Abstract, will not test here.
                return new class () implements AuthenticationProtocolInterface {
                    public function getDesignation(): string
                    {
                        return '';
                    }

                    public function getId(): int
                    {
                        return 0;
                    }
                };
            }
        };
    }
}
