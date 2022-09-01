<?php

namespace SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use Throwable;

abstract class AbstractRawEntity
{
    protected array $rawRow;
    protected AbstractPlatform $abstractPlatform;

    public function __construct(array $rawRow, AbstractPlatform $abstractPlatform)
    {
        $this->rawRow = $rawRow;
        $this->abstractPlatform = $abstractPlatform;
    }

    /**
     * @throws UnexpectedValueException
     */
    abstract protected function validate(array $rawRow): void;

    /**
     * @param mixed $value
     * @return DateTimeImmutable
     */
    protected function resolveDateTimeImmutable($value): DateTimeImmutable
    {
        try {
            /** @var DateTimeImmutable $createdAt */
            $createdAt = (Type::getType(Types::DATETIME_IMMUTABLE))
                ->convertToPHPValue($value, $this->abstractPlatform);
        } catch (Throwable $exception) {
            $message = sprintf(
                'Could not create DateTimeImmutable using value %s. Error was: %s.',
                var_export($value, true),
                $exception->getMessage()
            );
            throw new UnexpectedValueException($message, (int)$exception->getCode(), $exception);
        }

        return $createdAt;
    }
}