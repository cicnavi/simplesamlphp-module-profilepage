<?php

namespace SimpleSAML\Module\accounting\Entities\Providers\Bases;

use SimpleSAML\Module\accounting\Entities\Authentication;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractProvider;
use SimpleSAML\Module\accounting\Entities\Interfaces\AuthenticationProtocolInterface;

abstract class AbstractOidcProvider extends AbstractProvider
{
    public const METADATA_KEY_LOGO_URI = 'logo_uri';

    abstract public function getName(string $locale = self::DEFAULT_LOCALE): ?string;
    abstract public function getDescription(string $locale = self::DEFAULT_LOCALE): ?string;
    abstract protected function resolveEntityId(): string;
    abstract protected function getProviderDescription(): string;

    public function getLogoUrl(): ?string
    {
        if (
            !empty($this->metadata[self::METADATA_KEY_LOGO_URI]) &&
            is_string($this->metadata[self::METADATA_KEY_LOGO_URI])
        ) {
            return $this->metadata[self::METADATA_KEY_LOGO_URI];
        }

        return null;
    }

    public function getProtocol(): AuthenticationProtocolInterface
    {
        return new Authentication\Protocol\Oidc();
    }
}
