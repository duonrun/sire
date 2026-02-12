<?php

declare(strict_types=1);

namespace Duon\Sire;

use Duon\Sire\Validator;
use Duon\Sire\Value;
use Override;
use ValueError;

/**
 * @psalm-api
 */
class Schema implements SchemaInterface
{
	public array $errorList = [];  // A list of errors to be displayed in frontend
	protected array $validators = [];
	protected int $level = 0;
	/** @var array<string, Rule> */
	protected array $rules = [];
	protected array $errorMap = [];     // A dictonary of errorList with the fieldname as key
	protected ?array $cachedValues = null;
	protected ?array $validatedValues = null;
	protected ?array $cachedPristine = null;
	protected array $messages = [];

	public function __construct(
		protected bool $list = false,
		protected bool $keepUnknown = false,
		protected array $langs = [],
		protected ?string $title = null,
	) {
		$this->loadMessages();
		$this->loadDefaultValidators();
	}

	public function add(
		string $field,
		string|SchemaInterface $type,
		string ...$validators,
	): Rule {
		if (!$field) {
			throw new ValueError(
				'Schema definition error: field must not be empty',
			);
		}

		$rule = new Rule($field, $type, $validators);

		$this->rules[$field] = $rule;

		return $rule;
	}

	#[Override]
	public function validate(array $data, int $level = 1): bool
	{
		$this->level = $level;
		$this->errorList = [];
		$this->errorMap = [];
		$this->cachedValues = null;
		$this->cachedPristine = null;

		$this->rules();

		$values = $this->readValues($data);

		if ($this->list) {
			$this->validatedValues = [];

			foreach ($values as $listIndex => $subValues) {
				// add an empty array for this item which will be
				// filled in case of error. Allows to show errors
				// next to the field in frontend (still TODO)
				if (!isset($this->errorMap[$listIndex])) {
					$this->errorMap[$listIndex] = [];
				}

				$this->validatedValues[] = $this->validateItem(
					$subValues,
					$listIndex,
				);
			}
		} else {
			$this->validatedValues = $this->validateItem($values);
		}

		if (count($this->errorList) === 0) {
			$this->review();
		}

		return count($this->errorList) === 0;
	}

	public function isAssoc(array $arr): bool
	{
		if ([] === $arr) {
			return false;
		}

		return array_keys($arr) !== range(0, count($arr) - 1);
	}

	#[Override]
	public function errors(bool $grouped = false): array
	{
		$result = [
			'isList' => $this->list,
			'title' => $this->title,
			'map' => $this->errorMap,
			'grouped' => $grouped,
		];

		if ($grouped) {
			$result['errors'] = $this->groupErrors($this->errorList);
		} else {
			$result['errors'] = array_values($this->errorList);
		}

		return $result;
	}

	#[Override]
	public function values(): array
	{
		if ($this->cachedValues === null) {
			if ($this->list) {
				$this->cachedValues = [];

				foreach ($this->validatedValues ?? [] as $values) {
					$this->cachedValues[] = $this->getValues($values);
				}
			} else {
				$this->cachedValues = $this->getValues($this->validatedValues ?? []);
			}
		}

		return $this->cachedValues;
	}

	#[Override]
	public function pristineValues(): array
	{
		if ($this->cachedPristine === null) {
			if ($this->list) {
				$this->cachedPristine = [];

				foreach ($this->validatedValues ?? [] as $values) {
					$this->cachedPristine[] = array_map(
						function (Value $item): mixed {
							return $item->pristine;
						},
						$values,
					);
				}
			} else {
				$this->cachedPristine = array_map(
					function (Value $item): mixed {
						return $item->pristine;
					},
					$this->validatedValues ?? [],
				);
			}
		}

		return $this->cachedPristine;
	}

	/**
	 * This method is called before validation starts.
	 *
	 * It can be overwritten to add rules in a reusable schema
	 */
	protected function rules(): void
	{
		// Like:
		// $this->add('field', 'bool, 'required')->label('remember');
	}

