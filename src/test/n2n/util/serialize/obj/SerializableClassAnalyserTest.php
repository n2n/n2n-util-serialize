<?php

namespace n2n\util\serialize\obj;

use PHPUnit\Framework\TestCase;
use n2n\util\serialize\mock\UnserializableArrayPropMock;
use n2n\util\serialize\ex\ClassNotSupportedForSerializationException;
use n2n\util\serialize\mock\UnserializableArrayPropDecoratorMock;
use n2n\util\serialize\mock\UnserializableMixedPropMock;
use n2n\util\serialize\mock\SerializableScalarPropMock;
use n2n\util\serialize\mock\SerializableScalarPropDecoratorMock;
use n2n\util\serialize\mock\UnserializableSubSuperMagicMethodMock;
use n2n\util\serialize\mock\UnserializableSubMixedPropMock;
use n2n\util\serialize\mock\UnserializableUseTraitMixedPropMock;

class SerializableClassAnalyserTest extends TestCase {

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testArrayUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		SerializableClassAnalyser::createFromClass(UnserializableArrayPropMock::class)
				->determineAllowedClassNames();
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testSecondLevelUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		SerializableClassAnalyser::createFromClass(UnserializableArrayPropDecoratorMock::class)
				->determineAllowedClassNames();
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testMixedUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		SerializableClassAnalyser::createFromClass(UnserializableMixedPropMock::class)
				->determineAllowedClassNames();
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testMixedInheritanceUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/mixedProp/');
		SerializableClassAnalyser::createFromClass(UnserializableSubMixedPropMock::class)
				->determineAllowedClassNames();
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testMixedTraitUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/UnserializableUseTraitMixedPropMock/');
		$this->expectExceptionMessageMatches('/mixedProp/');
		SerializableClassAnalyser::createFromClass(UnserializableUseTraitMixedPropMock::class)
				->determineAllowedClassNames();
	}


	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testMagicMethodUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/__wakeup/');
		SerializableClassAnalyser::createFromClass(UnserializableSubSuperMagicMethodMock::class)
				->determineAllowedClassNames();
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testScalarSerializable(): void {
		$types = SerializableClassAnalyser::createFromClass(SerializableScalarPropMock::class)
				->determineAllowedClassNames();
		$this->assertSame([SerializableScalarPropMock::class], $types);
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testSecondLevelScalarSerializable(): void {
		$types = SerializableClassAnalyser::createFromClass(SerializableScalarPropDecoratorMock::class)
				->determineAllowedClassNames();
		$this->assertSame([SerializableScalarPropDecoratorMock::class, SerializableScalarPropMock::class], $types);
	}
}