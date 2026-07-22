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
namespace n2n\util\serialize\obj;

use n2n\util\serialize\ex\ClassNotSupportedForSerializationException;
use ReflectionClass;
use n2n\util\ex\ExUtils;
use n2n\util\type\TypeUtils;
use n2n\util\type\TypeName;

/**
 * SerializableClassAnalyser provides static factory methods to create instances of serializable objects,
 * ensuring that the target class structure is valid (e.g., constructor arguments are optional).
 */
class SerializableClassAnalyser {

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	function __construct(readonly \ReflectionClass $class, private bool $parentMode) {
		$this->validateClass($class);
	}

	/**
	 * Internal helper method to simulate checking for non-optional constructors.
	 * @throws ClassNotSupportedForSerializationException
	 */
	private function validateClass(\ReflectionClass $class): void {
		if ($class->isInterface() || $class->isTrait()) {
			throw new ClassNotSupportedForSerializationException($class->getName()
					. ' not supported for serialization. Type must be a class.');
		}

		if ($class->isEnum()) {
			throw new ClassNotSupportedForSerializationException($class->getName()
					. ' not supported for serialization. Enums are not supported.');
		}

		if ($class->isAbstract()) {
			throw new ClassNotSupportedForSerializationException($class->getName()
					. ' not supported for serialization. Class must not be abstract.');
		}

		if (!$this->parentMode && !$class->isFinal()) {
			throw new ClassNotSupportedForSerializationException($class->getName()
					. ' not supported for serialization. Class must be final.');
		}

		if ($class->hasMethod('__sleep') || $class->hasMethod('__wakeup')
				|| $class->hasMethod('__serialize') || $class->hasMethod('__unserialize')
				|| $class->hasMethod('serialize') || $class->hasMethod('unserialize')
				|| $class->hasMethod('__destruct')) {
			throw new ClassNotSupportedForSerializationException($class->getName()
					. ' not supported for serialization. Class must not contain any method which could be called '
					. ' during serialization/unserialzation process like:'
					. ' __sleep(), __wakeup(), __serialize(), __unserialize(), serialize(), unserialize(), __destruct());');
		}
	}

	/**
	 * Adds the allowed class names for this class (and transitively for its parent and object-typed properties)
	 * to the given {@see AllowedClassNameCollection}. The collection itself breaks cycles arising from self-
	 * referential or mutually recursive property types (e.g. `public ?Node $parent`).
	 *
	 * @throws ClassNotSupportedForSerializationException
	 */
	function determineAllowedClassNames(AllowedClassNameCollection $collection): void {
		$className = $this->class->getName();
		if ($collection->contains($className)) {
			return;
		}
		$collection->add($className);

		if (false !== ($parentClass = $this->class->getParentClass())) {
			(new SerializableClassAnalyser($parentClass, true))->determineAllowedClassNames($collection);
		}

		foreach ($this->class->getProperties() as $property) {
			$type = $property->getType();

			try {
				if ($type instanceof \ReflectionNamedType) {
					$this->extractAllowedClassNamesFromNamedType($property, $type, $collection);
					continue;
				}
				if ($type instanceof \ReflectionUnionType) {
					foreach ($type->getTypes() as $type) {
						$this->extractAllowedClassNamesFromNamedType($property, $type, $collection);
					}
					continue;
				}

				throw new ClassNotSupportedForSerializationException('Type is not supported.');
			} catch (ClassNotSupportedForSerializationException $e) {
				throw new ClassNotSupportedForSerializationException('Property '
								. TypeUtils::prettyReflPropName($property) . ' not serializable. Reason: ' . $e->getMessage(),
						previous: $e);
			}
		}
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	private function extractAllowedClassNamesFromNamedType(\ReflectionProperty $property, \ReflectionNamedType $type,
			AllowedClassNameCollection $collection): void {
		$typeName = $type->getName();
		if (TypeName::isScalar($typeName) || TypeName::NULL === $typeName) {
			return;
		}

		if ($type->isBuiltin()) {
			throw new ClassNotSupportedForSerializationException('Type is not supported for serialization '
					. $type->getName());
		}

		if (!$collection->contains($typeName)) {
			self::createFromClass($typeName)->determineAllowedClassNames($collection);
		}
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	public static function createFromObj(object $obj): SerializableClassAnalyser {
		return self::createFromClass(new ReflectionClass($obj));
	}

	/**
	 * @throws ClassNotSupportedForSerializationException
	 */
	public static function createFromClass(string|\ReflectionClass $class): SerializableClassAnalyser {
		if (is_string($class)) {
			$class = ExUtils::try(fn () => new ReflectionClass($class));
		}

		return new SerializableClassAnalyser($class, false);
	}


}