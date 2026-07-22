<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 */
namespace n2n\util\serialize;


use n2n\util\serialize\ex\UnserializationFailedException;
use n2n\util\serialize\obj\SerializableClassAnalyser;
use n2n\util\serialize\obj\AllowedClassNameCollection;
use n2n\util\serialize\ex\TypeNotSupportedForSerializationException;
use n2n\util\type\TypeUtils;
use n2n\util\type\TypeName;
use n2n\util\StringUtils;
use n2n\util\type\ArgUtils;
use n2n\util\JsonEncodeFailedException;
use n2n\util\JsonDecodeFailedException;

/**
 * Helpers around PHP's native {@see serialize()}/{@see unserialize()} that make (un)serializing plain data
 * objects safe against object-injection / POP-chain attacks.
 *
 * The strict variants {@see self::strictSerialize()} and {@see self::strictUnserialize()} are built
 * around a {@see SerializableClassAnalyser} which validates the structure of the passed root `$class` and
 * transitively of every class reachable through its object-typed properties. A class is considered safe only
 * if it is:
 *  - a concrete (non-abstract, non-interface, non-trait, non-enum) class,
 *  - {@see \ReflectionClass::isFinal()} (the root and every object-typed property type),
 *  - free of any method PHP could invoke during (un)serialization
 *    (`__sleep`, `__wakeup`, `__serialize`, `__unserialize`, `serialize`, `unserialize`, `__destruct`),
 *  - and every property is typed as a scalar (`int`, `float`, `bool`, `string`), `null`, or an object of
 *    another safe class. Untyped, `mixed`, `array` and intersection property types are rejected.
 *
 * On unserialization the analyser's transitive set of safe class names is passed to {@see unserialize()} as
 * `allowed_classes`, so any object of a class outside that set degrades to `__PHP_Incomplete_Class` instead of
 * being instantiated. PHP's own typed-property enforcement then guarantees that each property only receives a
 * value of its declared type. Finally the returned object is verified to be of the exact expected class.
 *
 * Note: this defends against code execution and type confusion, not against resource exhaustion. A crafted
 * payload can still allocate large amounts of memory before a typed-property mismatch rejects it; cap the
 * input size at the call site if the data is untrusted.
 */
class SerializationUtils {

	/**
	 * Native `serialize()` representation of the boolean `false`, used to disambiguate it from the `false`
	 * value {@see unserialize()} returns to signal an error.
	 */
	const SER_FALSE = 'b:0;';

	/**
	 * Default maximum nesting depth enforced by {@see self::strictUnserialize()} (and available to
	 * {@see self::unserialize()} via the `max_depth` option). PHP's native {@see unserialize()} has no depth
	 * limit, so a crafted payload with extreme `a:`/`O:` nesting can exhaust memory or segfault the C stack
	 * before a typed-property mismatch rejects it. 50 is generous for ordinary data-object graphs (whose depth
	 * is just object-property nesting) while firmly bounding such depth bombs; tune per call via `max_depth`.
	 */
	const DEFAULT_MAX_DEPTH = 50;

