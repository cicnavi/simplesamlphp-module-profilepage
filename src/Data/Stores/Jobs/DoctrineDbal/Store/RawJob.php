<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use SimpleSAML\Module\accounting\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity;
use SimpleSAML\Module\accounting\Data\Stores\Jobs\DoctrineDbal\Store;
use SimpleSAML\Module\accounting\Entities\Bases\AbstractPayload;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;

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

        $this->id = (int)$rawRow[TableConstants::COLUMN_NAME_ID];
        $this->payload = $this->resolvePayload((string)$rawRow[TableConstants::COLUMN_NAME_PAYLOAD]);
        $this->type = (string)$rawRow[TableConstants::COLUMN_NAME_TYPE];
        $this->createdAt = $this->resolveDateTimeImmutable($rawRow[TableConstants::COLUMN_NAME_CREATED_AT]);
    }

    protected function validate(array $rawRow): void
    {
        $columnsToCheck = [
            TableConstants::COLUMN_NAME_ID,
            TableConstants::COLUMN_NAME_PAYLOAD,
            TableConstants::COLUMN_NAME_TYPE,
            TableConstants::COLUMN_NAME_CREATED_AT,
        ];

        foreach ($columnsToCheck as $column) {
            if (empty($rawRow[$column])) {
                throw new UnexpectedValueException(sprintf('Column %s must be set.', $column));
            }
        }

        if (! is_numeric($rawRow[TableConstants::COLUMN_NAME_ID])) {
            throw new UnexpectedValueException(
                sprintf('Column %s must be numeric.', TableConstants::COLUMN_NAME_ID)
            );
        }

        if (! is_string($rawRow[TableConstants::COLUMN_NAME_PAYLOAD])) {
            throw new UnexpectedValueException(
                sprintf('Column %s must be string.', TableConstants::COLUMN_NAME_PAYLOAD)
            );
        }

        if (! is_string($rawRow[TableConstants::COLUMN_NAME_TYPE])) {
            throw new UnexpectedValueException(
                sprintf('Column %s must be string.', TableConstants::COLUMN_NAME_TYPE)
            );
        }

        if (! is_string($rawRow[TableConstants::COLUMN_NAME_CREATED_AT])) {
            throw new UnexpectedValueException(
                sprintf('Column %s must be string.', TableConstants::COLUMN_NAME_CREATED_AT)
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

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}