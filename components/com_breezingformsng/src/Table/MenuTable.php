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
	public $id = null;     // identifier
	public $package = null;     // package name
	public $parent = 0;        // parent id
	public $ordering = 0;        // ordering
	public $published = 1;        // is published
	public $img = '';       // menu icon image
	public $title = '';       // displayed menu name
	public $name = '';       // form name (identifier)
	public $page = 1;        // starting page
	public $frame = 0;        // run in iframe
	public $border = 0;        // show a border
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