	protected function addSubError(
		string $field,
		array|string|null $error,
		?int $listIndex,
	): void {
		foreach ($error['errors'] ?? [] as $err) {
			$this->errorList[] = $err;
		}

		if ($listIndex === null) {
			$this->errorMap[$field] = $error['map'] ?? [];
		} else {
			$this->errorMap[$listIndex][$field] = $error['map'] ?? [];
		}
	}

	protected function addError(
		string $field,
		string $label,
		array|string|null $error,
		?int $listIndex = null,
	): void {
		$e = [
			'error' => $error,
			'title' => $this->title,
			'level' => $this->level,
			'item' => null,
			'field' => $field,
			'label' => $label,
		];

		if ($listIndex === null) {
			if (!isset($this->errorMap[$field])) {
				$this->errorMap[$field] = [];
			}

			$this->errorMap[$field][] = $error;
		} else {
			$e['item'] = $listIndex;

			if (!isset($this->errorMap[$listIndex][$field])) {
				$this->errorMap[$listIndex][$field] = [];
			}

			$this->errorMap[$listIndex][$field][] = $error;
		}

		$this->errorList[] = $e;
	}

	protected function validateField(
		string $field,
		Value $value,
		string $validatorDefinition,
		?int $listIndex,
	): void {
		$validatorArray = explode(':', $validatorDefinition);
		$validatorName = $validatorArray[0];
		$validatorArgs = array_slice($validatorArray, 1);

		$validator = $this->validators[$validatorName];

		if (is_array($value->value)) {
			if (empty($value->value) && $validator->skipNull) {
				return;
			}
		} else {
			if (
				empty($value->value)
				&& strlen((string) $value->value) === 0 && $validator->skipNull
			) {
				return;
			}
		}

		if (!$validator->validate($value, ...$validatorArgs)) {
			$this->addError(
				$field,
				$this->rules[$field]->name(),
				sprintf(
					$validator->message,
					$this->rules[$field]->name(),
					$field,
					print_r($value->pristine, true),
					...$validatorArgs,
				),
				$listIndex,
			);
		}
	}

	protected function toBool(mixed $pristine, string $label): Value
	{
		if (is_bool($pristine)) {
			return new Value($pristine, $pristine);
		}

		if (!$pristine) {
			return new Value(false, $pristine);
		}

		$tmp = strtolower((string) $pristine);

		if (in_array($tmp, ['1', 'on', 'true', 'yes'])) {
			return new Value(true, $pristine);
		}

		if (in_array($tmp, ['0', 'off', 'false', 'no', 'null'])) {
			return new Value(false, $pristine);
		}

		return new Value(
			$pristine,
			$pristine,
			sprintf($this->messages['bool'], $label),
		);
	}

	protected function toText(mixed $pristine): Value
	{
		if (empty($pristine)) {
			return new Value(null, $pristine);
		}

		return new Value((string) $pristine, $pristine);
	}

	protected function toList(mixed $pristine, string $label): Value
	{
		if (is_array($pristine) && !$this->isAssoc($pristine)) {
			return new Value($pristine, $pristine);
		}

		return new Value(
			$pristine,
			$pristine,
			sprintf($this->messages['list'], $label),
		);
	}

	protected function toFloat(mixed $pristine, string $label): Value
	{
		if (is_float($pristine) || is_null($pristine)) {
			return new Value($pristine, $pristine);
		}

		if (is_int($pristine)) {
			return new Value((float) $pristine, $pristine);
		}

		$tmp = trim((string) $pristine);

		if (preg_match('/^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$/', $tmp)) {
			return new Value((float) $tmp, $pristine);
		}

		return new Value(
			$pristine,
			$pristine,
			sprintf($this->messages['float'], $label),
		);
	}

	protected function toInt(mixed $pristine, string $label): Value
	{
		if (is_int($pristine) || is_null($pristine)) {
			return new Value($pristine, $pristine);
		}

		if (preg_match('/^([0-9]|-[1-9]|-?[1-9][0-9]*)$/i', trim($pristine))) {
			return new Value((int) $pristine, $pristine);
		}

		return new Value(
			$pristine,
			$pristine,
			sprintf($this->messages['int'], $label),
		);
	}

