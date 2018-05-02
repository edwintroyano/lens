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

namespace Lens_0_0_56\Lens\Evaluator;

use Lens_0_0_56\Lens\Php\CallParser;
use Lens_0_0_56\Lens\Php\Code;
use Lens_0_0_56\Lens\Php\Resolver;
use Lens_0_0_56\Lens\Php\Semantics;
use Lens_0_0_56\SpencerMortensen\Parser\ParserException;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;

class Sanitizer
{
	/** @var Paths */
	private $paths;

	/** @var CallParser */
	private $parser;

	/** @var Resolver */
	private $resolver;

	/** @var array */
	private $mockFunctions;

	public function __construct($isFunction, array $mockFunctions)
	{
		// TODO: dependency injection
		$this->paths = Paths::getPlatformPaths();
		$this->parser = new CallParser();
		$this->resolver = new Resolver($isFunction);
		$this->mockFunctions = $mockFunctions;
	}

	public function sanitize($type, $namespace, array $uses, $definitionPhp)
	{
		$tokens = $this->getTokens($type, $definitionPhp);

		$functions = array();
		$edits = array();

		foreach ($tokens as $token) {
			$this->analyzeToken($namespace, $uses, $token, $functions, $edits);
		}

		$requirePhp = $this->getRequirePhp($functions);
		$definitionPhp = $this->applyEdits($definitionPhp, $edits);

		return Code::combine($requirePhp, $definitionPhp);
	}

	private function getTokens($type, $php)
	{
		switch ($type) {
			case 'class':
				$rule = 'class';
				break;

			case 'function':
				$rule = 'function';
				break;

			default:
				$rule = 'functionBody';
				break;
		}

		try {
			return $this->parser->parse($rule, $php);
		} catch (ParserException $exception) {
			// TODO:
			return array();
		}
	}

	private function analyzeToken($namespace, array $uses, array $token, array &$functions, array &$edits)
	{
		list($type, $position, $value) = $token;

		switch ($type) {
			default: // ClassParser::TYPE_CLASS
				$this->analyzeClass($namespace, $uses, $position, $value, $edits);
				break;

			case CallParser::TYPE_FUNCTION:
				$this->analyzeFunction($namespace, $uses, $position, $value, $functions, $edits);
				break;
		}
	}

	private function analyzeClass($namespace, array $uses, $position, $value, array &$edits)
	{
		if (Semantics::isClassIdentifier($value)) {
			return;
		}

		$class = $this->resolver->resolve($namespace, $uses, $value, false);

		if (Semantics::isUnsafeClass($class)) {
			$class = "Lens\\{$class}";
			$edits[$position] = array(strlen($value), "\\{$class}");
		}
	}

	private function analyzeFunction($namespace, array $uses, $position, $value, array &$functions, array &$edits)
	{
		$function = $this->resolver->resolve($namespace, $uses, $value, true);

		if (Semantics::isUnsafeFunction($function)) {
			$function = "Lens\\{$function}";
			$edits[$position] = array(strlen($value), "\\{$function}");
			$functions[$function] = $function;
		} elseif (!Semantics::isPhpFunction($function)) {
			$functions[$function] = $function;
		}
	}

	private function applyEdits($subject, array $edits)
	{
		krsort($edits, SORT_NUMERIC);

		foreach ($edits as $start => $edit) {
			list($length, $replacement) = $edit;

			$subject = substr_replace($subject, $replacement, $start, $length);
		}

		return $subject;
	}

	private function getRequirePhp(array $functions)
	{
		if (count($functions) === 0) {
			return null;
		}

		$lines = array();

		foreach ($functions as $function) {
			$this->getCoreMockPath($function, $pathPhp) ||
			$this->getUserPath($function, $pathPhp);

			$lines[] = Code::getRequireOncePhp($pathPhp);
		}

		return implode("\n", $lines);
	}

	private function getCoreMockPath($function, &$pathPhp)
	{
		if (strncmp($function, 'Lens\\', 5) !== 0) {
			return false;
		}

		$function = substr($function, 5);
		$relativePath = $this->getRelativeFilePath($function);
		$relativePath = DIRECTORY_SEPARATOR . $this->paths->join('functions', 'mock', $relativePath);
		$pathPhp = 'LENS_CORE_DIRECTORY . ' . Code::getValuePhp($relativePath);

		return true;
	}

	private function getUserPath($function, &$pathPhp)
	{
		if (isset($this->mockFunctions[$function])) {
			$directory = 'mock';
		} else {
			$directory = 'live';
		}

		// TODO: this is undoubtedly duplicated elsewhere:
		$relativePath = $this->getRelativeFilePath($function);
		$relativePath = DIRECTORY_SEPARATOR . $this->paths->join('functions', $directory, $relativePath);
		$pathPhp = 'LENS_CACHE_DIRECTORY . ' . Code::getValuePhp($relativePath);

		return true;
	}

	private function getRelativeFilePath($namespacePath)
	{
		$names = explode('\\', $namespacePath);
		return $this->paths->join($names) . '.php';
	}
}
