<?php

namespace n2n\util\serialize;

use PHPUnit\Framework\TestCase;
use n2n\util\serialize\mock\SerializableObjMock;
use n2n\util\serialize\ex\UnserializationFailedException;

class SerializationUtilsTest extends TestCase {

	/**
	 * @throws UnserializationFailedException
	 */
	function testStrictObjSerialize() {
		$m = new SerializableObjMock();
		$m->boolProp = true;
		$m->intProp = 1;
		$m->floatProp = 1.1;
		$m->strProp = 'foo';
		$m->unionProp = null;

		$str = SerializationUtils::strictObjSerialize($m, SerializableObjMock::class);

		$unserializedM = SerializationUtils::strictObjUnserialize($str, SerializableObjMock::class);
		$this->assertEquals($m, $unserializedM);
	}
}