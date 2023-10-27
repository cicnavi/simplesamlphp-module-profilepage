<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Interfaces;

interface SerializerInterface
{
	/**
	 * Serialize provided value.
	 */
	public function do(mixed $value): string;

	/**
	 * Un-serialize provided value.
	 */
	public function undo(string $value): mixed;
}