<?php

declare(strict_types=1);

use Duon\Sire\Validator;
use Duon\Sire\Value;

test('Validator validates', function () {
	$validator = new Validator(
		'same',
		'Same',
		function (Value $value, string $compare): bool {
			return $value->value === $compare;
		},
		false,
	);

	$value = new Value('testvalue', 'testvalue');
	expect($validator->validate($value, 'testvalue'))->toBe(true);
	$value = new Value('wrongvalue', 'wrongvalue');
	expect($validator->validate($value, 'testvalue'))->toBe(false);
	$value = new Value(null, null);
	expect($validator->validate($value, 'testvalue'))->toBe(false);
});
