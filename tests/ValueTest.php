<?php

declare(strict_types=1);

use Duon\Sire\Value;

test('Properties numbers', function () {
	$value = new Value(1, 2, null);

	expect($value->value)->toBe(1);
	expect($value->pristine)->toBe(2);
	expect($value->error)->toBe(null);
});

test('Properties strings', function () {
	$value = new Value('test1', 'test2', 'test3');

	expect($value->value)->toBe('test1');
	expect($value->pristine)->toBe('test2');
	expect($value->error)->toBe('test3');
});

test('Properties arrays', function () {
	$value = new Value([1, 2, 3], [2, 3, 4], [3, 4, 5]);

	expect($value->value)->toBe([1, 2, 3]);
	expect($value->pristine)->toBe([2, 3, 4]);
	expect($value->error)->toBe([3, 4, 5]);
});
