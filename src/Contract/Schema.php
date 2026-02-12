<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

/**
 * @psalm-api
 */
interface Schema
{
	public function validate(array $data, int $level = 1): bool;

	public function errors(bool $grouped = false): array;

	public function values(): array;

	public function pristineValues(): array;
}
