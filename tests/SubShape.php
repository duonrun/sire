<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Shape;

class SubShape extends Shape
{
	public function rules(): void
	{
		$this->add('inner_int', 'int', 'required')->label('Int');
		$this->add('inner_email', 'text', 'required', 'email')->label('Email');
	}
}
