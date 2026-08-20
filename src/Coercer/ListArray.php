<?php

declare(strict_types=1);

namespace Celema\Sire\Coercer;

use Celema\Sire\Coercion;
use Celema\Sire\CoercionMode;
use Celema\Sire\Contract;
use Celema\Sire\Failure;
use Override;

/** @api */
final class ListArray implements Contract\Coercer
{
	public string $message {
		get => '{label} must be a list';
	}

	#[Override]
	public function coerce(mixed $pristine, CoercionMode $mode): Contract\Coercion
	{
		if (
			is_array($pristine)
				&& (
					$pristine === []
					|| array_keys($pristine) === range(0, count($pristine) - 1)
				)
		) {
			return new Coercion($pristine, $pristine, empty: $pristine === []);
		}

		return new Coercion(
			$pristine,
			$pristine,
			Failure::invalid(),
			empty: self::isEmpty($pristine),
		);
	}

	private static function isEmpty(mixed $value): bool
	{
		return $value === null || $value === '' || $value === [];
	}
}
