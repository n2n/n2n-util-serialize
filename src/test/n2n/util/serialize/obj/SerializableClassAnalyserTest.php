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
use n2n\util\serialize\mock\UnserializableSerializableMock;
use n2n\util\serialize\mock\UnserializableIntersectionPropMock;

class SerializableClassAnalyserTest extends TestCase {

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testArrayUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		SerializableClassAnalyser::createFromClass(UnserializableArrayPropMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testSecondLevelUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		SerializableClassAnalyser::createFromClass(UnserializableArrayPropDecoratorMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testMixedUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		SerializableClassAnalyser::createFromClass(UnserializableMixedPropMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testMixedInheritanceUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/mixedProp/');
		SerializableClassAnalyser::createFromClass(UnserializableSubMixedPropMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testMixedTraitUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/UnserializableUseTraitMixedPropMock/');
		$this->expectExceptionMessageMatches('/mixedProp/');
		SerializableClassAnalyser::createFromClass(UnserializableUseTraitMixedPropMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}


	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testMagicMethodUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/__wakeup/');
		SerializableClassAnalyser::createFromClass(UnserializableSubSuperMagicMethodMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testSerializableInterfaceUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/serialize\(\), unserialize\(\)/');
		SerializableClassAnalyser::createFromClass(UnserializableSerializableMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testIntersectionTypeUnserializable(): void {
		$this->expectException(ClassNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/intersectionProp/');
		$this->expectExceptionMessageMatches('/Type is not supported./');
		SerializableClassAnalyser::createFromClass(UnserializableIntersectionPropMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testScalarSerializable(): void {
		$collection = new AllowedClassNameCollection();
		SerializableClassAnalyser::createFromClass(SerializableScalarPropMock::class)
				->determineAllowedClassNames($collection);
		$this->assertSame([SerializableScalarPropMock::class], $collection->toArray());
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function testSecondLevelScalarSerializable(): void {
		$collection = new AllowedClassNameCollection();
		SerializableClassAnalyser::createFromClass(SerializableScalarPropDecoratorMock::class)
				->determineAllowedClassNames($collection);
		$this->assertSame([SerializableScalarPropDecoratorMock::class, SerializableScalarPropMock::class],
				$collection->toArray());
	}
}