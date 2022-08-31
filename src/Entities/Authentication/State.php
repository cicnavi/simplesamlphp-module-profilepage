<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Entities\Authentication;

use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;

class State
{
    protected string $idpEntityId;
    protected string $spEntityId;
    protected array $attributes;
    protected \DateTimeImmutable $createdAt;
    protected \DateTimeImmutable $authnInstant;

    public function __construct(array $state, \DateTimeImmutable $createdAt = null)
    {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();

        $this->idpEntityId = $this->resolveSourceEntityId($state);
        $this->spEntityId = $this->resolveDestinationEntityId($state);
        $this->attributes = $this->resolveAttributes($state);
        $this->authnInstant = $this->resolveAuthnInstant($state);
    }

    protected function resolveSourceEntityId(array $state): string
    {
        if (!empty($state['Source']['entityid']) && is_string($state['Source']['entityid'])) {
            return $state['Source']['entityid'];
        } elseif (
            !empty($state['IdPMetadata']['entityid']) &&
            is_string($state['IdPMetadata']['entityid'])
        ) {
            return $state['IdPMetadata']['entityid'];
        }

        throw new UnexpectedValueException('State array does not contain source (IdP) entity ID.');
    }

    protected function resolveDestinationEntityId(array $state): string
    {
        if (!empty($state['Destination']['entityid']) && is_string($state['Destination']['entityid'])) {
            return $state['Destination']['entityid'];
        } elseif (
            !empty($state['SPMetadata']['entityid']) &&
            is_string($state['SPMetadata']['entityid'])
        ) {
            return $state['SPMetadata']['entityid'];
        }

        throw new UnexpectedValueException('State array does not contain destination (SP) entity ID.');
    }

    protected function resolveAttributes(array $state): array
    {
        if (empty($state['Attributes']) || !is_array($state['Attributes'])) {
            throw new UnexpectedValueException('State array does not contain user attributes.');
        }

        return $state['Attributes'];
    }

    public function getIdpEntityId(): string
    {
        return $this->idpEntityId;
    }

    public function getSpEntityId(): string
    {
        return $this->spEntityId;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttributeValue(string $attributeName): ?string
    {
        if (!empty($this->attributes[$attributeName]) && is_array($this->attributes[$attributeName])) {
            return (string)reset($this->attributes[$attributeName]);
        }

        return null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    protected function resolveAuthnInstant(array $state): \DateTimeImmutable
    {
        if (empty($state['AuthnInstant'])) {
            throw new UnexpectedValueException('State array does not contain AuthnInstant value.');
        }

        $authInstant = (string)$state['AuthnInstant'];

        try {
            return new \DateTimeImmutable('@' . $authInstant);
        } catch (\Throwable $exception) {
            $message = sprintf(
                'Unable to create DateTimeImmutable using AuthInstant value \'%s\'. Error was: %s.',
                $authInstant,
                $exception->getMessage()
            );
            throw new UnexpectedValueException($message);
        }
    }

    public function getAuthnInstant(): \DateTimeImmutable
    {
        return $this->authnInstant;
    }
}
