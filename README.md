# n2n-util-serialize

Safe serialization of plain data objects for the n2n framework.

PHP's native [`serialize()`](https://www.php.net/manual/en/function.serialize.php) /
[`unserialize()`](https://www.php.net/manual/en/function.unserialize.php) on untrusted input is a
well-known vector for **object-injection / POP-chain attacks**: a crafted payload can instantiate
arbitrary classes and trigger their `__wakeup()`, `__unserialize()`, `__destruct()` (or the
`Serializable::unserialize()` callback) with attacker-controlled data.

This library provides `SerializationUtils::strictObjSerialize()` and
`SerializationUtils::strictObjUnserialize()`, which make round-tripping plain data objects safe by:

1. Validating the structure of the root class — and transitively every class reachable through its
   object-typed properties.
2. Passing the resulting transitive set of safe class names to `unserialize()` as `allowed_classes`,
   so objects of any other class degrade to `__PHP_Incomplete_Class` instead of being instantiated.
3. Letting PHP's typed-property enforcement constrain every property to its declared type.
4. Verifying the returned object is of the exact expected class.


## Installation

```bash
composer require n2n/n2n-util-serialize
```

## What makes a class serializable

A class is accepted by `SerializableClassAnalyser` only if it is:

- a concrete class (not abstract, not an interface, not a trait, not an enum),
- `final` (the root class and every object-typed property type),
- free of any method PHP could invoke during (un)serialization:
  `__sleep`, `__wakeup`, `__serialize`, `__unserialize`, `serialize`, `unserialize`, `__destruct`,
- and every property is typed as a scalar (`int`, `float`, `bool`, `string`), `null`, or an object
  of another accepted class.

Untyped, `mixed`, `array` and intersection property types are rejected. Parent classes are analysed
too (they need not be `final`, but must satisfy the other rules).

```php
<?php
namespace n2n\example;

final class Money {
    public int $amount;
    public string $currency;        // scalar: ok
}

final class Invoice {
    public string $id;
    public Money $total;            // object of a final, safe class: ok
    public ?Invoice $previous;      // nullable self-reference: ok (cycles are handled)
}
```

The following would be **rejected** because they allow arbitrary values to be injected:

```php
final class Bad {
    public array $items;          // array  -> rejected
    public mixed $anything;       // mixed  -> rejected
    public $untyped;               // no type declaration -> rejected
    public Traversable $iter;     // non-final class type -> rejected
}
```

## Basic usage

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

// Serialize (throws InvalidArgumentException if the class is not supported,
// or if $invoice is not of the exact class passed as the second argument).
$serialized = SerializationUtils::strictSerialize($invoice, Invoice::class);

// ... store $serialized wherever you like ...

try {
    $restored = SerializationUtils::strictUnserialize($serialized, Invoice::class);
    // $restored is guaranteed to be an Invoice of the exact expected class.
} catch (UnserializationFailedException $e) {
    // $serialized was malformed, not an object, of the wrong class,
}
```
