<?php

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\Helpers\HashHelper;

class HashDecoratedState
{
    protected State $state;
    protected string $idpEntityIdHashSha256;
    protected string $spEntityIdHashSha256;
    protected string $idpMetadataArrayHashSha256;
    protected string $spMetadataArrayHashSha256;
    protected string $attributesArrayHashSha256;

    public function __construct(State $state)
    {
        $this->state = $state;

        $this->idpEntityIdHashSha256 = HashHelper::getSha256($state->getIdpEntityId());
        $this->idpMetadataArrayHashSha256 = HashHelper::getSha256ForArray($state->getIdpMetadataArray());

        $this->spEntityIdHashSha256 = HashHelper::getSha256($state->getSpEntityId());
        $this->spMetadataArrayHashSha256 = HashHelper::getSha256ForArray($state->getSpMetadataArray());

        $this->attributesArrayHashSha256 = HashHelper::getSha256ForArray($state->getAttributes());
    }

    /**
     * @return State
     */
    public function getState(): State
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getIdpEntityIdHashSha256(): string
    {
        return $this->idpEntityIdHashSha256;
    }

    /**
     * @return string
     */
    public function getSpEntityIdHashSha256(): string
    {
        return $this->spEntityIdHashSha256;
    }

    /**
     * @return string
     */
    public function getIdpMetadataArrayHashSha256(): string
    {
        return $this->idpMetadataArrayHashSha256;
    }

    public function getSpMetadataArrayHashSha256(): string
    {
        return $this->spMetadataArrayHashSha256;
    }

    /**
     * @return string
     */
    public function getAttributesArrayHashSha256(): string
    {
        return $this->attributesArrayHashSha256;
    }
}
