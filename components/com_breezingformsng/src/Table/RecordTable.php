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
use Joomla\Database\ParameterType;

final class RecordTable extends Table
{
	public $id = null;     		// identifier
	public $submitted = null;   // date and time
	public $form = null;     	// form id
	public $title = null;     	// form title
	public $name = null;     	// form name
	public $ip = null;     		// submitters ip
	public $browser = null;     // browser
	public $opsys = null;     	// operating system
	public $provider = null;    // provider
	public $viewed = null;     	// view status
	public $exported = null;    // export status
	public $state = null;       // publication state: 1 published, 2 archived, -2 trashed
	public $paypal_tx_id = null;
	public $paypal_payment_date = null;
	public $paypal_testaccount = null;
	public $paypal_download_tries = null;

	public function __construct(DatabaseInterface $db)
	{
		parent::__construct('#__facileforms_records', 'id', $db);
		$this->setColumnAlias('published', 'state');
	} // constructor

	/**
	 * Deletes the record together with its submitted values.
	 */
	public function delete($pk = null)
	{
		$recordId = (int) ($pk ?? $this->id);

		if (!parent::delete($pk)) {
			return false;
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__facileforms_subrecords'))
			->where($db->quoteName('record') . ' = :recordId')
			->bind(':recordId', $recordId, ParameterType::INTEGER);
		$db->setQuery($query)->execute();

		return true;
	}

	public function load($id = null, $reset = true)
	{
		return parent::load($id, $reset ?? true);
	}

} // class RecordTable

