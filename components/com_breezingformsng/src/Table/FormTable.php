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
	public $id = null;     // identifier
	public $package = null;     // package name
	public $ordering = null;     // ordering
	public $published = null;     // no/yes
	public $runmode = null;     // 0-any/1-foreground/2-background
	public $name = null;     // form name (identifier)
	public $title = null;     // fancy name
	public $description = null;     // form description
	public $class1 = null;     // css class for <div>
	public $class2 = null;     // css class for <form>
	public $width = null;     // form width in px
	public $widthmode = null;     // 0=px 1=%
	public $height = null;     // form height in px
	public $heightmode = null;     // 0=px 1=auto
	public $pages = null;     // # of pages
	public $emailntf = null;     // none/default/custom
	public $mb_emailntf = null;     // none/default/custom
	public $emaillog = null;     // header only/nonempty values/all
	public $mb_emaillog = null;     // header only/nonempty values/all
	public $emailxml = null;     // xml attachment no/nonempty values/all
	public $mb_emailxml = null;     // xml attachment no/nonempty values/all
	public $emailadr = null;     // custom email address
	public $dblog = null;     // no/nonempty values/all
	public $script1cond = null;     // init: none/library/custom
	public $script1id = null;     // library function id
	public $script1code = null;     // custom code ff_{form}_init()
	public $script2cond = null;     // submitted: none/library/custom
	public $script2id = null;     // library function id
	public $script2code = null;     // custom code ff_{form}_submitted(status='success','failed')
	public $piece1cond = null;     // Before form: none/library/custom
	public $piece1id = null;     // library function id
	public $piece1code = null;     // custom code
	public $piece2cond = null;     // After form: none/library/custom
	public $piece2id = null;     // library function id
	public $piece2code = null;     // custom code
	public $piece3cond = null;     // Begin submit: none/library/custom
	public $piece3id = null;     // library function id
	public $piece3code = null;     // custom code
	public $piece4cond = null;     // End submit: none/library/custom
	public $piece4id = null;     // library function id
	public $piece4code = null;     // custom code
	public $prevmode = null;     // preview mode 0-none 1-below 2-overlay
	public $prevwidth = null;     // preview width px in case of widthmode=1
		public $template_code_processed = null; // processed QuickMode template marker
	public $template_code = null;
	public $template_areas = null;
	public $custom_mail_subject = null;
	public $mb_custom_mail_subject = null;
	public $alt_mailfrom = null;
	public $mb_alt_mailfrom = null;
	public $alt_fromname = null;
	public $mb_alt_fromname = null;
	public $mailchimp_email_field = null;
	public $mailchimp_api_key = null;
	public $mailchimp_list_id = null;
	public $mailchimp_double_optin = null;
	public $mailchimp_mergevars = null;
	public $mailchimp_checkbox_field = null;
	public $mailchimp_text_html_mobile_field = null;
	public $mailchimp_send_errors = null;
	public $mailchimp_update_existing = null;
	public $mailchimp_replace_interests = null;
	public $mailchimp_send_welcome = null;
	public $mailchimp_default_type = null;
	public $mailchimp_unsubscribe_field = null;
	public $mailchimp_send_notify = null;
	public $mailchimp_send_goodbye = null;
	public $mailchimp_delete_member = null;
	public $salesforce_token = null;
	public $salesforce_username = null;
	public $salesforce_password = null;
	public $salesforce_type = null;
	public $salesforce_fields = null;
	public $salesforce_enabled = null;
	public $email_type = null;
	public $mb_email_type = null;
	public $email_custom_template = null;
	public $mb_email_custom_template = null;
	public $email_custom_html = null;
	public $mb_email_custom_html = null;
	public $dropbox_email = '';
	public $dropbox_password = '';
	public $dropbox_folder = '';
	public $dropbox_submission_enabled = 0;
	public $dropbox_submission_types = 'pdf';
	public $double_opt = '';
	public $opt_mail = '';
	public $created = null;
	public $created_by = null;
	public $modified = null;
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

