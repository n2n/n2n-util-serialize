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

use n2n\util\serialize\ex\TypeNotSupportedForSerializationException;
use ReflectionClass;
use n2n\util\ex\ExUtils;
use n2n\util\type\TypeUtils;
use n2n\util\type\TypeName;
use n2n\util\col\TypedArray;
use n2n\util\ex\IllegalStateException;
use n2n\util\col\CollectionTypeUtils;

/**
 * SerializableClassAnalyser provides static factory methods to create instances of serializable objects,
 * ensuring that the target class structure is valid (e.g., constructor arguments are optional).
 */
class SerializableClassAnalyser {
	private bool $enum;
	private bool $customSerializationMode;

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	function __construct(readonly \ReflectionClass $class, private bool $parentMode) {
		$this->enum = $class->isEnum();
		$this->customSerializationMode = $this->detectValidCustomSerializationMode();
		$this->validateClass($class);
	}

	/**
	 * Internal helper method to simulate checking for non-optional constructors.
	 * @throws TypeNotSupportedForSerializationException
	 */
	private function validateClass(\ReflectionClass $class): void {
		if ($class->isInterface() || $class->isTrait()) {
			throw new TypeNotSupportedForSerializationException($class->getName()
					. ' not supported for serialization. Type must be a class.');
		}

		if (!$this->parentMode && $class->isAbstract()) {
			throw new TypeNotSupportedForSerializationException($class->getName()
					. ' not supported for serialization. Class must not be abstract.');
		}

		if (!$this->parentMode && !$class->isFinal()) {
			throw new TypeNotSupportedForSerializationException($class->getName()
					. ' not supported for serialization. Class must be final.');
		}

		$disallowedMethodNames = ['__sleep', '__wakeup', 'serialize', 'unserialize', '__destruct', '__set', '__get'];
		if (!$this->customSerializationMode) {
			$disallowedMethodNames[] = '__serialize';
			$disallowedMethodNames[] = '__unserialize';
		}

		foreach ($disallowedMethodNames as $disallowedMethodName) {
			if (!$class->hasMethod($disallowedMethodName)
					|| $class->getMethod($disallowedMethodName)->getDeclaringClass()->getName() !== $class->getName()) {
				continue;
			}

			throw new TypeNotSupportedForSerializationException($class->getName()
					. ' not supported for serialization. Class must not contain any method which could affect the'
					. ' serialization/unserialzation process like:'
					. implode(', ', array_map(fn (string $m) => $m . '()', $disallowedMethodNames)));
		}
	}

	/**
	 * Adds the allowed class names for this class (and transitively for its parent and object-typed properties)
	 * to the given {@see AllowedClassNameCollection}. The collection itself breaks cycles arising from self-
	 * referential or mutually recursive property types (e.g. `public ?Node $parent`).
	 *
	 * @throws TypeNotSupportedForSerializationException
	 */
	function determineAllowedClassNames(AllowedClassNameCollection $collection): void {
		$className = $this->class->getName();
		if ($collection->contains($className)) {
			return;
		}
		$collection->add($className);

		if ($this->enum) {
			return;
		}

		if ($this->customSerializationMode) {
			$this->extractAllowedClassNamesForCustomSerialization($collection);
			return;
		}

		if (false !== ($parentClass = $this->class->getParentClass())) {
			(new SerializableClassAnalyser($parentClass, true))
					->determineAllowedClassNames($collection);
		}

		foreach ($this->class->getProperties() as $property) {
			$type = $property->getType();

			try {
				if ($type instanceof \ReflectionNamedType) {
					$this->extractAllowedClassNamesFromTypeName($type->getName(), $collection);
					continue;
				}
				if ($type instanceof \ReflectionUnionType) {
					foreach ($type->getTypes() as $type) {
						$this->extractAllowedClassNamesFromTypeName($type->getName(), $collection);
					}
					continue;
				}

				throw new TypeNotSupportedForSerializationException('Type is not supported.');
			} catch (TypeNotSupportedForSerializationException $e) {
				throw new TypeNotSupportedForSerializationException('Property '
								. TypeUtils::prettyReflPropName($property) . ' not serializable. Reason: ' . $e->getMessage(),
						previous: $e);
			}
		}
	}

//	/**
//	 * @throws TypeNotSupportedForSerializationException
//	 */
//	private function extractAllowedClassNamesFromNamedType(\ReflectionProperty $property, \ReflectionNamedType $type,
//			AllowedClassNameCollection $collection): void {
//		$typeName = $type->getName();
//		if (TypeName::isScalar($typeName) || TypeName::NULL === $typeName) {
//			return;
//		}
//
//		if ($type->isBuiltin()) {
//			throw new TypeNotSupportedForSerializationException('Type is not supported for serialization '
//					. $type->getName());
//		}
//
//		if (!$collection->contains($typeName)) {
//			self::createFromClass($typeName)->determineAllowedClassNames($collection);
//		}
//	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	private function extractAllowedClassNamesFromTypeName(string $typeName, AllowedClassNameCollection $collection): void {
		if (TypeName::isScalar($typeName) || TypeName::NULL === $typeName) {
			return;
		}

		if (TypeName::isBuiltin($typeName)) {
			throw new TypeNotSupportedForSerializationException('Type is not supported for serialization '
					. $typeName);
		}

		if (!$collection->contains($typeName)) {
			self::createFromClass($typeName)->determineAllowedClassNames($collection);
		}
	}


	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	private function extractAllowedClassNamesForCustomSerialization(AllowedClassNameCollection $collection): void {
		IllegalStateException::assertTrue($this->customSerializationMode);

		if ($this->class->isSubclassOf(TypedArray::class)) {
			foreach (CollectionTypeUtils::detectKeyTypeConstraint($this->class)->getNamedTypeConstraints()
					 as $namedTypeConstraint) {
				$this->extractAllowedClassNamesFromTypeName($namedTypeConstraint->getTypeName(), $collection);
			}

			foreach (CollectionTypeUtils::detectValueTypeConstraint($this->class)->getNamedTypeConstraints()
			         as $namedTypeConstraint) {
				$this->extractAllowedClassNamesFromTypeName($namedTypeConstraint->getTypeName(), $collection);
			}
		}
	}

	private function detectValidCustomSerializationMode(): bool {
		return $this->class->getName() === TypedArray::class || $this->class->isSubclassOf(TypedArray::class);
	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	public static function createFromObj(object $obj): SerializableClassAnalyser {
		return self::createFromClass(new ReflectionClass($obj));
	}

	/**
	 * @throws TypeNotSupportedForSerializationException
	 */
	public static function createFromClass(string|\ReflectionClass $class): SerializableClassAnalyser {
		if (is_string($class)) {
			$class = ExUtils::try(fn () => new ReflectionClass($class));
		}

		return new SerializableClassAnalyser($class, false);
	}


}