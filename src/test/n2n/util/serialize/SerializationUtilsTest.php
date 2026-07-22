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
use n2n\util\serialize\mock\SerializableObjPropHckMock;

class SerializationUtilsTest extends TestCase {



	/**
	 * @throws UnserializationFailedException
	 */
	function testStrictObjSerialize() {
		$m = SerializableScalarPropMock::create();

		$str = SerializationUtils::strictObjSerialize($m, SerializableScalarPropMock::class);

		$unserializedM = SerializationUtils::strictObjUnserialize($str, SerializableScalarPropMock::class);
		$this->assertEquals($m, $unserializedM);
	}

	/**
	 * @throws UnserializationFailedException
	 */
	function testStrictObjSerializeWrongType() {
		$m = SerializableScalarPropMock::create();
		$str = SerializationUtils::strictObjSerialize($m, SerializableScalarPropMock::class);

		$this->expectException(UnserializationFailedException::class);
		$unserializedM = SerializationUtils::strictObjUnserialize($str, SerializableObjPropMock::class);
	}


	/**
	 * @throws UnserializationFailedException
	 */
	function testStrictObjSerializeConflictingPropType() {
		$str = 'O:47:"n2n\util\serialize\mock\SerializableObjPropMock":1:{s:7:"objProp";s:3:"hck";}';

		$this->expectException(UnserializationFailedException::class);
		$unserializedM = SerializationUtils::strictObjUnserialize($str, SerializableObjPropMock::class);
	}

}