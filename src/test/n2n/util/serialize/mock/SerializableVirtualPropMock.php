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

final class SerializableVirtualPropMock {

	private string $prop = 'holeradio';
	private SerializableScalarPropMock $prop2;

	public SerializableScalarPropMock $virtualProp {
		get => SerializableScalarPropMock::create();
	}

	public mixed $virtualProp2 {
		get => $this->prop;
	}

	public UnserializableArrayPropMock $virtualProp3 {
		get {
			$mock = UnserializableArrayPropMock::create();
			$mock->arrayProp = [$this->prop2];
			return $mock;
		}
	}

	public static function create(): SerializableScalarPropMock {
		$m = new SerializableScalarPropMock();
		$m->prop2 = SerializableScalarPropMock::create();
		return $m;
	}
}



