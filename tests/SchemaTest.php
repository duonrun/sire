<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Contract\ValidatorDefinitionParser as ValidatorDefinitionParserContract;
use Duon\Sire\Schema;
use Duon\Sire\TypeCaster;
use Duon\Sire\TypeCasterRegistry;
use Duon\Sire\ValidationResult;
use Duon\Sire\Validator;
use Duon\Sire\ValidatorRegistry;
use Duon\Sire\Value;
use Duon\Sire\Violation;
use Override;
use ValueError;

class SchemaTest extends TestCase
{
	public function testTypeInt(): void
	{
		$testData = [
			'valid_int_1' => '13',
			'valid_int_2' => 13,
			'invalid_int_1' => '23invalid',
			'invalid_int_2' => '23.23',
		];

		$schema = new Schema();
		$schema->add('invalid_int_1', 'int')->label('Int 1');
		$schema->add('invalid_int_2', 'int');
		$schema->add('valid_int_1', 'int')->label('Int');
		$schema->add('valid_int_2', 'int')->label('Int');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertSame('Invalid number', $errors['errors'][0]['error']);
		$this->assertSame('invalid_int_1', $errors['errors'][0]['field']);
		$this->assertSame('Int 1', $errors['errors'][0]['label']);
		$this->assertSame('Invalid number', $errors['errors'][1]['error']);
		$this->assertSame('invalid_int_2', $errors['errors'][1]['field']);
		$this->assertSame('invalid_int_2', $errors['errors'][1]['label']);
		$this->assertSame('Invalid number', $errors['map']['invalid_int_1'][0]);
		$this->assertSame('Invalid number', $errors['map']['invalid_int_2'][0]);
		$this->assertFalse(isset($errors['map']['valid_int_1']));
		$this->assertFalse(isset($errors['map']['valid_int_2']));

		$values = $schema->values();
		$this->assertSame(13, $values['valid_int_1']);
		$this->assertSame(13, $values['valid_int_2']);
		$this->assertSame('23invalid', $values['invalid_int_1']);

		$pristine = $schema->pristineValues();
		$this->assertSame('13', $pristine['valid_int_1']);
		$this->assertSame(13, $pristine['valid_int_2']);
	}

	public function testTypeFloat(): void
	{
		$testData = [
			'valid_float_1' => '13',
			'valid_float_2' => '13.13',
			'valid_float_3' => 13,
			'valid_float_4' => 13.13,
			'invalid_float' => '23.23invalid',
		];

		$schema = new Schema();
		$schema->add('invalid_float', 'float')->label('Float');
		$schema->add('valid_float_1', 'float');
		$schema->add('valid_float_2', 'float');
		$schema->add('valid_float_3', 'float');
		$schema->add('valid_float_4', 'float');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertSame('Invalid number', $errors['errors'][0]['error']);
		$this->assertSame('Invalid number', $errors['map']['invalid_float'][0]);
		$this->assertFalse(isset($errors['map']['valid_float_1']));
		$this->assertFalse(isset($errors['map']['valid_float_2']));
		$this->assertFalse(isset($errors['map']['valid_float_3']));
		$this->assertFalse(isset($errors['map']['valid_float_4']));
	}

	public function testTypeBoolean(): void
	{
		$testData = [
			'valid_bool_1' => true,
			'valid_bool_2' => false,
			'valid_bool_3' => 'yes',
			'valid_bool_4' => 'off',
			'valid_bool_5' => 'true',
			'valid_bool_6' => 'null',
			'valid_bool_8' => null,
			'invalid_bool_1' => 'invalid',
			'invalid_bool_2' => 13,
		];

		$schema = new Schema();
		$schema->add('valid_bool_1', 'bool');
		$schema->add('valid_bool_2', 'bool');
		$schema->add('valid_bool_3', 'bool');
		$schema->add('valid_bool_4', 'bool');
		$schema->add('valid_bool_5', 'bool');
		$schema->add('valid_bool_6', 'bool');
		$schema->add('valid_bool_7', 'bool');
		$schema->add('valid_bool_8', 'bool');
		$schema->add('invalid_bool_1', 'bool')->label('Bool 1');
		$schema->add('invalid_bool_2', 'bool');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertSame('Invalid boolean', $errors['errors'][0]['error']);
		$this->assertSame('Invalid boolean', $errors['errors'][1]['error']);
		$this->assertSame('Invalid boolean', $errors['map']['invalid_bool_1'][0]);
		$this->assertSame('Invalid boolean', $errors['map']['invalid_bool_2'][0]);
		$this->assertFalse(isset($errors['map']['valid_bool_1']));
		$this->assertFalse(isset($errors['map']['valid_bool_2']));

		$values = $schema->values();
		$this->assertSame(true, $values['valid_bool_1']);
		$this->assertSame(false, $values['valid_bool_2']);
		$this->assertSame(true, $values['valid_bool_3']);
		$this->assertSame(false, $values['valid_bool_4']);
		$this->assertSame(true, $values['valid_bool_5']);
		$this->assertSame(false, $values['valid_bool_6']);
		$this->assertSame(false, $values['valid_bool_7']);
		$this->assertSame(false, $values['valid_bool_8']);

		$pristine = $schema->pristineValues();
		$this->assertSame('yes', $pristine['valid_bool_3']);
		$this->assertSame('invalid', $pristine['invalid_bool_1']);
		$this->assertSame(13, $pristine['invalid_bool_2']);
	}

