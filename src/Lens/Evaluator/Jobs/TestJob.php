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

namespace Lens_0_0_56\Lens\Evaluator\Jobs;

use Lens_0_0_56\Lens\Evaluator\Autoloader;
use Lens_0_0_56\Lens\Evaluator\Processor;
use Lens_0_0_56\Lens\Evaluator\Test;
use Lens_0_0_56\SpencerMortensen\Exceptions\Exceptions;
use Lens_0_0_56\SpencerMortensen\ParallelProcessor\ServerProcess;

class TestJob implements Job
{
	/** @var string */
	private $executable;

	/** @var string */
	private $src;

	/** @var string */
	private $autoload;

	/** @var string */
	private $cache;

	/** @var string */
	private $namespace;

	/** @var array */
	private $uses;

	/** @var string */
	private $prePhp;

	/** @var null|array */
	private $script;

	/** @var string */
	private $postPhp;

	/** @var Processor */
	private $processor;

	/** @var null|ServerProcess */
	private $process;

	/** @var null|array */
	private $results;

	/** @var null|array */
	private $coverage;

	public function __construct($executable, $src, $autoload, $cache, $namespace, array $uses, $prePhp, array $script = null, $postPhp, Processor $processor, ServerProcess &$process = null, array &$results = null, array &$coverage = null)
	{
		$this->executable = $executable;
		$this->src = $src;
		$this->autoload = $autoload;
		$this->cache = $cache;
		$this->namespace = $namespace;
		$this->uses = $uses;
		$this->prePhp = $prePhp;
		$this->script = $script;
		$this->postPhp = $postPhp;
		$this->processor = $processor;
		$this->process = &$process;
		$this->results = &$results;
		$this->coverage = &$coverage;
	}

	public function getCommand()
	{
		$arguments = array($this->src, $this->autoload, $this->cache, $this->namespace, $this->uses, $this->prePhp, $this->script, $this->postPhp);
		$serialized = serialize($arguments);
		$compressed = gzdeflate($serialized, -1);
		$encoded = base64_encode($compressed);

		return "{$this->executable} --internal-test={$encoded}";
	}

	public function start()
	{
		$worker = $this->process;
		$test = new Test($this->executable, $this->src, $this->autoload, $this->cache);

		$sendResults = function () use ($worker, $test) {
			$results = array(
				'pre' => $test->getPreState(),
				'post' => $test->getPostState()
			);

			$coverage = $test->getCoverage();

			$worker->sendResult(array($results, $coverage));
		};

		Exceptions::on($sendResults);

		$test->run($this->namespace, $this->uses, $this->prePhp, $this->script, $this->postPhp);

		Exceptions::off();

		call_user_func($sendResults);
	}

	public function stop($message)
	{
		if ($message === true) {
			$this->reboot();
			return;
		}

		list($this->results, $this->coverage) = $message;
	}

	private function reboot()
	{
		$this->process = $this->processor->getProcess($this);
		$this->processor->start($this->process);
	}
}
