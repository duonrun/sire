<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\ValidationResult;
use Duon\Sire\Violation;

class ValidationResultTest extends TestCase
{
	public function testViolationFromArray(): void
	{
		$violation = Violation::fromArray([
			'error' => 'Invalid value',
			'title' => 'Main',
			'level' => 2,
			'item' => 1,
			'field' => 'email',
			'label' => 'Email',
		]);

		$this->assertSame('Invalid value', $violation->error);
		$this->assertSame('Main', $violation->title);
		$this->assertSame(2, $violation->level);
		$this->assertSame(1, $violation->item);
		$this->assertSame('email', $violation->field);
		$this->assertSame('Email', $violation->label);
		$this->assertSame(
			[
				'error' => 'Invalid value',
				'title' => 'Main',
				'level' => 2,
				'item' => 1,
				'field' => 'email',
				'label' => 'Email',
			],
			$violation->toArray(),
		);
	}

	public function testValidationResultErrors(): void
	{
		$result = new ValidationResult(
			false,
			'Main',
			['email' => ['Invalid value']],
			[
				new Violation('Invalid value', 'Main', 1, null, 'email', 'Email'),
				new Violation('Invalid nested', null, 2, null, 'other', 'Other'),
			],
		);

		$this->assertFalse($result->isValid());
		$this->assertCount(2, $result->violations());
		$this->assertSame(['email' => ['Invalid value']], $result->map());

		$errors = $result->errors();
		$this->assertFalse($errors['grouped']);
		$this->assertCount(2, $errors['errors']);

		$grouped = $result->errors(grouped: true);
		$this->assertTrue($grouped['grouped']);
		$this->assertCount(2, $grouped['errors']);
		$this->assertSame('Main', $grouped['errors'][0]['title']);
		$this->assertNull($grouped['errors'][1]['title']);
	}

	public function testValidationResultValid(): void
	{
		$result = new ValidationResult(false, null, [], []);

		$this->assertTrue($result->isValid());
		$this->assertCount(0, $result->violations());
		$this->assertSame([], $result->map());
		$this->assertCount(0, $result->errors()['errors']);
	}
}
