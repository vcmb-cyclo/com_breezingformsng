<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Table;

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

final class QueryColumn
{
	/** @var string|null */
	public $title = null;    // column title
	/** @var string|null */
	public $name = null;     // column name
	/** @var string|null */
	public $class1 = null;   // class for th
	/** @var string|null */
	public $class2 = null;   // class for td(1)
	/** @var string|null */
	public $class3 = null;   // class for td(2)
	/** @var int|string|null */
	public $thspan = null;   // th span
	/** @var int|string|null */
	public $align = null;    // 0-left 1-center 2-right
	/** @var int|string|null */
	public $valign = null;   // 0-top 1-middle 2-bottom 3-baseline
	/** @var int|string|null */
	public $wrap = null;     // 0-nowrap 1-wrap
	/** @var string|null */
	public $value = null;    // value field (php allowed)
	/** @var array<int, array{bool, string}>|null */
	public $comp = null;     // complied value: array of array(type, value/code)

	/** @var string|null */
	public $width = null;
	/** @var int|string|null */
	public $widthmd = null;
	/** @var int|string|null */
	public $thalign = null;
	/** @var int|string|null */
	public $thvalign = null;
	/** @var int|string|null */
	public $thwrap = null;

	function __construct()
	{
		$this->title = '';
		$this->name = '';
		$this->class1 = '';
		$this->class2 = '';
		$this->class3 = '';
		$this->width = '';
		$this->widthmd = 0;
		$this->thspan = 1;
		$this->thalign = 0;
		$this->thvalign = 0;
		$this->thwrap = 0;
		$this->align = 0;
		$this->valign = 0;
		$this->wrap = 0;
		$this->value = '';
	} // constructor

	/**
	 * @param string $line
	 * @return void
	 */
	function unpack($line)
	{
		$vals = explode('&', $line);
		$cnt = count($vals);
		if ($cnt > 0)
			$this->title = impstring($vals[0]);
		if ($cnt > 1)
			$this->name = impstring($vals[1]);
		if ($cnt > 2)
			$this->class1 = impstring($vals[2]);
		if ($cnt > 3)
			$this->class2 = impstring($vals[3]);
		if ($cnt > 4)
			$this->class3 = impstring($vals[4]);
		if ($cnt > 5)
			$this->width = impstring($vals[5]);
		if ($cnt > 6)
			$this->widthmd = impstring($vals[6]);
		if ($cnt > 7)
			$this->thspan = impstring($vals[7]);
		if ($cnt > 8)
			$this->thalign = impstring($vals[8]);
		if ($cnt > 9)
			$this->thvalign = impstring($vals[9]);
		if ($cnt > 10)
			$this->thwrap = impstring($vals[10]);
		if ($cnt > 11)
			$this->align = impstring($vals[11]);
		if ($cnt > 12)
			$this->valign = impstring($vals[12]);
		if ($cnt > 13)
			$this->wrap = impstring($vals[13]);
		if ($cnt > 14)
			$this->value = impstring($vals[14]);
	} // unpack

	/** @return string */
	function pack()
	{
		return
			expstring($this->title ?? '') . '&' .
			expstring($this->name ?? '') . '&' .
			expstring($this->class1 ?? '') . '&' .
			expstring($this->class2 ?? '') . '&' .
			expstring($this->class3 ?? '') . '&' .
			expstring($this->width ?? '') . '&' .
			expstring((string) $this->widthmd) . '&' .
			expstring((string) $this->thspan) . '&' .
			expstring((string) $this->thalign) . '&' .
			expstring((string) $this->thvalign) . '&' .
			expstring((string) $this->thwrap) . '&' .
			expstring((string) $this->align) . '&' .
			expstring((string) $this->valign) . '&' .
			expstring((string) $this->wrap) . '&' .
			expstring($this->value ?? '');
	} // pack

} // class QueryColumn

