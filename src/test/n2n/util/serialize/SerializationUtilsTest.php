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

use PHPUnit\Framework\TestCase;
use n2n\util\serialize\mock\SerializableScalarPropMock;
use n2n\util\serialize\ex\UnserializationFailedException;
use n2n\util\serialize\mock\SerializableObjPropMock;
use n2n\util\serialize\ex\TypeNotSupportedForSerializationException;

class SerializationUtilsTest extends TestCase {



	/**
	 * @throws UnserializationFailedException
	 */
	function testStrictObjSerialize() {
		$m = SerializableScalarPropMock::create();

		$str = SerializationUtils::strictSerialize($m, SerializableScalarPropMock::class);

		$unserializedM = SerializationUtils::strictUnserialize($str, SerializableScalarPropMock::class);
		$this->assertEquals($m, $unserializedM);
	}

	/**
	 * @throws UnserializationFailedException
	 */
	function testStrictObjSerializeWrongType() {
		$m = SerializableScalarPropMock::create();
		$str = SerializationUtils::strictSerialize($m, SerializableScalarPropMock::class);

		$this->expectException(UnserializationFailedException::class);
		$unserializedM = SerializationUtils::strictUnserialize($str, SerializableObjPropMock::class);
	}


	/**
	 * @throws UnserializationFailedException
	 */
	function testStrictObjSerializeConflictingPropType() {
		$str = 'O:47:"n2n\util\serialize\mock\SerializableObjPropMock":1:{s:7:"objProp";s:3:"hck";}';

		$this->expectException(UnserializationFailedException::class);
		$unserializedM = SerializationUtils::strictUnserialize($str, SerializableObjPropMock::class);
	}

	/**
	 * @throws UnserializationFailedException
	 */
	function testStrictObjSerializeScalar() {

		$ser = SerializationUtils::strictSerialize('holeradio', 'string');
		$this->assertSame('holeradio', SerializationUtils::strictUnserialize($ser, 'string'));

		$ser = SerializationUtils::strictSerialize(3, 'int');
		$this->assertSame(3, SerializationUtils::strictUnserialize($ser, 'int'));

		$ser = SerializationUtils::strictSerialize(1.0, 'float');
		$this->assertSame(1.0, SerializationUtils::strictUnserialize($ser, 'float'));
	}

	function testStrictObjSerializeWrongScalarType() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/must be of type int, string given/');
		$ser = SerializationUtils::strictSerialize('holeradio', 'int');
	}

	function testStrictObjSerializeWrongScalar() {
		$this->expectException(TypeNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/Type not supported for serialization: array/');
		$ser = SerializationUtils::checkedStrictSerialize([], 'array');
	}

	/**
	 * @throws UnserializationFailedException
	 */
	function testStrictObjUnserializeWrongScalarType() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/Unserialized string must be of type int/');
		$ser = SerializationUtils::strictSerialize('holeradio', 'string');
		SerializationUtils::strictUnserialize($ser, 'int');
	}

	/**
	 * @throws UnserializationFailedException
	 */
	function testStrictObjUnserializeWrongScalar() {
		$this->expectException(TypeNotSupportedForSerializationException::class);
		$this->expectExceptionMessageMatches('/Type not supported for serialization: array/');
		$ser = SerializationUtils::checkedStrictUnserialize(serialize('[]'), 'array');
	}
}