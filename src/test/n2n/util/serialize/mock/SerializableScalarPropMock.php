<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 */
namespace n2n\util\serialize\mock;

final class SerializableScalarPropMock {

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