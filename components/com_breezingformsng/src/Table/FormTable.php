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

final class FormTable extends Table
{
	/** @var int|string|null */
	public $id = null;     // identifier
	/** @var int|string|null */
	public $package = null;     // package name
	/** @var int|string|null */
	public $ordering = null;     // ordering
	/** @var int|string|null */
	public $published = null;     // no/yes
	/** @var int|string|null */
	public $runmode = null;     // 0-any/1-foreground/2-background
	/** @var int|string|null */
	public $name = null;     // form name (identifier)
	/** @var int|string|null */
	public $title = null;     // fancy name
	/** @var int|string|null */
	public $description = null;     // form description
	/** @var int|string|null */
	public $class1 = null;     // css class for <div>
	/** @var int|string|null */
	public $class2 = null;     // css class for <form>
	/** @var int|string|null */
	public $width = null;     // form width in px
	/** @var int|string|null */
	public $widthmode = null;     // 0=px 1=%
	/** @var int|string|null */
	public $height = null;     // form height in px
	/** @var int|string|null */
	public $heightmode = null;     // 0=px 1=auto
	/** @var int|string|null */
	public $pages = null;     // # of pages
	/** @var int|string|null */
	public $emailntf = null;     // none/default/custom
	/** @var int|string|null */
	public $mb_emailntf = null;     // none/default/custom
	/** @var int|string|null */
	public $emaillog = null;     // header only/nonempty values/all
	/** @var int|string|null */
	public $mb_emaillog = null;     // header only/nonempty values/all
	/** @var int|string|null */
	public $emailxml = null;     // xml attachment no/nonempty values/all
	/** @var int|string|null */
	public $mb_emailxml = null;     // xml attachment no/nonempty values/all
	/** @var int|string|null */
	public $emailadr = null;     // custom email address
	/** @var int|string|null */
	public $dblog = null;     // no/nonempty values/all
	/** @var int|string|null */
	public $script1cond = null;     // init: none/library/custom
	/** @var int|string|null */
	public $script1id = null;     // library function id
	/** @var int|string|null */
	public $script1code = null;     // custom code ff_{form}_init()
	/** @var int|string|null */
	public $script2cond = null;     // submitted: none/library/custom
	/** @var int|string|null */
	public $script2id = null;     // library function id
	/** @var int|string|null */
	public $script2code = null;     // custom code ff_{form}_submitted(status='success','failed')
	/** @var int|string|null */
	public $piece1cond = null;     // Before form: none/library/custom
	/** @var int|string|null */
	public $piece1id = null;     // library function id
	/** @var int|string|null */
	public $piece1code = null;     // custom code
	/** @var int|string|null */
	public $piece2cond = null;     // After form: none/library/custom
	/** @var int|string|null */
	public $piece2id = null;     // library function id
	/** @var int|string|null */
	public $piece2code = null;     // custom code
	/** @var int|string|null */
	public $piece3cond = null;     // Begin submit: none/library/custom
	/** @var int|string|null */
	public $piece3id = null;     // library function id
	/** @var int|string|null */
	public $piece3code = null;     // custom code
	/** @var int|string|null */
	public $piece4cond = null;     // End submit: none/library/custom
	/** @var int|string|null */
	public $piece4id = null;     // library function id
	/** @var int|string|null */
	public $piece4code = null;     // custom code
	/** @var int|string|null */
	public $prevmode = null;     // preview mode 0-none 1-below 2-overlay
	/** @var int|string|null */
	public $prevwidth = null;     // preview width px in case of widthmode=1
		/** @var int|string|null */
		public $template_code_processed = null; // processed QuickMode template marker
	/** @var int|string|null */
	public $template_code = null;
	/** @var int|string|null */
	public $template_areas = null;
	/** @var int|string|null */
	public $custom_mail_subject = null;
	/** @var int|string|null */
	public $mb_custom_mail_subject = null;
	/** @var int|string|null */
	public $alt_mailfrom = null;
	/** @var int|string|null */
	public $mb_alt_mailfrom = null;
	/** @var int|string|null */
	public $alt_fromname = null;
	/** @var int|string|null */
	public $mb_alt_fromname = null;
	/** @var int|string|null */
	public $mailchimp_email_field = null;
	/** @var int|string|null */
	public $mailchimp_api_key = null;
	/** @var int|string|null */
	public $mailchimp_list_id = null;
	/** @var int|string|null */
	public $mailchimp_double_optin = null;
	/** @var int|string|null */
	public $mailchimp_mergevars = null;
	/** @var int|string|null */
	public $mailchimp_checkbox_field = null;
	/** @var int|string|null */
	public $mailchimp_text_html_mobile_field = null;
	/** @var int|string|null */
	public $mailchimp_send_errors = null;
	/** @var int|string|null */
	public $mailchimp_update_existing = null;
	/** @var int|string|null */
	public $mailchimp_replace_interests = null;
	/** @var int|string|null */
	public $mailchimp_send_welcome = null;
	/** @var int|string|null */
	public $mailchimp_default_type = null;
	/** @var int|string|null */
	public $mailchimp_unsubscribe_field = null;
	/** @var int|string|null */
	public $mailchimp_send_notify = null;
	/** @var int|string|null */
	public $mailchimp_send_goodbye = null;
	/** @var int|string|null */
	public $mailchimp_delete_member = null;
	/** @var int|string|null */
	public $salesforce_token = null;
	/** @var int|string|null */
	public $salesforce_username = null;
	/** @var int|string|null */
	public $salesforce_password = null;
	/** @var int|string|null */
	public $salesforce_type = null;
	/** @var string|list<string>|null */
	public $salesforce_fields = null;
	/** @var int|string|null */
	public $salesforce_enabled = null;
	/** @var int|string|null */
	public $email_type = null;
	/** @var int|string|null */
	public $mb_email_type = null;
	/** @var int|string|null */
	public $email_custom_template = null;
	/** @var int|string|null */
	public $mb_email_custom_template = null;
	/** @var int|string|null */
	public $email_custom_html = null;
	/** @var int|string|null */
	public $mb_email_custom_html = null;
	/** @var int|string|null */
	public $dropbox_email = '';
	/** @var int|string|null */
	public $dropbox_password = '';
	/** @var int|string|null */
	public $dropbox_folder = '';
	/** @var int|string|null */
	public $dropbox_submission_enabled = 0;
	/** @var int|string|null */
	public $dropbox_submission_types = 'pdf';
	/** @var int|string|null */
	public $double_opt = '';
	/** @var int|string|null */
	public $opt_mail = '';
	/** @var int|string|null */
	public $created = null;
	/** @var int|string|null */
	public $created_by = null;
	/** @var int|string|null */
	public $modified = null;
	/** @var int|string|null */
	public $modified_by = null;

	public function __construct(DatabaseInterface $db)
	{
		parent::__construct('#__facileforms_forms', 'id', $db);
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

} // class FormTable