	public function testTypeText(): void
	{
		$testData = [
			'valid_text_1' => 'Lorem ipsum',
			'valid_text_2' => false,
			'valid_text_3' => true,
			'valid_text_4' => '<a href="/test">Test</a>',
		];

		$schema = new Schema();
		$schema->add('valid_text_1', 'text')->label('Text');
		$schema->add('valid_text_2', 'text')->label('Text');
		$schema->add('valid_text_3', 'text')->label('Text');
		$schema->add('valid_text_4', 'text');
		$schema->add('valid_text_5', 'text');

		$this->assertTrue($schema->validate($testData));
		$this->assertCount(0, $schema->errors()['errors']);

		$values = $schema->values();

		$this->assertSame('Lorem ipsum', $values['valid_text_1']);
		$this->assertNull($values['valid_text_2']);
		$this->assertSame('1', $values['valid_text_3']);
		$this->assertSame('<a href="/test">Test</a>', $values['valid_text_4']);
		$this->assertNull($values['valid_text_5']);

		$pristine = $schema->pristineValues();
		$this->assertSame(false, $pristine['valid_text_2']);
		$this->assertNull($pristine['valid_text_5']);
	}

	public function testTypeSkipEmpty(): void
	{
		$testData = [
			'valid_text' => '',
		];

		$schema = new Schema();
		$schema->add('valid_text', 'text', 'maxlen');

		$this->assertTrue($schema->validate($testData));
	}

	public function testTypeList(): void
	{
		$testData = [
			'valid_list_1' => [1, 2],
			'valid_list_2' => [['key' => 'data']],
			'invalid_list_1' => 'invalid',
			'invalid_list_2' => 13,
		];

		$schema = new Schema();
		$schema->add('valid_list_1', 'list');
		$schema->add('valid_list_2', 'list');
		$schema->add('invalid_list_1', 'list')->label('List 1');
		$schema->add('invalid_list_2', 'list');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertSame('Invalid list', $errors['errors'][0]['error']);
		$this->assertSame('Invalid list', $errors['errors'][1]['error']);
		$this->assertSame('Invalid list', $errors['map']['invalid_list_1'][0]);
		$this->assertSame('Invalid list', $errors['map']['invalid_list_2'][0]);
		$this->assertFalse(isset($errors['map']['valid_list_1']));
		$this->assertFalse(isset($errors['map']['valid_list_2']));

		$values = $schema->values();
		$this->assertSame([1, 2], $values['valid_list_1']);
		$this->assertSame([['key' => 'data']], $values['valid_list_2']);

		$pristine = $schema->pristineValues();
		$this->assertSame([1, 2], $pristine['valid_list_1']);
		$this->assertSame('invalid', $pristine['invalid_list_1']);
		$this->assertSame(13, $pristine['invalid_list_2']);
	}

	public function testWrongType(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('Wrong schema type');

		$schema = new Schema();
		$schema->add('invalid_field', 'Invalid', 'invalid');
		$schema->validate(['invalid_field' => false]);
	}

	public function testUnknownValidator(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('Unknown validator');

		$schema = new Schema();
		$schema->add('field', 'text', 'unknown');
		$schema->validate(['field' => 'value']);
	}

