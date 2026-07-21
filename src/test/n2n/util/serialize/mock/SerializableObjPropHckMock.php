<?php

namespace n2n\util\serialize\mock;

final class SerializableObjPropHckMock {

	public string|SerializableObjPropMock $objProp;

	static function create(): SerializableObjPropHckMock {
		$m = new SerializableObjPropHckMock();
		$m->objProp = 'hck';
		return $m;
	}
}