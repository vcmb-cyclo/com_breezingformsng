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

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

final class ScriptTable extends Table
{
	/** @var int|string|null */
	public $id = null;     		// identifier
	/** @var int|string|null */
	public $published = null;   // is published
	/** @var int|string|null */
	public $package = null;     // package name
	/** @var int|string|null */
	public $name = null;     	// function name
	/** @var int|string|null */
	public $title = null;     	// fancy name
	/** @var int|string|null */
	public $description = null; // description
	/** @var int|string|null */
	public $type = null;     	// type name
	/** @var int|string|null */
	public $code = null;     	// the code
	/** @var int|string|null */
	public $unit_tests = null;   // unit test definitions
	/** @var int|string|null */
	public $created = null;
	/** @var int|string|null */
	public $created_by = null;
	/** @var int|string|null */
	public $modified = null;
	/** @var int|string|null */
	public $modified_by = null;

	/**
	 * The check-out columns are nullable: checking in writes NULL, not 0.
	 *
	 * @var bool
	 */
	protected $_supportNullValue = true;

	/** @var int|string|null */
	public $checked_out = null;
	/** @var string|null */
	public $checked_out_time = null;

	public function __construct(DatabaseInterface $db)
	{
		parent::__construct('#__facileforms_scripts', 'id', $db);
	} // constructor

	public function load($id = null, $reset = true)
	{
		return parent::load($id, $reset ?? true);
	}

} // class ScriptTable

