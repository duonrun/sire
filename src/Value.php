<?php

declare(strict_types=1);

namespace Duon\Sire;

/**
 * @psalm-api
 */
class Value
{
	public function __construct(
		public readonly mixed $value,
		public readonly mixed $pristine,
		public readonly null|array|string $error = null,
	) {}
}
