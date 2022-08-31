<?php

namespace SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;

use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\Helpers\HashHelper;

class HashDecoratedState
{
    protected State $state;
    protected string $idpEntityIdHashSha256;
    protected string $spEntityIdHashSha256;

    public function __construct(State $state)
    {
        $this->state = $state;
        $this->idpEntityIdHashSha256 = HashHelper::getSha256($state->getIdpEntityId());
        $this->spEntityIdHashSha256 = HashHelper::getSha256($state->getSpEntityId());
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
}
