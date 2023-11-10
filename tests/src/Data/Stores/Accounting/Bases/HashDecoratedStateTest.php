<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Data\Stores\Accounting\Bases;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\HashDecoratedState;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event\State;

/**
 * @covers \SimpleSAML\Module\profilepage\Data\Stores\Accounting\Bases\HashDecoratedState
 * @uses \SimpleSAML\Module\profilepage\Helpers\Hash
 * @uses \SimpleSAML\Module\profilepage\Helpers\Arr
 * @uses \SimpleSAML\Module\profilepage\Services\HelpersManager
 */
class HashDecoratedStateTest extends TestCase
{
    /**
     * @var Stub
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
        $this->stateStub = $this->createStub(State\Saml2::class);
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
        $this->assertInstanceOf(HashDecoratedState::class, new HashDecoratedState($this->stateStub));
    }

    public function testCanGetHashedProperties(): void
    {
        $hashDecoratedState = new HashDecoratedState($this->stateStub);

        $this->assertSame($this->stateStub, $hashDecoratedState->getState());
        $this->assertIsString($hashDecoratedState->getIdentityProviderEntityIdHashSha256());
        $this->assertIsString($hashDecoratedState->getServiceProviderEntityIdHashSha256());
        $this->assertIsString($hashDecoratedState->getIdentityProviderMetadataArrayHashSha256());
        $this->assertIsString($hashDecoratedState->getServiceProviderMetadataArrayHashSha256());
        $this->assertIsString($hashDecoratedState->getAttributesArrayHashSha256());
    }
}