	protected function toSubValues(mixed $pristine, SchemaInterface $schema): Value
	{
		if ($schema->validate($pristine, $this->level + 1)) {
			return new Value($schema->values(), $pristine);
		}

		return new Value($pristine, $pristine, $schema->errors());
	}

	protected function readFromData(array $data, ?int $listIndex = null): array
	{
		$values = [];

		foreach ($data as $field => $value) {
			$rule = $this->rules[$field] ?? null;

			if ($rule) {
				$label = $rule->name();

				// if (is_string($rule->type)) {
				// $typeArray = explode(':', $rule->type);
				// $typeName = $typeArray[0];
				// } else {
				// $typeName = 'schema';
				// }

				switch ($rule->type()) {
					case 'text':
						$valObj = $this->toText($value);
						break;
					case 'int':
						$valObj = $this->toInt($value, $label);
						break;
					case 'bool':
						$valObj = $this->toBool($value, $label);
						break;
					case 'float':
						$valObj = $this->toFloat($value, $label);
						break;
					case 'list':
						$valObj = $this->toList($value, $label);
						break;
					case 'schema':
						$schema = $rule->type;
						assert($schema instanceof SchemaInterface);
						$valObj = $this->toSubValues($value, $schema);
						break;
					default:
						throw new ValueError('Wrong schema type');
				}

				if ($valObj->error !== null) {
					if ($rule->type() === 'schema') {
						$this->addSubError($field, $valObj->error, $listIndex);
					} else {
						$this->addError(
							$field,
							$this->rules[$field]->name(),
							$valObj->error,
							$listIndex,
						);
					}
				}

				$values[$field] = $valObj;
			} else {
				if ($this->keepUnknown) {
					$values[$field] = new Value($value, $value);
				}
			}
		}

		return $values;
	}

	protected function fillMissingFromRules(array $values): array
	{
		foreach ($this->rules as $field => $rule) {
			if (!isset($values[$field])) {
				if ($rule->type() == 'bool') {
					$values[$field] = new Value(false, null);

					continue;
				}

				$values[$field] = new Value(null, null);
			}
		}

		return $values;
	}

	protected function readValues(array $data): array
	{
		if ($this->list) {
			$values = [];

			foreach ($data as $listIndex => $item) {
				$subValues = $this->readFromData($item, $listIndex);
				$values[] = $this->fillMissingFromRules($subValues);
			}

			return $values;
		}
		$values = $this->readFromData($data);

		return $this->fillMissingFromRules($values);
	}

	protected function validateItem(array $values, ?int $listIndex = null): array
	{
		foreach ($this->rules as $field => $rule) {
			foreach ($rule->validators as $validator) {
				$this->validateField(
					$field,
					$values[$field],
					$validator,
					$listIndex,
				);
			}
		}

		return $values;
	}

	protected function review(): void
	{
		// Can be overwritten in subclasses to make additional checks
		//
		// Implementations should call $this->addError('field_name', 'label', 'Error message');
		// in case of error.
	}

	/** @param array<int, array> $data */
	protected function groupBy(array $data, mixed $key): array
	{
		$result = [];

		foreach ($data as $val) {
			$result[$val[$key]][] = $val;
		}

		return $result;
	}

	/**
	 * Groups errors by schema and sub schema.
	 *
	 * Example:
	 *    [
	 *        [
	 *            'title': 'Main Schema',
	 *            'errors': [
	 *                [
	 *                   'error': 'First Error',
	 *                   ...
	 *                ], [
	 *                   ...
	 *                ]
	 *            ]
	 *        ], [
	 *           'title': 'First Sub Schema',
	 *           ....
	 *        ]
	 *    ]
	 */
	protected function groupErrors(array $errors): array
	{
		$sections = [];

		foreach ($errors as $error) {
			$item = ['title' => $error['title'], 'level' => (string) $error['level']];

			if (in_array($item, $sections)) {
				continue;
			}

			$sections[] = $item;
		}

		usort($sections, function ($a, $b) {
			$aa = $a['level'] . $a['title'];
			$bb = $b['level'] . $b['title'];

			return $aa > $bb ? 1 : -1;
		});

		$groups = $this->groupBy(array_values($errors), 'title');
		$result = [];

		foreach ($sections as $section) {
			$result[] = [
				'title' => $section['title'],
				'errors' => $groups[$section['title']],
			];
		}

		return $result;
	}

