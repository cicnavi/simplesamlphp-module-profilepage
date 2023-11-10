<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Providers\Bases;

use SimpleSAML\Module\profilepage\Entities\Authentication;
use SimpleSAML\Module\profilepage\Entities\Bases\AbstractProvider;
use SimpleSAML\Module\profilepage\Entities\Interfaces\AuthenticationProtocolInterface;

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
