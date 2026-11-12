<?php
defined('_JEXEC') or die('Direct Access to this location is not allowed.');
/**
 * BreezingForms NG - A Joomla Forms Application
 * 
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

if (!isset($tx_token, $form_id, $record_id, $tries)) {
    throw new \LogicException('Stripe download template context is incomplete.');
}

// The token comes from the request: escaped as text, URL-encoded in the link.
$downloadUrl = Uri::root() . 'index.php?raw=true&option=com_breezingformsng&stripeDownload=true'
    . '&token=' . rawurlencode((string) $tx_token)
    . '&form=' . (int) $form_id . '&record_id=' . (int) $record_id;

?>
<?php echo Text::_('COM_BREEZINGFORMSNG_THANK_YOU_FOR_BUYING'); ?>
<br />
<br />
<?php echo Text::_('COM_BREEZINGFORMSNG_YOUR_TRANSACTION_ID') ?>:
<?php echo htmlspecialchars((string) $tx_token, ENT_QUOTES, 'UTF-8'); ?>
<br />
<?php echo Text::_('COM_BREEZINGFORMSNG_PAYMENT_METHOD_STRIPE') ?>
<br />
<br />
<a
    href="<?php echo htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo Text::_('COM_BREEZINGFORMSNG_DOWNLOAD'); ?> (
    <?php echo Text::_('COM_BREEZINGFORMSNG_ALLOWED_TRIES'); ?>:
    <?php echo (int) $tries; ?>)
</a>
