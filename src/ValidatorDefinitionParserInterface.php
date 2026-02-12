<?php

declare(strict_types=1);

namespace Duon\Sire;

/**
 * @psalm-api
 */
interface ValidatorDefinitionParserInterface
{
	/** @return array{name: string, args: list<string>} */
	public function parse(string $validatorDefinition): array;
}
