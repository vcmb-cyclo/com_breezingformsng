<?php
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Language\Text;
/**
* BreezingForms - A Joomla Forms Application
* @package BreezingFormsNG
* @copyright (C) 2008-2020 by Markus Bopp
* @license Released under the terms of the GNU General Public License
 **/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');
if (!isset($tx_token, $msg)) {
    throw new \LogicException('Payment error template context is incomplete.');
}
?>
<?php echo Text::_('COM_BREEZINGFORMSNG_PAYMENT_ERROR_MSG'); ?>
<br/>
<br/>
<?php echo Text::_('COM_BREEZINGFORMSNG_YOUR_TRANSACTION_ID')  ?>: <?php echo htmlentities($tx_token); ?>
<br/>
<br/>
<?php echo Text::_('COM_BREEZINGFORMSNG_ERROR')  ?>: <?php echo htmlentities($msg); ?>
