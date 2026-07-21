<?php

namespace n2n\util\serialize\mock;

final class UnserializableArrayPropDecoratorMock {

	public UnserializableArrayPropMock $arrayPropMock;

	static function create(): UnserializableArrayPropDecoratorMock {
		$m = new UnserializableArrayPropDecoratorMock();
		$m->arrayPropMock = UnserializableArrayPropMock::create();
		return $m;
	}
}