	/**
	 * Thin, safer wrapper around PHP's native {@see unserialize()}.
	 *
	 * Unlike the native function — which returns `false` both for the serialized boolean `false` and on error,
	 * and only emits a notice/warning on failure — this method resolves that ambiguity via {@see self::SER_FALSE}
	 * and converts every error and thrown {@see \Throwable} into an {@see UnserializationFailedException}, so a
	 * caller never has to inspect {@see error_get_last()}.
	 *
	 * No `allowed_classes` restriction is applied unless one is supplied through `$options`; for unserializing
	 * untrusted data prefer {@see self::strictUnserialize()}.
	 *
	 * Recognized `$options` keys:
	 *  - `allowed_classes` (bool|string[]): forwarded to {@see unserialize()}.
	 *  - `max_depth` (int): if set, the payload is pre-scanned and rejected when its container nesting
	 *    (`a:`/`O:`/`C:`) exceeds this depth. The scan uses the length-prefixed string format to skip string
	 *    bodies, so braces or `";` sequences inside strings are never misread as nesting (no false positives).
	 *    Malformed payloads are left for native {@see unserialize()} to reject authoritatively.
	 *
	 * Note: `max_depth` bounds nesting, not total size; a huge flat `s:` string still allocates. For untrusted
	 * data, also cap the input length at the call site.
	 *
	 * @param string $serializedStr the serialized string
	 * @param array $options options forwarded to {@see unserialize()} (`allowed_classes`) plus `max_depth`
	 * @return mixed the unserialized value, or `false` if `$serializedStr` is the serialized boolean `false`
	 *
	 * @throws UnserializationFailedException if `$serializedStr` is not a valid serialized value or exceeds
	 *         the `max_depth` option
	 */
	public static function unserialize(string $serializedStr, array $options = []): mixed {
		if ($serializedStr == self::SER_FALSE) {
			return false;
		}

		$options['max_depth'] ??= self::DEFAULT_MAX_DEPTH;

		try {
			$obj = unserialize($serializedStr, $options);
		} catch (\Throwable $e) {
			throw new UnserializationFailedException($e->getMessage(), previous: $e);
		}

		if ($obj === false && $err = error_get_last()) {
			throw new UnserializationFailedException($err['message']);
		}

		return $obj;
	}

	/**
	 * Serializes `$obj` only if its class — and transitively every class reachable through its object-typed
	 * properties — is supported for (un)serialization according to {@see SerializableClassAnalyser} (see the
	 * class docs for the rules). This guarantees the result can later be fed back to
	 * {@see self::strictUnserialize()} with the same `$class` without enabling object-injection attacks.
	 *
	 * `$obj` must be of the exact class described by `$class` (subclasses are not accepted); otherwise an
	 * {@see \InvalidArgumentException} is thrown.
	 *
	 * @param mixed $data the object to serialize
	 * @param class-string|\ReflectionClass $typeName the root class describing the expected type
	 * @return string the serialized string, or `null` if {@see serialize()} produces no output
	 *
	 * @throws \InvalidArgumentException if `$class` (or any reachable property type) is not supported for
	 *         serialization, or if `$obj` is not of the exact class described by `$class`
	 */
	static function strictSerialize(mixed $data, string $typeName): string {
		try {
			return self::checkedStrictSerialize($data, $typeName);
		} catch (TypeNotSupportedForSerializationException $e) {
			throw new \InvalidArgumentException($e->getMessage(), previous: $e);
		}
	}

	/**
	 * Same as {@see self::strictSerialize()} but throws checked exception
	 * {@see TypeNotSupportedForSerializationException} instead of wrapping it in an
	 * {@see \InvalidArgumentException}.
	 *
	 * @template T
	 * @param T $data the object to serialize
	 * @param class-string<T> $typeName the root class describing the expected type
	 * @return string the serialized string, or `null` if {@see serialize()} produces no output
	 *
	 * @throws TypeNotSupportedForSerializationException if `$class` (or any reachable property type) is not
	 *         supported for serialization
	 * @throws \InvalidArgumentException if `$obj` is not of the exact class described by `$class`
	 */
	static function checkedStrictSerialize(mixed $data, string $typeName): string {
		if (TypeName::isScalar($typeName) || TypeName::NULL === $typeName) {
			ArgUtils::valType($data, $typeName);
			try {
				return StringUtils::jsonEncode($data, JSON_PRESERVE_ZERO_FRACTION);
			} catch (JsonEncodeFailedException $e) {
				throw new \InvalidArgumentException($e->getMessage(), previous: $e);
			}
		}

		if (TypeName::isBuiltin($typeName)) {
			throw new TypeNotSupportedForSerializationException('Type not supported for serialization: ' .
					$typeName);
		}

		$analyzer = SerializableClassAnalyser::createFromClass($typeName);
		$analyzer->determineAllowedClassNames(new AllowedClassNameCollection());

		if ($analyzer->class->getName() !== get_class($data)) {
			throw new \InvalidArgumentException('Passed object must be exact type ' . $typeName
					. '. Given: ' . get_class($data));
		}

		return serialize($data);
	}

