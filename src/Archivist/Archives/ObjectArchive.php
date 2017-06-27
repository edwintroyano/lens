<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of testphp.
 *
 * Testphp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Testphp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with testphp. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@testphp.org>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace TestPhp\Archivist\Archives;

use ReflectionClass;
use ReflectionProperty;
use TestPhp\Archivist\Archivist;

class ObjectArchive extends Archive
{
	/** @var string */
	private $id;

	/** @var string */
	private $class;

	/** @var array */
	private $properties;

	public function __construct($object)
	{
		parent::__construct(Archive::TYPE_OBJECT);

		$this->id = spl_object_hash($object);
		$this->class = get_class($object);
		$this->properties = self::archiveProperties($object);
	}

	private static function archiveProperties($object)
	{
		$properties = array();

		$reflectionClass = new ReflectionClass($object);

		do {
			$class = $reflectionClass->getName();
			$reflectionProperties = $reflectionClass->getProperties();

			/** @var ReflectionProperty $reflectionProperty */
			foreach ($reflectionProperties as $reflectionProperty) {
				$reflectionProperty->setAccessible(true);

				$name = $reflectionProperty->getName();
				$value = $reflectionProperty->getValue($object);

				$properties[$class][$name] = Archivist::archive($value);
			}

			$reflectionClass = $reflectionClass->getParentClass();
		} while ($reflectionClass !== false);

		return $properties;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getClass()
	{
		return $this->class;
	}

	public function getProperties()
	{
		return $this->properties;
	}

	public function setProperties(array $properties)
	{
		$this->properties = $properties;
	}
}