<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\ValidatorDefinitionParser;
use ValueError;

class ValidatorDefinitionParserTest extends TestCase
{
	public function testParsesSimpleValidator(): void
	{
		$parser = new ValidatorDefinitionParser();

		$this->assertSame(
			['name' => 'required', 'args' => []],
			$parser->parse('required'),
		);
	}

	public function testParsesQuotedAndEscapedArguments(): void
	{
		$parser = new ValidatorDefinitionParser();

		$this->assertSame(
			['name' => 'starts_with', 'args' => ['http://']],
			$parser->parse('starts_with:http\\://'),
		);

		$this->assertSame(
			['name' => 'starts_with', 'args' => ['http://']],
			$parser->parse('starts_with:"http://"'),
		);
	}

	public function testParsesRegexWithColonLikeLegacyDsl(): void
	{
		$parser = new ValidatorDefinitionParser();

		$this->assertSame(
			[
				'name' => 'regex',
				'args' => ['/^[a-z', ']+', '$/'],
			],
			$parser->parse('regex:/^[a-z:]+:$/'),
		);
	}

	public function testThrowsOnInvalidDefinition(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('unclosed quote');

		$parser = new ValidatorDefinitionParser();
		$parser->parse('in:"foo,bar');
	}

	public function testThrowsOnMissingValidatorName(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('missing validator name');

		$parser = new ValidatorDefinitionParser();
		$parser->parse(':10');
	}

	public function testParsesEmptyArgument(): void
	{
		$parser = new ValidatorDefinitionParser();

		$this->assertSame(
			['name' => 'regex', 'args' => ['']],
			$parser->parse('regex:'),
		);
	}
}
