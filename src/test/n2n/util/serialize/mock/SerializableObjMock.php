<?php

namespace n2n\util\serialize\mock;

class SerializableObjMock {

	public string $strProp;
	public int $intProp;
	public bool $boolProp;
	public float $floatProp;
	public string|int|bool|float|null $unionProp;

}