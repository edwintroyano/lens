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

namespace Lens\Evaluator;

class Code
{
	const LENS_CONSTANT_NAME = 'LENS';

	public function prepare($lensDirectory, $autoloaderPath)
	{
		define(self::LENS_CONSTANT_NAME, $lensDirectory . '/');

		spl_autoload_register(
			function ($class)
			{
				$mockPrefix = 'Lens\\Mock\\';
				$mockPrefixLength = strlen($mockPrefix);

				if (strncmp($class, $mockPrefix, $mockPrefixLength) !== 0) {
					return;
				}

				$parentClass = substr($class, $mockPrefixLength);

				$mockBuilder = new MockBuilder($mockPrefix, $parentClass);
				$mockCode = $mockBuilder->getMock();

				eval($mockCode);
			}
		);

		if (is_string($autoloaderPath)) {
			require $autoloaderPath;
		}
	}

	public function getExpectedPhp($fixture, $input, $output)
	{
		$contextPhp = self::extractContextPhp($fixture);
		$beforePhp = self::combine($contextPhp, $fixture, $input);
		$afterPhp = self::combine($contextPhp, self::useMockCalls($output));

		return array($beforePhp, $afterPhp);
	}

	public function getActualPhp($fixture, $input, $subject)
	{
		$contextPhp = self::extractContextPhp($fixture);
		$beforePhp = self::combine($contextPhp, $fixture, $input);
		$afterPhp = self::combine($contextPhp, $subject);

		return array($beforePhp, $afterPhp);
	}

	private static function extractContextPhp(&$fixturePhp)
	{
		$namespacePhp = self::extractNamespacePhp($fixturePhp);
		$usePhp = self::extractUsePhp($fixturePhp);

		return self::combine($namespacePhp, $usePhp);
	}

	private static function extractNamespacePhp(&$inputPhp)
	{
		$namespacePattern = self::getPattern('^\s*namespace\h+[^\n\r]+');

		if (preg_match($namespacePattern, $inputPhp, $match, PREG_OFFSET_CAPTURE) !== 1) {
			return null;
		}

		$inputPhp = trim(substr($inputPhp, $match[0][1] + strlen($match[0][0])));
		return trim($match[0][0]);
	}

	// TODO: this might corrupt multiline text strings:
	private static function extractUsePhp(&$inputPhp)
	{
		$inputLines = self::getLines($inputPhp);
		$outputLines = array();

		foreach ($inputLines as $i => $line) {
			if (self::getUsePhp($line, $usePhp)) {
				unset($inputLines[$i]);
				$outputLines[] = $usePhp;
			}
		}

		$inputPhp = self::getTrimmedString($inputLines);
		return self::getTrimmedString($outputLines);
	}

	private static function getLines($code)
	{
		$pattern = self::getPattern('\\n|\\r', 'm');
		return preg_split($pattern, trim($code));
	}

	private static function getUsePhp($code, &$usePhp)
	{
		$pattern = self::getPattern('^\h*use\\h+([^\\h]+);\h*(// Mock)?\h*$');

		if (preg_match($pattern, $code, $match) !== 1) {
			return false;
		}

		$namespace = $match[1];
		$isMock = (count($match) === 3);

		if ($isMock) {
			$namespace = 'Lens\\Mock\\' . $namespace;
		}

		$usePhp = "use {$namespace};";
		return true;
	}

	private static function getTrimmedString(array $lines)
	{
		if (count($lines) === 0) {
			return null;
		}

		$string = trim(implode("\n", $lines));

		if (count($string) === 0) {
			return null;
		}

		return $string;
	}

	private static function useMockCalls($code)
	{
		$expression = '^\\s*(\\$.+?)->(.+?)\\((.*?)\\);\\s*// (return|throw)\\s+(.*);$';

		$pattern = self::getPattern($expression, 'm');

		return preg_replace_callback($pattern, 'self::getMockCall', $code);
	}

	protected static function getMockCall(array $match)
	{
		list(, $object, $method, $argumentList, $resultAction, $resultValue) = $match;

		$methodName = var_export($method, true);
		$arguments = "array({$argumentList})";

		$resultType = ($resultAction === 'throw' ? Agent::ACTION_THROW : Agent::ACTION_RETURN);
		$result = "array({$resultType}, {$resultValue})";

		return "\\Lens\\Evaluator\\Agent::call({$object}, {$methodName}, {$arguments}, {$result});";
	}

	private static function combine()
	{
		return implode("\n\n", array_filter(func_get_args(), 'is_string'));
	}

	private static function getPattern($expression, $flags = '')
	{
		$delimiter = "\x03";

		return "{$delimiter}{$expression}{$delimiter}{$flags}";
	}
}