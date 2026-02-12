<?php

declare(strict_types=1);

namespace Duon\Sire;

/**
 * @psalm-api
 */
interface ValidatorRegistryInterface
{
	/** @return array<string, Validator> */
	public function all(): array;
}
