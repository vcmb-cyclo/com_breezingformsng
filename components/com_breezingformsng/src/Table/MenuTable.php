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

final class MenuTable extends Table
{
	/** @var int|string|null */
	public $id = null;     // identifier
	/** @var string|null */
	public $package = null;     // package name
	/** @var int|string|null */
	public $parent = 0;        // parent id
	/** @var int|string|null */
	public $ordering = 0;        // ordering
	/** @var int|string|null */
	public $published = 1;        // is published
	/** @var string|null */
	public $img = '';       // menu icon image
	/** @var string|null */
	public $title = '';       // displayed menu name
	/** @var string|null */
	public $name = '';       // form name (identifier)
	/** @var int|string|null */
	public $page = 1;        // starting page
	/** @var int|string|null */
	public $frame = 0;        // run in iframe
	/** @var int|string|null */
	public $border = 0;        // show a border
	/** @var string|null */
	public $params = null;     // additional parameters

	public function __construct(DatabaseInterface $db)
	{
		parent::__construct('#__facileforms_compmenus', 'id', $db);
	} // constructor

	public function load($id = null, $reset = true)
	{
		return parent::load($id, $reset ?? true);
	}

	/**
	 * The move event of Joomla takes the conditions as one string, while
	 * AdminModel::reorder() hands over the list of them.
	 */
	public function move($delta, $where = '')
	{
		return parent::move($delta, is_array($where) ? implode(' AND ', $where) : $where);
	}

} // class MenuTable

