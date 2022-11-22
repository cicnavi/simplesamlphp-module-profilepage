<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities\Bases;

use SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\IdentityProvider;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider
 * @uses \SimpleSAML\Module\accounting\Entities\IdentityProvider
 */
class AbstractProviderTest extends TestCase
{
    /**
     * @var array
     */
    protected array $metadata;

    public function setUp(): void
    {
        $this->metadata = [
            AbstractProvider::METADATA_KEY_ENTITY_ID => 'http//example.org/idp',
            AbstractProvider::METADATA_KEY_NAME => [
                'en' => 'Example service',
            ],
            AbstractProvider::METADATA_KEY_DESCRIPTION => [
                'en' => 'Example description'
            ],
        ];
    }

    /**
     * @psalm-suppress MixedArrayAccess
     */
    public function testCanCreateInstance(): void
    {
        $identityProvider = new IdentityProvider($this->metadata);

        $this->assertSame($this->metadata, $identityProvider->getMetadata());
        $this->assertSame(
            $this->metadata[AbstractProvider::METADATA_KEY_ENTITY_ID],
            $identityProvider->getEntityId()
        );
        $this->assertSame(
            $this->metadata[AbstractProvider::METADATA_KEY_NAME]['en'],
            $identityProvider->getName()
        );
        $this->assertSame(
            $this->metadata[AbstractProvider::METADATA_KEY_DESCRIPTION]['en'],
            $identityProvider->getDescription()
        );
    }

    public function testCanResolveNonLocalizedString(): void
    {
        $metadata = $this->metadata;
        $metadata[AbstractProvider::METADATA_KEY_DESCRIPTION] = 'Non localized description.';

        $identityProvider = new IdentityProvider($metadata);

        $this->assertSame($metadata[AbstractProvider::METADATA_KEY_DESCRIPTION], $identityProvider->getDescription());
    }

    public function testInvalidLocalizedDataResolvesToNull(): void
    {
        $metadata = $this->metadata;
        $metadata[AbstractProvider::METADATA_KEY_DESCRIPTION] = false;

        $identityProvider = new IdentityProvider($metadata);

        $this->assertNull($identityProvider->getDescription());
    }

    public function testReturnsNullIfNameNotAvailable(): void
    {
        $metadata = $this->metadata;
        unset($metadata[AbstractProvider::METADATA_KEY_NAME]);

        $identityProvider = new IdentityProvider($metadata);
        $this->assertNull($identityProvider->getName());
    }

    public function testThrowsIfEntityIdNotAvailable(): void
    {
        $metadata = $this->metadata;
        unset($metadata[AbstractProvider::METADATA_KEY_ENTITY_ID]);

        $this->expectException(UnexpectedValueException::class);
        new IdentityProvider($metadata);
    }
}
