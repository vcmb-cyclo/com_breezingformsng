<?php
/**
* BreezingForms - A Joomla Forms Application
* @version 1.9
* @package BreezingForms
* @copyright (C) 2008-2020 by Markus Bopp
* @license Released under the terms of the GNU General Public License
**/
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

$_1 = "DROP TABLE IF EXISTS `#__facileforms_config`;";

$_2 = "CREATE TABLE `#__facileforms_config` (
  `id` varchar(30) NOT NULL DEFAULT '',
  `value` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_3 = "DROP TABLE IF EXISTS `#__facileforms_packages`";


$_4 = "CREATE TABLE `#__facileforms_packages` (
  `id` varchar(30) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `version` varchar(30) NOT NULL DEFAULT '',
  `created` varchar(20) NOT NULL DEFAULT '',
  `title` varchar(50) NOT NULL DEFAULT '',
  `author` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(50) NOT NULL DEFAULT '',
  `url` varchar(50) NOT NULL DEFAULT '',
  `description` varchar(100) NOT NULL DEFAULT '',
  `copyright` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_5 = "INSERT INTO `#__facileforms_packages` VALUES (
  '',
  'mypck_001',
  '0.0.1',
  '2005-07-31 22:21:23',
  'My First Package',
  'My Name',
  'my.name@my.domain',
  'http://www.my.domain',
  'This is the first package that I created',
  'This FacileForms package is released under the GNU/GPL license'
)";

$_6 = "DROP TABLE IF EXISTS `#__facileforms_compmenus`";

$_7 = "CREATE TABLE `#__facileforms_compmenus` (
  `id` int(11) NOT NULL,
  `package` varchar(30) NOT NULL DEFAULT '',
  `parent` int(11) NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `img` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `page` int(11) NOT NULL DEFAULT '1',
  `frame` tinyint(1) NOT NULL DEFAULT '0',
  `border` tinyint(1) NOT NULL DEFAULT '0',
  `params` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_8 = "DROP TABLE IF EXISTS `#__facileforms_forms`";

