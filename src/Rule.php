<?php

declare(strict_types=1);

namespace Duon\Sire;

/**
 * @psalm-api
 */
final class Rule
{
	/** @psalm-suppress PropertyNotSetInConstructor */
	public readonly string $label;

	public function __construct(
		public readonly string $field,
		public readonly string|Contract\Schema $type,
		public readonly array $validators,
	) {}

	public function label(string $label): static
	{
		/** @psalm-suppress InaccessibleProperty */
		$this->label = $label;

		return $this;
	}

	public function name(): string
	{
		/** @psalm-suppress RedundantPropertyInitializationCheck */
		return isset($this->label) ? $this->label : $this->field;
	}

	public function type(): string
	{
		return is_string($this->type) ? $this->type : 'schema';
	}
}
