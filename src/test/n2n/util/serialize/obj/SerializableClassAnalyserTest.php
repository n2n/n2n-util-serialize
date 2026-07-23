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
use n2n\util\serialize\ex\TypeNotSupportedForSerializationException;
use n2n\util\serialize\mock\UnserializableArrayPropDecoratorMock;
use n2n\util\serialize\mock\UnserializableMixedPropMock;
use n2n\util\serialize\mock\SerializableScalarPropMock;
use n2n\util\serialize\mock\SerializableScalarPropDecoratorMock;
use n2n\util\serialize\mock\UnserializableSubSuperMagicMethodMock;
use n2n\util\serialize\mock\UnserializableSubMixedPropMock;
use n2n\util\serialize\mock\UnserializableUseTraitMixedPropMock;
use n2n\util\serialize\mock\UnserializableSerializableMock;
use n2n\util\serialize\mock\UnserializableIntersectionPropMock;
use n2n\util\serialize\mock\SerializableEnumPropMock;
use n2n\util\serialize\mock\SerializableEnumMock;
use n2n\util\serialize\mock\SerializableRecursivePropMock;

class SerializableClassAnalyserTest extends TestCase {

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function testArrayUnserializable(): void {
		$this->expectException(TypeNotSupportedForSerializationException::class);
		SerializableClassAnalyser::createFromClass(UnserializableArrayPropMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function testSecondLevelUnserializable(): void {
		$this->expectException(TypeNotSupportedForSerializationException::class);
		SerializableClassAnalyser::createFromClass(UnserializableArrayPropDecoratorMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function testMixedUnserializable(): void {
		$this->expectException(TypeNotSupportedForSerializationException::class);
		SerializableClassAnalyser::createFromClass(UnserializableMixedPropMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function testMixedInheritanceUnserializable(): void {
		$this->expectException(TypeNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/mixedProp/');
		SerializableClassAnalyser::createFromClass(UnserializableSubMixedPropMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function testMixedTraitUnserializable(): void {
		$this->expectException(TypeNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/UnserializableUseTraitMixedPropMock/');
		$this->expectExceptionMessageMatches('/mixedProp/');
		SerializableClassAnalyser::createFromClass(UnserializableUseTraitMixedPropMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}


	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function testMagicMethodUnserializable(): void {
		$this->expectException(TypeNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/__wakeup/');
		SerializableClassAnalyser::createFromClass(UnserializableSubSuperMagicMethodMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function testSerializableInterfaceUnserializable(): void {
		$this->expectException(TypeNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/serialize\(\), unserialize\(\)/');
		SerializableClassAnalyser::createFromClass(UnserializableSerializableMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function testIntersectionTypeUnserializable(): void {
		$this->expectException(TypeNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/intersectionProp/');
		$this->expectExceptionMessageMatches('/Type is not supported./');
		SerializableClassAnalyser::createFromClass(UnserializableIntersectionPropMock::class)
				->determineAllowedClassNames(new AllowedClassNameCollection());
	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function testScalarSerializable(): void {
		$collection = new AllowedClassNameCollection();
		SerializableClassAnalyser::createFromClass(SerializableScalarPropMock::class)
				->determineAllowedClassNames($collection);
		$this->assertSame([SerializableScalarPropMock::class], $collection->toArray());
	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function testSecondLevelScalarSerializable(): void {
		$collection = new AllowedClassNameCollection();
		SerializableClassAnalyser::createFromClass(SerializableScalarPropDecoratorMock::class)
				->determineAllowedClassNames($collection);
		$this->assertSame([SerializableScalarPropDecoratorMock::class, SerializableScalarPropMock::class],
				$collection->toArray());
	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function testEnumSerializable(): void {
		$collection = new AllowedClassNameCollection();
		SerializableClassAnalyser::createFromClass(SerializableEnumMock::class)
				->determineAllowedClassNames($collection);
		$this->assertSame([SerializableEnumMock::class], $collection->toArray());
	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function testEnumPropSerializable(): void {
		$collection = new AllowedClassNameCollection();
		SerializableClassAnalyser::createFromClass(SerializableEnumPropMock::class)
				->determineAllowedClassNames($collection);
		$this->assertSame([SerializableEnumPropMock::class], $collection->toArray());
	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function testRecursivePropSerializable(): void {
		$collection = new AllowedClassNameCollection();
		SerializableClassAnalyser::createFromClass(SerializableRecursivePropMock::class)
				->determineAllowedClassNames($collection);
		$this->assertSame([SerializableRecursivePropMock::class], $collection->toArray());
	}


}