$_9 = "CREATE TABLE `#__facileforms_forms` (
  `id` int(11) NOT NULL,
  `alt_mailfrom` text,
  `alt_fromname` text,
  `mb_alt_mailfrom` text,
  `mb_alt_fromname` text,
  `mailchimp_email_field` varchar(255) NOT NULL DEFAULT '',
  `mailchimp_checkbox_field` varchar(255) NOT NULL DEFAULT '',
  `mailchimp_api_key` varchar(255) NOT NULL DEFAULT '',
  `mailchimp_list_id` varchar(255) NOT NULL DEFAULT '',
  `mailchimp_double_optin` tinyint(1) NOT NULL DEFAULT '1',
  `mailchimp_mergevars` text,
  `mailchimp_text_html_mobile_field` varchar(255) NOT NULL DEFAULT '',
  `mailchimp_send_errors` tinyint(1) NOT NULL DEFAULT '0',
  `mailchimp_default_type` varchar(255) NOT NULL DEFAULT 'text',
  `mailchimp_delete_member` tinyint(1) NOT NULL DEFAULT '0',
  `mailchimp_unsubscribe_field` varchar(255) NOT NULL DEFAULT '',
  `salesforce_token` varchar(255) NOT NULL DEFAULT '',
  `salesforce_username` varchar(255) NOT NULL DEFAULT '',
  `salesforce_password` varchar(255) NOT NULL DEFAULT '',
  `salesforce_type` varchar(255) NOT NULL DEFAULT '',
  `salesforce_fields` text,
  `salesforce_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `dropbox_email` varchar(255) NOT NULL DEFAULT '',
  `dropbox_password` varchar(255) NOT NULL DEFAULT '',
  `dropbox_folder` text,
  `dropbox_submission_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `dropbox_submission_types` varchar(255) NOT NULL DEFAULT 'pdf',
  `autoheight` tinyint(1) NOT NULL DEFAULT '0',
  `package` varchar(30) NOT NULL DEFAULT '',
  `template_code` longtext,
  `template_code_processed` longtext,
  `template_areas` longtext,
  `ordering` int(11) NOT NULL DEFAULT '0',
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `runmode` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `custom_mail_subject` varchar(255) NOT NULL DEFAULT '',
  `mb_custom_mail_subject` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `class1` varchar(30) DEFAULT NULL,
  `class2` varchar(30) DEFAULT NULL,
  `width` int(11) NOT NULL DEFAULT '0',
  `widthmode` tinyint(1) NOT NULL DEFAULT '0',
  `height` int(11) NOT NULL DEFAULT '0',
  `heightmode` tinyint(1) NOT NULL DEFAULT '0',
  `pages` int(11) NOT NULL DEFAULT '1',
  `emailntf` tinyint(1) NOT NULL DEFAULT '1',
  `mb_emailntf` tinyint(1) NOT NULL DEFAULT '1',
  `emaillog` tinyint(1) NOT NULL DEFAULT '1',
  `mb_emaillog` tinyint(1) NOT NULL DEFAULT '1',
  `emailxml` tinyint(1) NOT NULL DEFAULT '0',
  `mb_emailxml` tinyint(1) NOT NULL DEFAULT '0',
  `email_type` tinyint(1) NOT NULL DEFAULT '0',
  `mb_email_type` tinyint(1) NOT NULL DEFAULT '0',
  `email_custom_template` text,
  `mb_email_custom_template` text,
  `email_custom_html` tinyint(1) NOT NULL DEFAULT '0',
  `mb_email_custom_html` tinyint(1) NOT NULL DEFAULT '0',
  `emailadr` text,
  `dblog` tinyint(1) NOT NULL DEFAULT '1',
  `script1cond` tinyint(1) NOT NULL DEFAULT '0',
  `script1id` int(11) DEFAULT NULL,
  `script1code` longtext,
  `script2cond` tinyint(1) NOT NULL DEFAULT '0',
  `script2id` int(11) DEFAULT NULL,
  `script2code` longtext,
  `piece1cond` tinyint(1) NOT NULL DEFAULT '0',
  `piece1id` int(11) DEFAULT NULL,
  `piece1code` longtext,
  `piece2cond` tinyint(1) NOT NULL DEFAULT '0',
  `piece2id` int(11) DEFAULT NULL,
  `piece2code` longtext,
  `piece3cond` tinyint(1) NOT NULL DEFAULT '0',
  `piece3id` int(11) DEFAULT NULL,
  `piece3code` longtext,
  `piece4cond` tinyint(1) NOT NULL DEFAULT '0',
  `piece4id` int(11) DEFAULT NULL,
  `piece4code` longtext,
  `prevmode` tinyint(1) NOT NULL DEFAULT '2',
  `prevwidth` int(11) DEFAULT NULL,
  `double_opt` tinyint(1) NOT NULL DEFAULT '0',
  `opt_mail` varchar(128) NOT NULL DEFAULT '',
  `filter_state` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_9_1 = "ALTER TABLE `#__facileforms_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `double_opt` (`double_opt`),
  ADD KEY `opt_mail` (`opt_mail`),
  ADD KEY `name` (`name`),
  ADD KEY `published` (`published`),
  ADD KEY `ordering` (`ordering`)";

$_9_2 = "ALTER TABLE `#__facileforms_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `opted` (`opted`),
  ADD KEY `opt_ip` (`opt_ip`),
  ADD KEY `opt_date` (`opt_date`),
  ADD KEY `opt_token` (`opt_token`),
  ADD KEY `form` (`form`),
  ADD KEY `name` (`name`),
  ADD KEY `user_id` (`user_id`)";

$_10 = "DROP TABLE IF EXISTS `#__facileforms_elements`";

