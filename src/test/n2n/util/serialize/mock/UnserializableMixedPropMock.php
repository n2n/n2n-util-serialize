<?php

namespace n2n\util\serialize\mock;

final class UnserializableMixedPropMock {

	public ?array $arrayProp;

	function create(): UnserializableMixedPropMock {
		$m = new UnserializableMixedPropMock();
		$m->arrayProp = [];
		return $m;
	}
}