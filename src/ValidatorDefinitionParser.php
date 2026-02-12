<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;

/**
 * @psalm-api
 */
final class ValidatorDefinitionParser implements ValidatorDefinitionParserInterface
{
	#[Override]
	/** @return array{name: string, args: list<string>} */
	public function parse(string $validatorDefinition): array
	{
		$validatorArray = explode(':', $validatorDefinition);

		return [
			'name' => $validatorArray[0],
			'args' => array_slice($validatorArray, 1),
		];
	}
}