$_11 = "CREATE TABLE `#__facileforms_elements` (
  `id` int(11) NOT NULL,
  `form` int(11) NOT NULL DEFAULT '0',
  `page` int(11) NOT NULL DEFAULT '1',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(50) NOT NULL DEFAULT '',
  `class1` varchar(30) DEFAULT NULL,
  `class2` varchar(30) DEFAULT NULL,
  `logging` tinyint(1) NOT NULL DEFAULT '1',
  `posx` int(11) DEFAULT NULL,
  `posxmode` tinyint(1) NOT NULL DEFAULT '0',
  `posy` int(11) DEFAULT NULL,
  `posymode` tinyint(1) NOT NULL DEFAULT '0',
  `width` int(11) DEFAULT NULL,
  `widthmode` tinyint(1) NOT NULL DEFAULT '0',
  `height` int(11) DEFAULT NULL,
  `heightmode` tinyint(1) NOT NULL DEFAULT '0',
  `flag1` tinyint(1) NOT NULL DEFAULT '0',
  `flag2` tinyint(1) NOT NULL DEFAULT '0',
  `data1` text,
  `data2` text,
  `data3` text,
  `script1cond` tinyint(1) NOT NULL DEFAULT '0',
  `script1id` int(11) DEFAULT NULL,
  `script1code` text,
  `script1flag1` tinyint(1) NOT NULL DEFAULT '0',
  `script1flag2` tinyint(1) NOT NULL DEFAULT '0',
  `script2cond` tinyint(1) NOT NULL DEFAULT '0',
  `script2id` int(11) DEFAULT NULL,
  `script2code` text,
  `script2flag1` tinyint(1) NOT NULL DEFAULT '0',
  `script2flag2` tinyint(1) NOT NULL DEFAULT '0',
  `script2flag3` tinyint(1) NOT NULL DEFAULT '0',
  `script2flag4` tinyint(1) NOT NULL DEFAULT '0',
  `script2flag5` tinyint(1) NOT NULL DEFAULT '0',
  `script3cond` tinyint(1) NOT NULL DEFAULT '0',
  `script3id` int(11) DEFAULT NULL,
  `script3code` text,
  `script3msg` text,
  `mailback` tinyint(1) NOT NULL DEFAULT '0',
  `mailbackfile` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_12 = "DROP TABLE IF EXISTS `#__facileforms_scripts`";

$_13 = "CREATE TABLE `#__facileforms_scripts` (
  `id` int(11) NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `package` varchar(30) NOT NULL DEFAULT '',
  `name` varchar(30) NOT NULL DEFAULT '',
  `title` varchar(50) NOT NULL DEFAULT '',
  `description` text,
  `type` varchar(30) NOT NULL DEFAULT '',
  `code` longtext,
  `created` datetime NULL DEFAULT NULL,
  `created_by` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `modified` datetime NULL DEFAULT NULL,
  `modified_by` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_14 = "DROP TABLE IF EXISTS `#__facileforms_pieces`";

$_15 = "CREATE TABLE `#__facileforms_pieces` (
  `id` int(11) NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `package` varchar(30) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `type` varchar(30) NOT NULL DEFAULT '',
  `code` longtext,
  `created` datetime NULL DEFAULT NULL,
  `created_by` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `modified` datetime NULL DEFAULT NULL,
  `modified_by` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_16 = "DROP TABLE IF EXISTS `#__facileforms_records`";

$_17 = "CREATE TABLE `#__facileforms_records` (
  `id` int(11) NOT NULL,
  `submitted` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
  `form` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(39) NOT NULL DEFAULT '',
  `browser` varchar(255) NOT NULL DEFAULT '',
  `opsys` varchar(255) NOT NULL DEFAULT '',
  `provider` varchar(255) NOT NULL DEFAULT '',
  `viewed` tinyint(1) NOT NULL DEFAULT '0',
  `exported` tinyint(1) NOT NULL DEFAULT '0',
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `username` varchar(255) NOT NULL DEFAULT '',
  `user_full_name` varchar(255) NOT NULL DEFAULT '',
  `paypal_tx_id` varchar(255) NOT NULL DEFAULT '',
  `paypal_payment_date` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
  `paypal_testaccount` tinyint(1) NOT NULL DEFAULT '0',
  `paypal_download_tries` int(11) NOT NULL DEFAULT '0',
  `opted` tinyint(1) NOT NULL DEFAULT '0',
  `opt_ip` varchar(255) NOT NULL DEFAULT '',
  `opt_date` datetime NULL DEFAULT NULL,
  `opt_token` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_18 = "DROP TABLE IF EXISTS `#__facileforms_subrecords`";
    
