<?php

namespace n2n\util\serialize\mock;

class SerializableScalarPropMock {

	public string $strProp;
	public int $intProp;
	public bool $boolProp;
	public float $floatProp;
	public string|int|bool|float|null $unionProp;

	public static function create(): SerializableScalarPropMock {
		$m = new SerializableScalarPropMock();
		$m->boolProp = true;
		$m->intProp = 1;
		$m->floatProp = 1.1;
		$m->strProp = 'foo';
		$m->unionProp = null;
		return $m;
	}

}