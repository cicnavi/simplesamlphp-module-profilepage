<?php

namespace SimpleSAML\Test\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\HashDecoratedState;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store\HashDecoratedState
 * @uses \SimpleSAML\Module\accounting\Helpers\HashHelper
 * @uses \SimpleSAML\Module\accounting\Helpers\ArrayHelper
 */
class HashDecoratedStateTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|State|State&\PHPUnit\Framework\MockObject\Stub
     */
    protected $stateStub;
    protected string $identityProviderEntityId;
    /**
     * @var string[]
     */
    protected array $identityProviderMetadata;
    protected string $serviceProviderEntityId;
    /**
     * @var string[]
     */
    protected array $serviceProviderMetadata;
    /**
     * @var string[]
     */
    protected array $attributes;

    protected function setUp(): void
    {
        $this->stateStub = $this->createStub(State::class);
        $this->identityProviderEntityId = 'idpEntityId';
        $this->stateStub->method('getIdentityProviderEntityId')->willReturn($this->identityProviderEntityId);
        $this->identityProviderMetadata = ['idp' => 'metadata'];
        $this->stateStub->method('getIdentityProviderMetadata')->willReturn($this->identityProviderMetadata);
        $this->serviceProviderEntityId = 'spEntityId';
        $this->stateStub->method('getServiceProviderEntityId')->willReturn($this->serviceProviderEntityId);
        $this->serviceProviderMetadata = ['sp' => 'metadata'];
        $this->stateStub->method('getServiceProviderMetadata')->willReturn($this->serviceProviderMetadata);
        $this->attributes = ['sample' => 'attribute'];
        $this->stateStub->method('getAttributes')->willReturn($this->attributes);
    }

    public function testCanCreateInstance(): void
    {
        /** @psalm-suppress PossiblyInvalidArgument */
        $this->assertInstanceOf(HashDecoratedState::class, new HashDecoratedState($this->stateStub));
    }

    public function testCanGetHashedProperties(): void
    {
        /** @psalm-suppress PossiblyInvalidArgument */
        $hashDecoratedState = new HashDecoratedState($this->stateStub);

        $this->assertSame($this->stateStub, $hashDecoratedState->getState());
        $this->assertIsString($hashDecoratedState->getIdentityProviderEntityIdHashSha256());
        $this->assertIsString($hashDecoratedState->getServiceProviderEntityIdHashSha256());
        $this->assertIsString($hashDecoratedState->getIdentityProviderMetadataArrayHashSha256());
        $this->assertIsString($hashDecoratedState->getServiceProviderMetadataArrayHashSha256());
        $this->assertIsString($hashDecoratedState->getAttributesArrayHashSha256());
    }
}
