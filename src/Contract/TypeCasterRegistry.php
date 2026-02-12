<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

/**
 * @psalm-api
 */
interface TypeCasterRegistry
{
	/** @return array<string, TypeCaster> */
	public function all(): array;
}
