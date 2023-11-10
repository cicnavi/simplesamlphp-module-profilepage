<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Entities\Authentication\Protocol;

use SimpleSAML\Module\profilepage\Entities\Interfaces\AuthenticationProtocolInterface;

class Bag
{
    /**
     * @var array<int,AuthenticationProtocolInterface>
     */
    protected array $protocols = [];

    public function __construct()
    {
        $saml2 = new Saml2();
        $this->protocols[$saml2->getId()] = $saml2;
        $oidc = new Oidc();
        $this->protocols[$oidc->getId()] = $oidc;
    }

    public function getById(int $id): ?AuthenticationProtocolInterface
    {
        return $this->protocols[$id] ?? null;
    }

    public function getAll(): array
    {
        return $this->protocols;
    }
}