$_19 = "CREATE TABLE `#__facileforms_subrecords` (
  `id` int(11) NOT NULL,
  `record` int(11) NOT NULL DEFAULT '0',
  `element` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(255) NOT NULL DEFAULT '',
  `value` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_20 = "DROP TABLE IF EXISTS `#__facileforms_integrator_criteria_fixed`";

$_21 = "CREATE TABLE `#__facileforms_integrator_criteria_fixed` (
  `id` int(11) NOT NULL,
  `rule_id` int(11) DEFAULT NULL,
  `reference_column` varchar(255) DEFAULT NULL,
  `operator` varchar(255) DEFAULT NULL,
  `fixed_value` text,
  `andor` varchar(3) NOT NULL DEFAULT 'AND'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_22 = "DROP TABLE IF EXISTS `#__facileforms_integrator_criteria_form`";

$_23 = "CREATE TABLE `#__facileforms_integrator_criteria_form` (
  `id` int(11) NOT NULL,
  `rule_id` int(11) DEFAULT NULL,
  `reference_column` varchar(255) DEFAULT NULL,
  `operator` varchar(255) DEFAULT NULL,
  `element_id` varchar(255) DEFAULT NULL,
  `andor` varchar(3) NOT NULL DEFAULT 'AND'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_24 = "DROP TABLE IF EXISTS `#__facileforms_integrator_criteria_joomla`";

$_25 = "CREATE TABLE `#__facileforms_integrator_criteria_joomla` (
  `id` int(11) NOT NULL,
  `rule_id` int(11) DEFAULT NULL,
  `reference_column` varchar(255) DEFAULT NULL,
  `operator` varchar(255) DEFAULT NULL,
  `joomla_object` varchar(255) DEFAULT NULL,
  `andor` varchar(3) NOT NULL DEFAULT 'AND'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_26 = "DROP TABLE IF EXISTS `#__facileforms_integrator_items`";

$_27 = "CREATE TABLE `#__facileforms_integrator_items` (
  `id` int(11) NOT NULL,
  `rule_id` int(11) DEFAULT NULL,
  `element_id` int(11) DEFAULT NULL,
  `reference_column` varchar(255) DEFAULT NULL,
  `code` text,
  `published` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_28 = "DROP TABLE IF EXISTS `#__facileforms_integrator_rules`";

$_29 = "CREATE TABLE `#__facileforms_integrator_rules` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `form_id` int(11) DEFAULT NULL,
  `reference_table` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'insert',
  `published` tinyint(1) NOT NULL DEFAULT '1',
  `finalize_code` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$_30 = "ALTER TABLE `#__facileforms_compmenus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (id)";

$_31 = "ALTER TABLE `#__facileforms_config`
  ADD PRIMARY KEY (`id`)";

$_32 = "ALTER TABLE `#__facileforms_elements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form` (`form`),
  ADD KEY `ordering` (`ordering`),
  ADD KEY `published` (`published`)";

$_33 = "ALTER TABLE `#__facileforms_integrator_criteria_fixed`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reference_column` (`reference_column`),
  ADD KEY `rule_id` (`rule_id`)";

$_34 = "ALTER TABLE `#__facileforms_integrator_criteria_form`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rule_id` (`rule_id`),
  ADD KEY `reference_column` (`reference_column`)";

$_35 = "ALTER TABLE `#__facileforms_integrator_criteria_joomla`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rule_id` (`rule_id`),
  ADD KEY `reference_column` (`reference_column`)";

$_36 = "ALTER TABLE `#__facileforms_integrator_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rule_id` (`rule_id`),
  ADD KEY `element_id` (`element_id`),
  ADD KEY `reference_column` (`reference_column`),
  ADD KEY `published` (`published`)";

$_37 = "ALTER TABLE `#__facileforms_integrator_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `form_id` (`form_id`),
  ADD KEY `reference_table` (`reference_table`),
  ADD KEY `published` (`published`)";

$_38 = "ALTER TABLE `#__facileforms_packages`
  ADD PRIMARY KEY (`id`)";

$_39 = "ALTER TABLE `#__facileforms_pieces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `published` (`published`)";

