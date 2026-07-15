<?php

namespace n2n\util\serialize;

use PHPUnit\Framework\TestCase;
use n2n\util\serialize\mock\SerializableScalarPropMock;
use n2n\util\serialize\ex\UnserializationFailedException;
use n2n\util\serialize\mock\SerializableObjPropMock;

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

}