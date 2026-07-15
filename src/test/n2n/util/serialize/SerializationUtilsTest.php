<?php

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