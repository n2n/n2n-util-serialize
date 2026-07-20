<?php

namespace n2n\util\serialize\mock;

class UnserializableArrayPropMock {

	public ?array $arrayProp;

	static function create(): UnserializableArrayPropMock {
		$m = new UnserializableArrayPropMock();
		$m->arrayProp = [];
		return $m;
	}
}