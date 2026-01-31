<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Value;

class ValueTest extends TestCase
{
	public function testPropertiesNumbers(): void
	{
		$value = new Value(1, 2, null);

		$this->assertSame(1, $value->value);
		$this->assertSame(2, $value->pristine);
		$this->assertNull($value->error);
	}

	public function testPropertiesStrings(): void
	{
		$value = new Value('test1', 'test2', 'test3');

		$this->assertSame('test1', $value->value);
		$this->assertSame('test2', $value->pristine);
		$this->assertSame('test3', $value->error);
	}

	public function testPropertiesArrays(): void
	{
		$value = new Value([1, 2, 3], [2, 3, 4], [3, 4, 5]);

		$this->assertSame([1, 2, 3], $value->value);
		$this->assertSame([2, 3, 4], $value->pristine);
		$this->assertSame([3, 4, 5], $value->error);
	}
}
