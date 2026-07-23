# n2n-util-serialize

Safe serialization of plain data objects and scalar values for the n2n framework.

PHP's native [`serialize()`](https://www.php.net/manual/en/function.serialize.php) /
[`unserialize()`](https://www.php.net/manual/en/function.unserialize.php) on untrusted input is a
well-known vector for **object-injection / POP-chain attacks**: a crafted payload can instantiate
arbitrary classes and trigger their `__wakeup()`, `__unserialize()`, `__destruct()` (or the
`Serializable::unserialize()` callback) with attacker-controlled data.

This library provides `SerializationUtils::strictSerialize()` and
`SerializationUtils::strictUnserialize()`, which make round-tripping plain data objects — and scalar
values — safe. Each takes a `$typeName` describing the expected type:

- For a **class** type the value uses PHP's native object format, made safe by:
  1. Validating the structure of the root class — and transitively every class reachable through its
     object-typed properties.
  2. Passing the resulting transitive set of safe class names to `unserialize()` as `allowed_classes`,
     so objects of any other class degrade to `__PHP_Incomplete_Class` instead of being instantiated.
  3. Letting PHP's typed-property enforcement constrain every property to its declared type.
  4. Verifying the returned object is of the exact expected class.
- For a **scalar** or `null` type (`int`, `float`, `bool`, `string`, `null`) the value is encoded as
  JSON, which is inherently free of object instantiation.


## Installation

```bash
composer require n2n/n2n-util-serialize
```


## What makes a class serializable

A class is accepted by `SerializableClassAnalyser` only if it is:

- a concrete class (not abstract, not an interface, not a trait) or a PHP 8.1 enum,
- `final` (the root class and every object-typed property type; enums are final by definition),
- free of any method PHP could invoke during (un)serialization:
  `__sleep`, `__wakeup`, `__serialize`, `__unserialize`, `serialize`, `unserialize`, `__destruct`,
  `__set`, `__get`,
- and every property is typed as a scalar (`int`, `float`, `bool`, `string`), `null`, an enum, or an
  object of another accepted class.

Untyped, `mixed`, `array` and intersection property types are rejected. Enums are treated as terminal
leaves — their cases are not recursed into. Parent classes are analysed too (they need not be `final`,
but must satisfy the other rules).

```php
<?php
namespace n2n\example;

enum Color: string {
    case Red = 'red';
    case Blue = 'blue';
}

final class Money {
    public int $amount;
    public string $currency;        // scalar: ok
}

final class Invoice {
    public string $id;
    public Money $total;            // object of a final, safe class: ok
    public ?Invoice $previous;      // nullable self-reference: ok (cycles are handled)
    public Color $color;            // enum: ok (terminal leaf)
}
```

The following would be **rejected** because they allow arbitrary values to be injected:

```php
final class Bad {
    public array $items;          // array  -> rejected
    public mixed $anything;       // mixed  -> rejected
    public $untyped;              // no type declaration -> rejected
    public Traversable $iter;     // non-final class type -> rejected
}
```


## Basic usage

### Objects

```php
use n2n\example\Invoice;
use n2n\example\Money;
use n2n\util\serialize\SerializationUtils;
use n2n\util\serialize\ex\UnserializationFailedException;

$money = new Money();
$money->amount = 4200;
$money->currency = 'CHF';

$invoice = new Invoice();
$invoice->id = 'INV-1';
$invoice->total = $money;
$invoice->previous = null;
$invoice->color = Color::Red;

// Serialize (throws InvalidArgumentException if the class is not supported,
// or if $invoice is not of the exact class passed as the second argument).
$serialized = SerializationUtils::strictSerialize($invoice, Invoice::class);

// ... store $serialized wherever you like ...

try {
    $restored = SerializationUtils::strictUnserialize($serialized, Invoice::class);
    // $restored is guaranteed to be an Invoice of the exact expected class.
} catch (UnserializationFailedException $e) {
    // $serialized was malformed, not an object, or of the wrong class.
}
```

### Scalars

Scalar and `null` values are encoded as JSON and round-trip through the same methods — pass a scalar
type name instead of a class name:

```php
use n2n\util\type\TypeName;
use n2n\util\serialize\SerializationUtils;

$serialized = SerializationUtils::strictSerialize(3.14, TypeName::FLOAT);
$value = SerializationUtils::strictUnserialize($serialized, TypeName::FLOAT);
// $value === 3.14  (float)

SerializationUtils::strictSerialize(1.0, TypeName::FLOAT);   // "1.0" — whole-number floats
                                                              // are preserved via JSON_PRESERVE_ZERO_FRACTION
```

Prefer a concrete scalar type name (`int`, `float`, `bool`, `string`) over the pseudo-types `scalar`
and `numeric`, which are also accepted. A concrete name pins the value to a single kind across the
round trip; `scalar` only guarantees *some* scalar and `numeric` only a numeric result, so a value
stored under one concrete kind may come back as another (e.g. an `int` re-read as a `float`) without
raising an error. Use the pseudo-types only when that looser guarantee is intentional.


## Resource exhaustion

The strict methods defend against code execution and type confusion, not against resource exhaustion:
a crafted payload can still allocate large amounts of memory before a typed-property mismatch rejects
it. Object nesting is capped by PHP 8.4's native `max_depth` `unserialize()` option, which defaults to
`SerializationUtils::DEFAULT_MAX_DEPTH` (50) when not supplied; override it per call via the `max_depth`
option, or process-wide via the `unserialize_max_depth` ini setting. `max_depth` bounds nesting, not
total size — for untrusted data also cap the input length at the call site.