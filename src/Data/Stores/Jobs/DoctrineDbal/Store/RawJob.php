<?php

declare(strict_types=1);

namespace SimpleSAML\Module\profilepage\Data\Stores\Jobs\DoctrineDbal\Store;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use SimpleSAML\Module\profilepage\Data\Stores\Bases\DoctrineDbal\AbstractRawEntity;
use SimpleSAML\Module\profilepage\Exceptions\UnexpectedValueException;
use SimpleSAML\Module\profilepage\Interfaces\SerializerInterface;

use function sprintf;

class RawJob extends AbstractRawEntity
{
    protected int $id;
    protected array $payload;
    protected string $type;
    protected DateTimeImmutable $createdAt;

    public function __construct(array $rawRow, AbstractPlatform $abstractPlatform, SerializerInterface $serializer)
    {
        parent::__construct($rawRow, $abstractPlatform, $serializer);

        $this->id = (int)$rawRow[TableConstants::COLUMN_NAME_ID];
        $this->payload = $this->resolvePayload((string)$rawRow[TableConstants::COLUMN_NAME_PAYLOAD]);
        $this->type = (string)$rawRow[TableConstants::COLUMN_NAME_TYPE];
        $this->createdAt = $this->resolveDateTimeImmutable((int)$rawRow[TableConstants::COLUMN_NAME_CREATED_AT]);
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

        if (! is_numeric($rawRow[TableConstants::COLUMN_NAME_CREATED_AT])) {
            throw new UnexpectedValueException(
                sprintf('Column %s must be numeric.', TableConstants::COLUMN_NAME_CREATED_AT)
            );
        }
    }

    protected function resolvePayload(string $rawPayload): array
    {
        /** @psalm-suppress MixedAssignment - we check the type manually */
        $payload = $this->serializer->undo($rawPayload);

        if (is_array($payload)) {
            return $payload;
        }

        throw new UnexpectedValueException('Job payload is not in expected array format.');
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getPayload(): array
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