	public function testCustomValidatorRegistry(): void
	{
		$registry = ValidatorRegistry::withDefaults()->with(
			'starts_with',
			new Validator(
				'starts_with',
				'Must start with %4$s',
				function (Value $value, string ...$args): bool {
					$prefix = $args[0] ?? '';

					return str_starts_with((string) $value->value, $prefix);
				},
				true,
			),
		);

		$schema = new Schema(validatorRegistry: $registry);
		$schema->add('field', 'text', 'required', 'starts_with:foo');

		$this->assertTrue($schema->validate(['field' => 'foobar']));
		$this->assertFalse($schema->validate(['field' => 'barfoo']));
		$this->assertSame('Must start with foo', $schema->errors()['map']['field'][0]);
		$this->assertFalse($schema->validate(['field' => '']));
		$this->assertSame('Required', $schema->errors()['map']['field'][0]);
	}

	public function testCustomValidatorDefinitionParser(): void
	{
		$registry = new ValidatorRegistry([
			'starts_with' => new Validator(
				'starts_with',
				'Must start with %4$s',
				function (Value $value, string ...$args): bool {
					$prefix = $args[0] ?? '';

					return str_starts_with((string) $value->value, $prefix);
				},
				true,
			),
		]);

		$parser = new class implements ValidatorDefinitionParserContract {
			#[Override]
			/** @return array{name: string, args: list<string>} */
			public function parse(string $validatorDefinition): array
			{
				$parts = explode('|', $validatorDefinition);

				return [
					'name' => $parts[0],
					'args' => array_slice($parts, 1),
				];
			}
		};

		$schema = new Schema(
			validatorRegistry: $registry,
			validatorDefinitionParser: $parser,
		);
		$schema->add('field', 'text', 'starts_with|foo');

		$this->assertTrue($schema->validate(['field' => 'foobar']));
		$this->assertFalse($schema->validate(['field' => 'barfoo']));
		$this->assertSame('Must start with foo', $schema->errors()['map']['field'][0]);
	}

	public function testCustomTypeCasterRegistry(): void
	{
		$registry = TypeCasterRegistry::withDefaults([
			'bool' => 'Invalid boolean',
			'float' => 'Invalid number',
			'int' => 'Invalid number',
			'list' => 'Invalid list',
		])->with(
			'slug',
			new TypeCaster(function (mixed $pristine, string $label): Value {
				if (!is_string($pristine) || !preg_match('/^[a-z0-9-]+$/', $pristine)) {
					return new Value($pristine, $pristine, 'Invalid slug');
				}

				return new Value($pristine, $pristine);
			}),
		);

		$schema = new Schema(typeCasterRegistry: $registry);
		$schema->add('slug', 'slug', 'required');

		$this->assertTrue($schema->validate(['slug' => 'test-slug']));
		$this->assertFalse($schema->validate(['slug' => 'Not A Slug']));
		$this->assertSame('Invalid slug', $schema->errors()['map']['slug'][0]);
	}

	public function testValidationResult(): void
	{
		$schema = new Schema();
		$schema->add('email', 'text', 'required', 'email');

		$this->assertFalse($schema->validate(['email' => 'invalid']));

		$result = $schema->result();
		$this->assertInstanceOf(ValidationResult::class, $result);
		$this->assertFalse($result->isValid());
		$this->assertSame('Invalid email address', $result->map()['email'][0]);

		$violations = $result->violations();
		$this->assertCount(1, $violations);
		$this->assertInstanceOf(Violation::class, $violations[0]);
		$this->assertSame('email', $violations[0]->field);
		$this->assertSame('Invalid email address', $violations[0]->error);

		$this->assertSame(
			$schema->errors(),
			$result->errors(),
		);
	}

	public function testResultBeforeValidation(): void
	{
		$schema = new Schema();

		$result = $schema->result();
		$this->assertTrue($result->isValid());
		$this->assertCount(0, $result->violations());
		$this->assertSame([], $result->map());
		$this->assertCount(0, $schema->violations());
	}

	public function testWrongErrorType(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('Wrong error type');

		$registry = new TypeCasterRegistry([
			'text' => new TypeCaster(function (mixed $pristine, string $label): Value {
				return new Value($pristine, $pristine, ['not', 'a', 'string']);
			}),
		]);

		$schema = new Schema(typeCasterRegistry: $registry);
		$schema->add('field', 'text');
		$schema->validate(['field' => 'value']);
	}