	protected function getValues(array $values): array
	{
		return array_map(
			function (Value $item): mixed {
				return $item->value;
			},
			$values,
		);
	}

	protected function loadMessages(): void
	{
		// You can use the following placeholder to get more
		// information into your error messages:
		//
		//     %1$s for the field label if set, otherwise the field name
		//     %2$s for the field name
		//     %3$s for the original value
		//     %4$s for the first validator parameter
		//     %5$s for the next validator parameter
		//     %6$s for the next validator and so on
		//
		//  e. g. 'int' => 'Invalid number "%3$1" in field "%1$s"'

		$this->messages = [
			// Types:
			'bool' => 'Invalid boolean',
			'float' => 'Invalid number',
			'int' => 'Invalid number',
			'list' => 'Invalid list',

			// Validators:
			'required' => 'Required',
			'email' => 'Invalid email address',
			'minlen' => 'Shorter than the minimum length of %4$s characters',
			'maxlen' => 'Exeeds the maximum length of %4$s characters',
			'min' => 'Lower than the required minimum of %4$s',
			'max' => 'Higher than the allowed maximum of %4$s',
			'regex' => 'Does not match the required pattern',
			'in' => 'Invalid value',
		];
	}

	protected function loadDefaultValidators(): void
	{
		foreach ($this->defaultValidatorDefinitions() as $name => $definition) {
			$this->registerValidator(
				$name,
				$definition['validator'],
				$definition['skipNull'],
			);
		}
	}

	/** @return array<string, array{validator: \Closure, skipNull: bool}> */
	protected function defaultValidatorDefinitions(): array
	{
		return [
			'required' => [
				/** @psalm-suppress UnusedClosureParam */
				'validator' => function (Value $value, string ...$args) {
					$val = $value->value;

					if (is_null($val)) {
						return false;
					}

					if (is_array($val) && count($val) === 0) {
						return false;
					}

					return true;
				},
				'skipNull' => false,
			],
			'email' => [
				'validator' => function (Value $value, string ...$args) {
					$email = filter_var(
						trim((string) $value->value),
						\FILTER_VALIDATE_EMAIL,
					);

					if ($email !== false && ($args[0] ?? null) === 'checkdns') {
						[, $mailDomain] = explode('@', $email);

						return checkdnsrr($mailDomain, 'MX');
					}

					return $email !== false;
				},
				'skipNull' => true,
			],
			'minlen' => [
				'validator' => function (Value $value, string ...$args) {
					return strlen($value->value) >= (int) $args[0];
				},
				'skipNull' => true,
			],
			'maxlen' => [
				'validator' => function (Value $value, string ...$args) {
					return strlen($value->value) <= (int) $args[0];
				},
				'skipNull' => true,
			],
			'min' => [
				'validator' => function (Value $value, string ...$args) {
					return (float) $value->value >= (float) $args[0];
				},
				'skipNull' => true,
			],
			'max' => [
				'validator' => function (Value $value, string ...$args) {
					return $value->value <= (float) $args[0];
				},
				'skipNull' => true,
			],
			'regex' => [
				'validator' => function (Value $value, string ...$args) {
					// As regex patterns could contain colons ':' and validator
					// args are separated by colons and split at their position
					// we need to join them again
					$pattern = implode(':', $args);

					if ($pattern === '') {
						return false;
					}

					return preg_match($pattern, $value->value) === 1;
				},
				'skipNull' => true,
			],
			'in' => [
				'validator' => function (Value $value, string ...$args) {
					// Allowed values must be passed as validator arg
					// seperated by comma.
					// Like: in:firstval,secondval,thirdval
					$allowed = explode(',', $args[0]);

					return in_array($value->value, $allowed);
				},
				'skipNull' => true,
			],
		];
	}

	protected function registerValidator(
		string $name,
		\Closure $validator,
		bool $skipNull,
	): void {
		$this->validators[$name] = new Validator(
			$name,
			$this->messages[$name],
			$validator,
			$skipNull,
		);
	}
}
