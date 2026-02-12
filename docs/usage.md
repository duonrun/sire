---
title: Usage
---

# Usage

This guide covers the day-to-day Sire API, including schema creation,
validation execution, result handling, nested schemas, and extension points.

## Validate data with a schema

Create a `Schema`, define rules with `add()`, and call `validate()` to get a
`ValidationResult` object.

```php
<?php

use Duon\Sire\Schema;

$schema = new Schema();
$schema->add('email', 'text', 'required', 'email')->label('Email address');
$schema->add('age', 'int', 'min:18');

$result = $schema->validate([
    'email' => 'test@example.com',
    'age' => '21',
]);

if (!$result->isValid()) {
    var_dump($result->errors());
}

var_dump($result->values());
```

## Use built-in types and validators

Sire supports a small set of built-in types and validators out of the box, so
you can start without additional configuration.

- Built-in types: `text`, `int`, `float`, `bool`, `list`
- Built-in validators: `required`, `email`, `minlen`, `maxlen`, `min`, `max`,
  `regex`, `in`

The validator DSL uses `:` to separate the validator name from arguments.

- `required`
- `min:10`
- `email:checkdns`
- `in:active,inactive`

## Use quoted and escaped DSL arguments

You can keep commas and colons inside argument values by quoting or escaping
them.

- Quoted comma values: `in:"ACME, Inc",Globex`
- Escaped comma values: `in:ACME\, Inc,Globex`
- Quoted colon values: `starts_with:"http://"`
- Escaped colon values: `starts_with:http\://`

Sire throws a `ValueError` if a validator definition is malformed, for example
for unclosed quotes or a missing validator name.

## Read validation results

The `ValidationResult` object is the primary output of validation. Use it as
your source of truth in application code.

- `isValid()` returns `true` when no violations exist.
- `violations()` returns typed `Violation` objects.
- `errors()` returns a structured array output.
- `errors(grouped: true)` groups errors by schema section.
- `map()` returns a field-to-messages map.
- `values()` returns cast values.
- `pristineValues()` returns original values before casting.

`ValidationResult` and `Violation` implement `JsonSerializable`, so you can
return them directly from JSON APIs.

## Validate nested objects and lists

You can use another schema as a field type to validate nested structures.
Create a list schema by passing `true` to the constructor.

```php
<?php

use Duon\Sire\Schema;

$address = new Schema();
$address->add('street', 'text', 'required');
$address->add('zip', 'text', 'required', 'minlen:5');

$user = new Schema();
$user->add('name', 'text', 'required');
$user->add('address', $address);

$users = new Schema(true);
$users->add('name', 'text', 'required');
$users->add('address', $address);
```

## Extend validators and type casters

You can provide custom registries when you construct a schema. This is useful
for project-specific rules and casting behavior.

- Use `ValidatorRegistry::withDefaults()->with(...)` to add validators.
- Use `TypeCasterRegistry::withDefaults($messages)->with(...)` to add casters.
- Use a custom `ValidatorDefinitionParser` if you need a different DSL split
  strategy.

## Next steps

Continue with the [development guide](development.md) for local workflows,
tests, and quality checks.
