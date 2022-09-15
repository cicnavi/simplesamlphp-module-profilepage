<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\accounting\Stores\Bases\DoctrineDbal\AbstractRawEntity;
use SimpleSAML\Module\accounting\Stores\Jobs\DoctrineDbal\Store;
use Throwable;

use function sprintf;

class RawJob extends AbstractRawEntity
{
    protected int $id;
    protected AbstractPayload $payload;
    protected string $type;
    protected DateTimeImmutable $createdAt;

    public function __construct(array $rawRow, AbstractPlatform $abstractPlatform)
    {
        parent::__construct($rawRow, $abstractPlatform);

        $this->id = (int)$rawRow[Store\TableConstants::COLUMN_NAME_ID];
        $this->payload = $this->resolvePayload((string)$rawRow[Store\TableConstants::COLUMN_NAME_PAYLOAD]);
        $this->type = (string)$rawRow[Store\TableConstants::COLUMN_NAME_TYPE];
        $this->createdAt = $this->resolveDateTimeImmutable($rawRow[Store\TableConstants::COLUMN_NAME_CREATED_AT]);
    }

    protected function validate(array $rawRow): void
    {
        $columnsToCheck = [
            Store\TableConstants::COLUMN_NAME_ID,
            Store\TableConstants::COLUMN_NAME_PAYLOAD,
            Store\TableConstants::COLUMN_NAME_TYPE,
            Store\TableConstants::COLUMN_NAME_CREATED_AT,
        ];

        foreach ($columnsToCheck as $column) {
            if (empty($rawRow[$column])) {
                throw new UnexpectedValueException(sprintf('Column %s must be set.', $column));
            }
        }

        if (! is_numeric($rawRow[Store\TableConstants::COLUMN_NAME_ID])) {
            throw new UnexpectedValueException(
                sprintf('Column %s must be numeric.', Store\TableConstants::COLUMN_NAME_ID)
            );
        }

        if (! is_string($rawRow[Store\TableConstants::COLUMN_NAME_PAYLOAD])) {
            throw new UnexpectedValueException(
                sprintf('Column %s must be string.', Store\TableConstants::COLUMN_NAME_PAYLOAD)
            );
        }

        if (! is_string($rawRow[Store\TableConstants::COLUMN_NAME_TYPE])) {
            throw new UnexpectedValueException(
                sprintf('Column %s must be string.', Store\TableConstants::COLUMN_NAME_TYPE)
            );
        }

        if (! is_string($rawRow[Store\TableConstants::COLUMN_NAME_CREATED_AT])) {
            throw new UnexpectedValueException(
                sprintf('Column %s must be string.', Store\TableConstants::COLUMN_NAME_CREATED_AT)
            );
        }
    }

    protected function resolvePayload(string $rawPayload): AbstractPayload
    {
        /** @psalm-suppress MixedAssignment - we check the type manually */
        $payload = unserialize($rawPayload);

        if ($payload instanceof AbstractPayload) {
            return $payload;
        }

        throw new UnexpectedValueException('Job payload is not instance of AbstractPayload.');
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return AbstractPayload
     */
    public function getPayload(): AbstractPayload
    {
        return $this->payload;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    protected function resolveCreatedAt(array $rawRow): DateTimeImmutable
    {
        try {
            /** @var DateTimeImmutable $createdAt */
            $createdAt = (Type::getType(Types::DATETIME_IMMUTABLE))
                ->convertToPHPValue($rawRow[Store\TableConstants::COLUMN_NAME_CREATED_AT], $this->abstractPlatform);
        } catch (Throwable $exception) {
            throw new UnexpectedValueException(
                sprintf(
                    'Could not create instance of DateTimeImmutable using value %s for column %s.',
                    var_export($rawRow[Store\TableConstants::COLUMN_NAME_CREATED_AT], true),
                    Store\TableConstants::COLUMN_NAME_CREATED_AT
                ),
                (int)$exception->getCode(),
                $exception
            );
        }

        return $createdAt;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