$_40 = "ALTER TABLE `#__facileforms_scripts`
  ADD PRIMARY KEY (`id`)";

$_41 = "ALTER TABLE `#__facileforms_subrecords`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `record` (`record`),
  ADD KEY `element` (`element`),
  ADD KEY `record_2` (`record`,`element`),
  ADD KEY `record_3` (`record`,`name`),
  ADD KEY `record_4` (`record`,`type`)";

$_42 = "ALTER TABLE `#__facileforms_subrecords` ADD FULLTEXT KEY `value` (`value`)";

$_43 = "ALTER TABLE `#__facileforms_elements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";

$_44 = "ALTER TABLE `#__facileforms_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";

$_45 = "ALTER TABLE `#__facileforms_integrator_criteria_fixed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";

$_46 = "ALTER TABLE `#__facileforms_integrator_criteria_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";

$_47 = "ALTER TABLE `#__facileforms_integrator_criteria_joomla`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";

$_48 = "ALTER TABLE `#__facileforms_integrator_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";

$_49 = "ALTER TABLE `#__facileforms_integrator_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";

$_50 = "ALTER TABLE `#__facileforms_pieces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";

$_51 = "ALTER TABLE `#__facileforms_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";

$_52 = "ALTER TABLE `#__facileforms_scripts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";

$_53 = "ALTER TABLE `#__facileforms_subrecords`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";

$db = Factory::getContainer()->get(DatabaseInterface::class);

$db->setQuery($_1);
$db->execute();

$db->setQuery($_2);
$db->execute();

$db->setQuery($_3);
$db->execute();

$db->setQuery($_4);
$db->execute();

$db->setQuery($_5);
$db->execute();

$db->setQuery($_6);
$db->execute();

$db->setQuery($_7);
$db->execute();

$db->setQuery($_8);
$db->execute();

$db->setQuery($_9);
$db->execute();

$db->setQuery($_10);
$db->execute();

$db->setQuery($_11);
$db->execute();

$db->setQuery($_12);
$db->execute();

$db->setQuery($_13);
$db->execute();

$db->setQuery($_14);
$db->execute();

$db->setQuery($_15);
$db->execute();

$db->setQuery($_16);
$db->execute();

$db->setQuery($_17);
$db->execute();

$db->setQuery($_18);
$db->execute();

$db->setQuery($_19);
$db->execute();

$db->setQuery($_20);
$db->execute();

$db->setQuery($_21);
$db->execute();

$db->setQuery($_22);
$db->execute();

$db->setQuery($_23);
$db->execute();

$db->setQuery($_24);
$db->execute();

$db->setQuery($_25);
$db->execute();

$db->setQuery($_26);
$db->execute();

$db->setQuery($_27);
$db->execute();

$db->setQuery($_28);
$db->execute();

$db->setQuery($_29);
$db->execute();

$db->setQuery($_9_1);
$db->execute();

$db->setQuery($_9_2);
$db->execute();

$db->setQuery($_30);
$db->execute();
$db->setQuery($_31);
$db->execute();
$db->setQuery($_32);
$db->execute();
$db->setQuery($_33);
$db->execute();
$db->setQuery($_34);
$db->execute();
$db->setQuery($_35);
$db->execute();
$db->setQuery($_36);
$db->execute();
$db->setQuery($_37);
$db->execute();
$db->setQuery($_38);
$db->execute();
$db->setQuery($_39);
$db->execute();
$db->setQuery($_40);
$db->execute();
$db->setQuery($_41);
$db->execute();
$db->setQuery($_42);
$db->execute();
$db->setQuery($_43);
$db->execute();
$db->setQuery($_44);
$db->execute();
$db->setQuery($_45);
$db->execute();
$db->setQuery($_46);
$db->execute();
$db->setQuery($_47);
$db->execute();
$db->setQuery($_48);
$db->execute();
$db->setQuery($_49);
$db->execute();
$db->setQuery($_50);
$db->execute();
$db->setQuery($_51);
$db->execute();
$db->setQuery($_52);
$db->execute();
$db->setQuery($_53);
$db->execute();
