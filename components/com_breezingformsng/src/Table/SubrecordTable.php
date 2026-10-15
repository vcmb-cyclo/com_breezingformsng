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

final class SubrecordTable extends Table
{
	/** @var int|string|null */
	public $id = null;     	// identifier
	/** @var int|string|null */
	public $record = null;  // record id
	/** @var int|string|null */
	public $element = null; // element id
	/** @var string|null */
	public $name = null;    // element name
	/** @var string|null */
	public $type = null;    // data type
	/** @var string|null */
	public $value = null;   // data value

	public function __construct(DatabaseInterface $db)
	{
		parent::__construct('#__facileforms_subrecords', 'id', $db);
	} // constructor

	public function load($id = null, $reset = true)
	{
		return parent::load($id, $reset ?? true);
	}

} // class SubrecordTable

