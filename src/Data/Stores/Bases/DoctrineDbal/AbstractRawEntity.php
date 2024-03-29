<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;
use Throwable;

/**
 * @psalm-api
 */
abstract class AbstractRawEntity
{
    public function __construct(
        protected array $rawRow,
        protected AbstractPlatform $abstractPlatform,
        protected SerializerInterface $serializer
    ) {
        $this->validate($rawRow);
    }

    /**
     * @throws UnexpectedValueException
     */
    abstract protected function validate(array $rawRow): void;

    protected function resolveDateTimeImmutable(int $timestamp): DateTimeImmutable
    {
        return (new DateTimeImmutable())->setTimestamp($timestamp);
    }
}
