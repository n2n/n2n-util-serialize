<?php

namespace n2n\util\serialize\mock;

final class SerializableTypedArrayPropMock {

	public SerializableScalarPropMock $objProp;

	function create(): SerializableTypedArrayPropMock {
		$m = new SerializableTypedArrayPropMock();
		$m->objProp = SerializableScalarPropMock::create();
		return $m;
	}
}