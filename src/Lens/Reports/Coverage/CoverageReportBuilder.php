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

namespace _Lens\Lens\Reports\Coverage;

use _Lens\SpencerMortensen\Filesystem\Directory;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\Filesystem\Path;

class CoverageReportBuilder
{
	/** @var Path */
	private $core;

	/** @var Path */
	private $cache;

	/** @var Filesystem */
	private $filesystem;

	public function __construct(Path $core, Path $cache, Path $coverage, Filesystem $filesystem)
	{
		$this->core = $core;
		$this->cache = $cache;
		$this->coverage = $coverage;
		$this->filesystem = $filesystem;
	}

	public function build(array $executableStatements, array $results)
	{
		$baseComponents = ['Example'];

		$dataBuilder = new CoverageDataBuilder($this->core, $this->cache);
		$data = $dataBuilder->build($executableStatements, $results);

		$fileGenerator = new CoverageFilesGenerator($this->core);
		$files = $fileGenerator->generate($baseComponents, $data);

		// TODO: where does this go?
		$path = $this->core->add('style', '.theme', 'style.css');
		$file = new File($path);
		$files['.theme']['style.css'] = $file->read();

		$path = $this->core->add('style', 'favicon.ico');
		$file = new File($path);
		$files['favicon.ico'] = $file->read();
		//

		$directory = new Directory($this->coverage);
		$directory->write($files);
	}
}

/*
	private function getInstructionsHtml()
	{
		$followupMessage = "When you’re finished making changes, run Lens again and refresh this page to see the results.";

		if (!extension_loaded('xdebug')) {
			$basicMessage = "You’ll be able to see your code coverage here after you’ve enabled the “xdebug” extension for PHP.";

			if ($this->isLinux($os, $version) && ($os === 'ubuntu')) {
				$package = self::getUbuntuPackage($version);
				$command = "sudo apt-get install {$package}";
				$commandHtml = self::getCodeHtml($command);

				return "<p>{$basicMessage} Here’s the command:</p>\n\n{$commandHtml}\n\n<p>{$followupMessage}</p>";
			}

			return "<p>{$basicMessage}</p>\n\n<p>{$followupMessage}</p>";
		}

		$xdebugInstalledVersion = phpversion('xdebug');
		$xdebugMinimumVersion = '2.2';

		if (!version_compare($xdebugInstalledVersion, $xdebugMinimumVersion, '>=')) {
			return "<p>You’ll be able to see your code coverage here after you’ve upgraded to xdebug {$xdebugMinimumVersion} or greater. {$followupMessage}</p>";
		}

		if (!ini_get('xdebug.coverage_enable')) {
			return "<p>You’ll be able to see your code coverage here after you’ve set your “xdebug.coverage_enable” property to “On” in your PHP configuration settings. {$followupMessage}</p>";
		}

		return "<p>You’ll be able to see your code coverage here after you’ve enabled the “xdebug” extension for PHP. {$followupMessage}</p>";
	}

	private function isLinux(&$os, &$version)
	{
		$osReleaseText = $this->filesystem->read('/etc/os-release');

		if ($osReleaseText === null) {
			return false;
		}

		if (preg_match('~^ID=(.*)$~m', $osReleaseText, $match) === 1) {
			$os = $match[1];
		}

		if (preg_match('~^VERSION_ID="(.*)"$~m', $osReleaseText, $match) === 1) {
			$version = $match[1];
		}

		return true;
	}

	private static function getUbuntuPackage($version)
	{
		if ((float)$version < 16.04) {
			return 'php5-xdebug';
		}

		return 'php-xdebug';
	}

	private static function getCodeHtml($code)
	{
		$codeHtml = self::html5TextEncode($code);

		return "<pre><code>{$codeHtml}</code></pre>";
	}
*/