	public function testUnknownData(): void
	{
		$testData = [
			'unknown_1' => 'Test',
			'unknown_2' => '13',
			'unknown_3' => 'Unknown',
			'unknown_4' => '23',
		];

		$schema = new Schema();
		$schema->add('unknown_1', 'text');
		$schema->add('unknown_2', 'int');

		$this->assertTrue($schema->validate($testData));
		$this->assertCount(0, $schema->errors()['errors']);

		$values = $schema->values();
		$this->assertSame('Test', $values['unknown_1']);
		$this->assertSame(13, $values['unknown_2']);
		$this->assertFalse(isset($values['unknown_3']));

		$pristine = $schema->pristineValues();
		$this->assertSame('Test', $pristine['unknown_1']);
		$this->assertSame('13', $pristine['unknown_2']);
		$this->assertFalse(isset($pristine['unknown_3']));

		$schema = new Schema(false, true);
		$schema->add('unknown_1', 'text');
		$schema->add('unknown_2', 'int');

		$this->assertTrue($schema->validate($testData));
		$this->assertCount(0, $schema->errors()['errors']);

		$values = $schema->values();
		$this->assertSame('Test', $values['unknown_1']);
		$this->assertSame(13, $values['unknown_2']);
		$this->assertSame('Unknown', $values['unknown_3']);
		$this->assertSame('23', $values['unknown_4']);

		$pristine = $schema->pristineValues();
		$this->assertSame('Test', $pristine['unknown_1']);
		$this->assertSame('13', $pristine['unknown_2']);
		$this->assertSame('Unknown', $pristine['unknown_3']);
		$this->assertSame('23', $pristine['unknown_4']);
	}

	public function testRequiredValidator(): void
	{
		$testData = [
			'valid_1' => 'value',
			'valid_2' => false,
			'valid_3' => 0,
			'valid_4' => 0.0,
			'valid_5' => [1],
			'invalid_3' => [],
			'invalid_4' => '',
		];

		$schema = new Schema();
		$schema->add('valid_1', 'text', 'required');
		$schema->add('valid_2', 'bool', 'required');
		$schema->add('valid_3', 'int', 'required');
		$schema->add('valid_4', 'float', 'required');
		$schema->add('valid_5', 'list', 'required');
		$schema->add('invalid_1', 'text', 'required');
		$schema->add('invalid_2', 'float', 'required')->label('Required 2');
		$schema->add('invalid_3', 'list', 'required');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertCount(3, $errors['errors']);
		$this->assertSame('Required', $errors['map']['invalid_1'][0]);
		$this->assertSame('Required', $errors['map']['invalid_2'][0]);
		$this->assertSame('Required', $errors['map']['invalid_3'][0]);
	}

	public function testEmailValidator(): void
	{
		$testData = [
			'valid_email' => 'valid@email.com',
			'invalid_email' => 'invalid@email',
		];

		$schema = new Schema();
		$schema->add('invalid_email', 'text', 'email')->label('Email');
		$schema->add('valid_email', 'text', 'email');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame('Invalid email address', $errors['map']['invalid_email'][0]);
	}

	public function testEmailValidatorWithDnsCheck(): void
	{
		$testData = [
			'valid_email' => 'valid@gmail.com',
			'invalid_email' => 'invalid@test.tld',
		];

		$schema = new Schema();
		$schema->add('invalid_email', 'text', 'email:checkdns');
		$schema->add('valid_email', 'text', 'email:checkdns');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame('Invalid email address', $errors['map']['invalid_email'][0]);
	}

	public function testMinValueValidator(): void
	{
		$testData = [
			'valid_1' => 13,
			'valid_2' => 13,
			'valid_3' => 10,
			'valid_4' => 10,
			'invalid_1' => 7,
			'invalid_2' => 7.13,
		];

		$schema = new Schema();
		$schema->add('valid_1', 'int', 'min:10');
		$schema->add('valid_2', 'float', 'min:10');
		$schema->add('valid_3', 'int', 'min:10');
		$schema->add('valid_4', 'float', 'min:10');
		$schema->add('invalid_1', 'int', 'min:10')->label('Min');
		$schema->add('invalid_2', 'float', 'min:10');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertCount(2, $errors['errors']);
		$this->assertSame('Lower than the required minimum of 10', $errors['map']['invalid_1'][0]);
		$this->assertSame('Lower than the required minimum of 10', $errors['map']['invalid_2'][0]);
	}

