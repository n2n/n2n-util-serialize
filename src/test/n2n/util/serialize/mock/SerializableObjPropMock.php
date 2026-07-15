<?php

namespace n2n\util\serialize\mock;

class SerializableObjPropMock {

	public SerializableScalarPropMock $objProp;

	function create(): SerializableObjPropMock {
		$m = new SerializableObjPropMock();
		$m->objProp = SerializableScalarPropMock::create();
		return $m;
	}
}