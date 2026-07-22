<?php

namespace n2n\util\serialize;


use n2n\util\serialize\ex\UnserializationFailedException;
use n2n\util\serialize\obj\SerializableClassAnalyser;
use n2n\util\serialize\obj\AllowedClassNameCollection;
use n2n\util\serialize\ex\ClassNotSupportedForSerializationException;
use n2n\util\type\TypeUtils;

class SerializationUtils {

	const SER_FALSE = 'b:0;';

	/**
	 *
	 * @throws UnserializationFailedException
	 */
	public static function unserialize(string $serializedStr, array $options = []): mixed {
		if ($serializedStr == self::SER_FALSE) {
			return false;
		}

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
	 * @param object $obj
	 * @param string|\ReflectionClass $class
	 * @return string|null
	 */
	static function strictObjSerialize(object $obj, string|\ReflectionClass $class): ?string {
		try {
			return self::checkedStrictObjSerialize($obj, $class);
		} catch (ClassNotSupportedForSerializationException $e) {
			throw new \InvalidArgumentException($e->getMessage(), previous: $e);
		}
	}

	/**
	 * @param object $obj
	 * @param string|\ReflectionClass $class
	 * @return string|null
	 * @throws ClassNotSupportedForSerializationException
	 */
	static function checkedStrictObjSerialize(object $obj, string|\ReflectionClass $class): ?string {
		$analyzer = SerializableClassAnalyser::createFromClass($class);
		$analyzer->determineAllowedClassNames(new AllowedClassNameCollection());

		if ($analyzer->class->getName() !== get_class($obj)) {
			throw new \InvalidArgumentException('Passed object must be exact type ' . $class->getName()
					. '. Given: ' . get_class($obj));
		}

		return serialize($obj);
	}

	/**
	 * @template T
	 * @param string $data
	 * @param class-string<T>|\ReflectionClass $class
	 * @return T
	 * @throws UnserializationFailedException
	 */
	static function strictObjUnserialize(string $data, string|\ReflectionClass $class): mixed {
		try {
			return self::checkedStrictObjUnserialize($data, $class);
		} catch (ClassNotSupportedForSerializationException $e) {
			throw new \InvalidArgumentException($e->getMessage(), previous: $e);
		}
	}

	/**
	 * @template T
	 * @param string $data
	 * @param class-string<T>|\ReflectionClass $class
	 * @return T
	 * @throws ClassNotSupportedForSerializationException
	 * @throws UnserializationFailedException
	 */
	private static function checkedStrictObjUnserialize(string $data, string|\ReflectionClass $class): mixed {
		$analyzer = SerializableClassAnalyser::createFromClass($class);
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