	public function testMaxValueValidator(): void
	{
		$testData = [
			'valid_1' => 13,
			'valid_2' => 13,
			'valid_3' => 10,
			'valid_4' => 10,
			'invalid_1' => 23,
			'invalid_2' => 23.13,
		];

		$schema = new Schema();
		$schema->add('valid_1', 'int', 'max:13');
		$schema->add('valid_2', 'float', 'max:13');
		$schema->add('valid_3', 'int', 'max:13');
		$schema->add('valid_4', 'float', 'max:13');
		$schema->add('invalid_1', 'int', 'max:13');
		$schema->add('invalid_2', 'float', 'max:13')->label('Max');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertCount(2, $errors['errors']);
		$this->assertSame('Higher than the allowed maximum of 13', $errors['map']['invalid_1'][0]);
		$this->assertSame('Higher than the allowed maximum of 13', $errors['map']['invalid_2'][0]);
	}

	public function testMinLengthValidator(): void
	{
		$testData = [
			'valid_1' => 'abcdefghijklm',
			'valid_2' => 'abcdefghij',
			'invalid' => 'abcdefghi',
		];

		$schema = new Schema();
		$schema->add('valid_1', 'text', 'minlen:10');
		$schema->add('valid_2', 'text', 'minlen:10');
		$schema->add('invalid', 'text', 'minlen:10');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame(
			'Shorter than the minimum length of 10 characters',
			$errors['map']['invalid'][0],
		);
	}

	public function testMaxLengthValidator(): void
	{
		$testData = [
			'valid_1' => 'abcdefghi',
			'valid_2' => 'abcdefghij',
			'invalid' => 'abcdefghiklm',
		];

		$schema = new Schema();
		$schema->add('valid_1', 'text', 'maxlen:10');
		$schema->add('valid_2', 'text', 'maxlen:10');
		$schema->add('invalid', 'text', 'maxlen:10');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame(
			'Exeeds the maximum length of 10 characters',
			$errors['map']['invalid'][0],
		);
	}

	public function testRegexValidator(): void
	{
		$testData = [
			'valid' => 'abcdefghi',
			'invalid' => 'abcdefghiklm',
			'valid_colon' => 'abcdef:ghi:klm:',
			'invalid_colon' => 'abcdef:ghi:klm',
		];

		$schema = new Schema();
		$schema->add('valid', 'text', 'regex:/^abcdefghi$/');
		$schema->add('invalid', 'text', 'regex:/^abcdefghi$/');
		$schema->add('valid_colon', 'text', 'regex:/^[a-z:]+:$/');
		$schema->add('invalid_colon', 'text', 'regex:/^[a-z:]+:$/');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertCount(2, $errors['errors']);
		$this->assertSame('Does not match the required pattern', $errors['map']['invalid'][0]);
	}

	public function testInValidator(): void
	{
		$testData = [
			'valid1' => 'valid',
			'valid2' => 'alsovalid',
			'invalid' => 'invalid',
		];

		$schema = new Schema();
		$schema->add('valid1', 'text', 'in:valid,alsovalid');
		$schema->add('valid2', 'text', 'in:valid,alsovalid');
		$schema->add('invalid', 'text', 'in:valid,alsovalid');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame('Invalid value', $errors['map']['invalid'][0]);
	}

	public function testSubSchema(): void
	{
		$testData = [
			'int' => 13,
			'text' => 'Text',
			'schema' => [
				'inner_int' => 23,
				'inner_email' => 'test@example.com',
			],
		];

		$schema = new Schema();
		$schema->add('int', 'int', 'required');
		$schema->add('text', 'text', 'required');
		$schema->add('schema', new SubSchema())->label('Schema');

		$this->assertTrue($schema->validate($testData));
	}

	public function testInvalidDataInSubSchema(): void
	{
		$testData = [
			'int' => 13,
			'schema' => [
				'inner_int' => 23,
				'inner_email' => 'test INVALID example.com',
			],
		];

		$schema = new Schema();
		$schema->add('int', 'int', 'required');
		$schema->add('text', 'text', 'required');
		$schema->add('schema', new SubSchema());

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertCount(2, $errors['errors']);
		$this->assertSame('Required', $errors['map']['text'][0]);
		$this->assertSame('Invalid email address', $errors['map']['schema']['inner_email'][0]);
	}

