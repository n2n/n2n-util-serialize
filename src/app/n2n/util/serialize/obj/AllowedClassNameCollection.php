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
declare(strict_types=1);

namespace n2n\util\serialize\obj;

/**
 * Collects the class names allowed during (un)serialization and acts as the cycle-breaker for
 * self-referential or mutually recursive property types (e.g. `public ?Node $parent`).
 *
 * The same instance is threaded through every {@see SerializableClassAnalyser::determineAllowedClassNames()}
 * call of a traversal: {@see self::contains()} guards against revisiting a class, while {@see self::add()}
 * accumulates the allowed class names in first-visit order.
 */
class AllowedClassNameCollection {

	/**
	 * @var array<string, string> class name as both key (for O(1) lookup / dedup) and value
	 */
	private array $classNames = [];

	function add(string $className): void {
		$this->classNames[$className] = $className;
	}

	function contains(string $className): bool {
		return isset($this->classNames[$className]);
	}

	/**
	 * @return string[]
	 */
	function toArray(): array {
		return array_values($this->classNames);
	}
}