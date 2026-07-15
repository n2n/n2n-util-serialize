<?php

namespace n2n\util\serialize\mock;

class SerializableObjPropHckMock {

	public string|SerializableObjPropMock $objProp;

	static function create(): SerializableObjPropHckMock {
		$m = new SerializableObjPropHckMock();
		$m->objProp = 'hck';
		return $m;
	}
}