	public function testListSchema(): void
	{
		$testData = [[
			'int' => 13,
			'text' => 'Text 1',
			'single_schema' => [
				'inner_int' => 23,
				'inner_email' => 'test@example.com',
			],
		], [
			'int' => 17,
			'text' => 'Text 2',
			'single_schema' => [
				'inner_int' => '31',
				'inner_email' => 'example@example.com',
			],
			'list_schema' => [[
				'inner_int' => '43',
				'inner_email' => 'example@example.com',
			], [
				'inner_int' => '47',
				'inner_email' => 'example@example.com',
			]],
		]];

		$schema = new Schema(true);
		$schema->add('int', 'int', 'required');
		$schema->add('text', 'text', 'required');
		$schema->add('single_schema', new SubSchema());
		$schema->add('list_schema', new SubSchema(true));

		$this->assertTrue($schema->validate($testData));
		$values = $schema->values();
		$this->assertSame(13, $values[0]['int']);
		$this->assertSame(23, $values[0]['single_schema']['inner_int']);
		$this->assertNull($values[0]['list_schema']);
		$this->assertSame('Text 2', $values[1]['text']);
		$this->assertSame('example@example.com', $values[1]['single_schema']['inner_email']);
		$this->assertSame('example@example.com', $values[1]['list_schema'][0]['inner_email']);
		$this->assertSame(47, $values[1]['list_schema'][1]['inner_int']);

		$pristineValues = $schema->pristineValues();
		$this->assertSame(13, $pristineValues[0]['int']);
		$this->assertSame(23, $pristineValues[0]['single_schema']['inner_int']);
		$this->assertNull($pristineValues[0]['list_schema']);
		$this->assertSame('Text 2', $pristineValues[1]['text']);
		$this->assertSame('example@example.com', $pristineValues[1]['single_schema']['inner_email']);
		$this->assertSame('example@example.com', $pristineValues[1]['list_schema'][0]['inner_email']);
		$this->assertSame('47', $pristineValues[1]['list_schema'][1]['inner_int']);
	}

	public function testInvalidListSchema(): void
	{
		$testData = $this->getListData();
		$schema = $this->getListSchema();

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertCount(5, $errors);
		$this->assertSame('Required', $errors['map'][0]['text'][0]);
		$this->assertSame('Required', $errors['map'][0]['single_schema']['inner_int'][0]);
		$this->assertSame('Required', $errors['map'][1]['single_schema'][0]);
		$this->assertSame('Required', $errors['map'][1]['text'][0]);
		$this->assertSame('Invalid email address', $errors['map'][1]['email'][0]);
		$this->assertSame('Shorter than the minimum length of 10 characters', $errors['map'][1]['email'][1]);
		$this->assertSame('Invalid email address', $errors['map'][3]['single_schema']['inner_email'][0]);
		$this->assertSame('Invalid number', $errors['map'][3]['list_schema'][0]['inner_int'][0]);
		$this->assertSame('Invalid email address', $errors['map'][3]['list_schema'][2]['inner_email'][0]);
	}

	public function testGroupedErrors(): void
	{
		$testData = $this->getListData();
		$schema = $this->getListSchema();

		$this->assertFalse($schema->validate($testData));
		$groups = $schema->errors(grouped: true)['errors'];
		$this->assertCount(3, $groups);
		$this->assertSame('List Root', $groups[0]['title']);
		$this->assertSame('Invalid email address', $groups[0]['errors'][2]['error']);
		$this->assertSame('List Sub', $groups[1]['title']);
		$this->assertSame('Invalid number', $groups[1]['errors'][0]['error']);
		$this->assertSame('Single Sub', $groups[2]['title']);
		$this->assertSame('Invalid email address', $groups[2]['errors'][1]['error']);
	}

	public function testEmptyFieldName(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('must not be empty');

		$schema = new class (langs: ['de', 'en']) extends Schema {
			protected function rules(): void
			{
				$this->add('', 'Int', 'int');
			}
		};
		$schema->validate([]);
	}

	public function testEmptyArraySkipsValidatorWithSkipNull(): void
	{
		$testData = [
			'items' => [],
		];

		$schema = new Schema();
		// Using 'in' validator which has skipNull=true
		$schema->add('items', 'list', 'in:a,b,c');

		// Empty array should skip the 'in' validator (which has skipNull=true)
		// and not produce an error
		$this->assertTrue($schema->validate($testData));
	}

	public function testEmptyRegexPatternFails(): void
	{
		$testData = [
			'text' => 'test',
		];

		$schema = new Schema();
		// Regex validator without a pattern (just 'regex' with no argument)
		$schema->add('text', 'text', 'regex');

		$this->assertFalse($schema->validate($testData));
		$errors = $schema->errors();
		$this->assertCount(1, $errors['errors']);
		$this->assertSame('Does not match the required pattern', $errors['map']['text'][0]);
	}
}
