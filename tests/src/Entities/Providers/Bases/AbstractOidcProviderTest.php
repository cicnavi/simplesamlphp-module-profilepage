<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\accounting\Entities\Providers\Bases;

use PHPUnit\Framework\MockObject\Stub;
use SimpleSAML\Module\accounting\Entities\Authentication\Protocol\Oidc;
use SimpleSAML\Module\accounting\Entities\Providers\Bases\AbstractOidcProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Helpers\Arr;
use SimpleSAML\Module\accounting\Services\HelpersManager;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Providers\Bases\AbstractOidcProvider
 * @uses \SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider
 */
class AbstractOidcProviderTest extends TestCase
{
    protected Stub $helpersManagerStub;
    protected Stub $arrStub;
    protected array $metadata;

    protected function setUp(): void
    {
        $this->helpersManagerStub = $this->createStub(HelpersManager::class);
        $this->arrStub = $this->createStub(Arr::class);
        $this->metadata = StateArrays::OIDC_FULL['Oidc']['RelyingPartyMetadata'];
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(AbstractOidcProvider::class, $this->prepareInstance(StateArrays::OIDC_FULL));
    }

    public function testCanGetLogoUrl(): void
    {
        $this->assertSame(
            $this->metadata['logo_uri'],
            $this->prepareInstance($this->metadata)->getLogoUrl()
        );

        $metadaNoLogo = $this->metadata;
        unset($metadaNoLogo['logo_uri']);

        $this->assertNull($this->prepareInstance($metadaNoLogo)->getLogoUrl());
    }

    public function testCanGetProtocol(): void
    {
        $this->assertInstanceOf(Oidc::class, $this->prepareInstance($this->metadata)->getProtocol());
    }

    protected function prepareInstance(array $metadata): AbstractOidcProvider
    {
        $this->helpersManagerStub->method('getArr')->willReturn($this->arrStub);

        return new class ($metadata, $this->helpersManagerStub) extends AbstractOidcProvider {
            public function getName(string $locale = self::DEFAULT_LOCALE): ?string
            {
                return 'name';
            }

            public function getDescription(string $locale = self::DEFAULT_LOCALE): ?string
            {
                return 'description';
            }

            protected function resolveEntityId(): string
            {
                return 'entityId';
            }

            protected function getProviderDescription(): string
            {
                return 'provider description';
            }
        };
    }
}
