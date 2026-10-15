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
	/** @var int|string|null */
	public $id = null;     		// identifier
	/** @var string|null */
	public $submitted = null;   // date and time
	/** @var int|string|null */
	public $form = null;     	// form id
	/** @var string|null */
	public $title = null;     	// form title
	/** @var string|null */
	public $name = null;     	// form name
	/** @var string|null */
	public $ip = null;     		// submitters ip
	/** @var string|null */
	public $browser = null;     // browser
	/** @var string|null */
	public $opsys = null;     	// operating system
	/** @var string|null */
	public $provider = null;    // provider
	/** @var int|string|null */
	public $viewed = null;     	// view status
	/** @var int|string|null */
	public $exported = null;    // export status
	/** @var int|string|null */
	public $state = null;       // publication state: 1 published, 2 archived, -2 trashed
	/** @var string|null */
	public $paypal_tx_id = null;
	/** @var string|null */
	public $paypal_payment_date = null;
	/** @var int|string|null */
	public $paypal_testaccount = null;
	/** @var int|string|null */
	public $paypal_download_tries = null;

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
		$query = $db->createQuery()
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

