<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of Lens.
 *
 * Lens is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Lens is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Lens. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace Lens_0_0_56\Lens;

use Lens_0_0_56\Lens\Files\TextFile;
use Lens_0_0_56\Mustangostang\Spyc\Spyc as Yaml;

class Settings
{
	/** @var TextFile */
	private $file;

	/** @var Yaml */
	private $yaml;

	/** @var array */
	private $input;

	/** @var array */
	private $values;

	public function __construct(Filesystem $filesystem, $path)
	{
		$file = new TextFile($filesystem, $path);
		$yaml = new Yaml();

		$file->read($content);
		$input = $yaml->load($content);
		$values = $this->getValues($input);

		$this->file = $file;
		$this->yaml = $yaml;
		$this->input = $input;
		$this->values = $values;
	}

	private function getValues($input)
	{
		$src = self::getNonEmptyString($input['src']);
		$autoload = self::getNonEmptyString($input['autoload']);
		$cache = self::getNonEmptyString($input['cache']);
		$checkForUpdates = self::getBoolean($input['checkForUpdates']);
		$mockClasses = self::getStringList($input['mockClasses']);
		$mockFunctions = self::getStringList($input['mockFunctions']);

		if ($checkForUpdates === null) {
			$checkForUpdates = true;
		}

		return array(
			'src' => $src,
			'autoload' => $autoload,
			'cache' => $cache,
			'checkForUpdates' => $checkForUpdates,
			'mockClasses' => $mockClasses,
			'mockFunctions' => $mockFunctions
		);
	}

	private static function getNonEmptyString(&$value)
	{
		if (!is_string($value) || (strlen($value) === 0)) {
			return null;
		}

		return $value;
	}

	private static function getBoolean(&$value)
	{
		if (!is_bool($value)) {
			return null;
		}

		return $value;
	}

	private static function getStringList(&$value)
	{
		if (!is_array($value)) {
			return null;
		}

		$value = array_filter($value, 'is_string');
		return array_values($value);
	}

	public function get($key)
	{
		return $this->values[$key];
	}

	public function set($key, $value)
	{
		$this->values[$key] = $value;
	}

	public function __destruct()
	{
		if ($this->values === $this->input) {
			return;
		}

		$content = $this->yaml->dump($this->values, 1, false, true);
		$this->file->write($content);
	}
}
