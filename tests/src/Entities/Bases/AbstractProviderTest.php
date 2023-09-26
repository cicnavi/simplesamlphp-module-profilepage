<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities\Bases;

use SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Entities\Interfaces\AuthenticationProtocolInterface;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider
 */
class AbstractProviderTest extends TestCase
{
    protected array $metadata;
    protected AbstractProvider $sampleProvider;

    public function setUp(): void
    {
        $this->metadata = [
            'entityid' => 'http//example.org/idp',
            'name' => [
                'en' => 'Example service',
            ],
            'description' => [
                'en' => 'Example description'
            ],
        ];

        $this->sampleProvider = $this->prepareSampleProvider($this->metadata);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(AbstractProvider::class, $this->sampleProvider);

        $this->assertSame($this->metadata, $this->sampleProvider->getMetadata());
        $this->assertSame(
            $this->metadata['entityid'],
            $this->sampleProvider->getEntityId()
        );
        $this->assertSame(
            $this->metadata['name']['en'],
            $this->sampleProvider->getName()
        );
        $this->assertSame(
            $this->metadata['description']['en'],
            $this->sampleProvider->getDescription()
        );
    }

    public function testCanResolveNonLocalizedString(): void
    {
        $metadata = $this->metadata;
        $metadata['description'] = 'Non localized description.';

        $identityProvider = $this->prepareSampleProvider($metadata);

        $this->assertSame($metadata['description'], $identityProvider->getDescription());
    }

    public function testInvalidLocalizedDataResolvesToNull(): void
    {
        $metadata = $this->metadata;
        $metadata['description'] = false;

        $identityProvider = $this->prepareSampleProvider($metadata);

        $this->assertNull($identityProvider->getDescription());
    }

    public function testReturnsNullIfNameNotAvailable(): void
    {
        $metadata = $this->metadata;
        unset($metadata['name']);

        $identityProvider = $this->prepareSampleProvider($metadata);
        $this->assertNull($identityProvider->getName());
    }

    protected function prepareSampleProvider(array $metadata): AbstractProvider
    {
        return new class ($metadata) extends AbstractProvider {
            public function getName(string $locale = 'en'): ?string
            {
                return $this->resolveOptionallyLocalizedString('name', $locale);
            }

            public function getDescription(string $locale = 'en'): ?string
            {
                return $this->resolveOptionallyLocalizedString('description', $locale);
            }

            protected function resolveEntityId(): string
            {
                return (string)($this->metadata['entityid'] ?? 'N/A');
            }

            public function getProtocol(): AuthenticationProtocolInterface
            {
                return new class implements AuthenticationProtocolInterface {
                    public function getDesignation(): string
                    {
                        return 'designation';
                    }

                    public function getId(): int
                    {
                        return 999;
                    }
                };
            }

            protected function getProviderDescription(): string
            {
                return 'provider description';
            }

            public function getLogoUrl(): ?string
            {
                return 'https://example.org/logo';
            }
        };
    }
}