	/**
	 * Safely unserializes `$data` into an object of the exact class described by `$class`.
	 *
	 * The transitive set of class names allowed for `$class` (determined by {@see SerializableClassAnalyser}) is
	 * passed to {@see unserialize()} as `allowed_classes`, so objects of any other class in the payload degrade
	 * to `__PHP_Incomplete_Class` and PHP's typed-property enforcement constrains every property to its declared
	 * type. The returned value is then verified to be an object of exactly `$class`.
	 *
	 * This is the counterpart to {@see self::strictSerialize()}: a string produced by `strictObjSerialize()`
	 * with a given `$class` round-trips back through `strictObjUnserialize()` with the same `$class`.
	 *
	 * @template T
	 * @param string $data the serialized string, typically produced by {@see self::strictSerialize()}
	 * @param class-string<T> $typeName the root class describing the expected type
	 * @return T the unserialized object of class `$class`
	 *
	 * @throws \InvalidArgumentException if `$class` (or any reachable property type) is not supported for
	 *         serialization
	 * @throws UnserializationFailedException if `$data` is not a valid serialized value, does not represent an
	 *         object, or represents an object of a class other than `$class`
	 */
	static function strictUnserialize(string $data, string $typeName): mixed {
		try {
			return self::checkedStrictUnserialize($data, $typeName);
		} catch (TypeNotSupportedForSerializationException $e) {
			throw new \InvalidArgumentException($e->getMessage(), previous: $e);
		}
	}

	/**
	 *  Same as {@see self::strictUnserialize()} but throws checked exception
	 *  {@see TypeNotSupportedForSerializationException} instead of wrapping it in an
	 *  {@see \InvalidArgumentException}.
	 *
	 * @template T
	 * @param string $data the serialized string
	 * @param class-string<T> $typeName the root class describing the expected type
	 * @return T the unserialized object of class `$class`
	 *
	 * @throws TypeNotSupportedForSerializationException if `$class` (or any reachable property type) is not
	 *         supported for serialization
	 * @throws UnserializationFailedException if `$data` is not a valid serialized value, does not represent an
	 *         object, or represents an object of a class other than `$class`
	 */
	static function checkedStrictUnserialize(string $data, string $typeName): mixed {
		if (TypeName::isScalar($typeName) || TypeName::NULL === $typeName) {
			try {
				$result = StringUtils::jsonDecode($data);
			} catch (JsonDecodeFailedException $e) {
				throw new \InvalidArgumentException($e->getMessage(), previous: $e);
			}

			if (!TypeName::isValueA($result, $typeName)) {
				throw new \InvalidArgumentException('Unserialized string must be of type ' . $typeName
						. '. Given: ' . TypeUtils::getTypeInfo($result));
			}

			return $result;
		}

		if (TypeName::isBuiltin($typeName)) {
			throw new TypeNotSupportedForSerializationException('Type not supported for serialization: ' .
					$typeName);
		}

		$analyzer = SerializableClassAnalyser::createFromClass($typeName);
		$allowedClassNameCollection = new AllowedClassNameCollection();
		$analyzer->determineAllowedClassNames($allowedClassNameCollection);
		$obj = self::unserialize($data, ['allowed_classes' => $allowedClassNameCollection->toArray()]);

		if (!is_object($obj) || $analyzer->class->getName() !== get_class($obj)) {
			throw new UnserializationFailedException('Serialized string is not of exact type '
					. $analyzer->class->getName() . '. ' . TypeUtils::getTypeInfo($obj) . ' returned.');
		}

		return $obj;
	}


}