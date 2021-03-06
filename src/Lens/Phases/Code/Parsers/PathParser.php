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

namespace _Lens\Lens\Phases\Code\Parsers;

use _Lens\Lens\Phases\Code\Input;
use _Lens\Lens\Php\Lexer;

class PathParser
{
	/** @var Input */
	private $input;

	public function parse(Input $input, &$range = null)
	{
		$this->input = $input;
		$iBegin = $this->input->getPosition();

		$this->input->get(Lexer::NAMESPACE_SEPARATOR_);

		if (
			$this->input->get(Lexer::IDENTIFIER_) &&
			$this->getLinks()
		) {
			$iEnd = $this->input->getPosition() - 1;
			$range = [$iBegin => $iEnd];
			return true;
		}

		$this->input->setPosition($iBegin);
		return false;
	}

	private function getLinks()
	{
		while ($this->getLink());

		return true;
	}

	private function getLink()
	{
		$position = $this->input->getPosition();

		if (
			$this->input->get(Lexer::NAMESPACE_SEPARATOR_) &&
			$this->input->get(Lexer::IDENTIFIER_)
		) {
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}
}
