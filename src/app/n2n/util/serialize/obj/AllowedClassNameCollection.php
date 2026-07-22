<?php

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