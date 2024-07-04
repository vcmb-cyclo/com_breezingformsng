<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var  \Akeeba\Component\AdminTools\Administrator\View\Controlpanel\HtmlView $this */

$returnUrl = base64_encode('index.php?option=com_admintools&view=' . $this->getName());

?>

<?php if(!$this->pluginExists): ?>
	<div class="alert alert-danger small">
		<span class="fa fa-exclamation-triangle" aria-hidden="true"></span>
		<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_ERR_NOPLUGINEXISTS'); ?>
	</div>
<?php elseif(!$this->pluginActive): ?>
	<div class="alert alert-danger small">
		<p>
			<span class="fa fa-exclamation-triangle" aria-hidden="true"></span>
			<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_ERR_NOPLUGINACTIVE'); ?>
		</p>
		<a class="btn btn-primary text-light small"
		   href="<?= Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $this->pluginid) ?>">
			<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_ERR_NOPLUGINACTIVE_DOIT'); ?>
		</a>
	</div>
<?php elseif($this->isMainPhpDisabled && !empty($this->mainPhpRenamedTo)): ?>
	<div class="alert alert-danger small">
		<p>
			<span class="fa fa-exclamation-triangle" aria-hidden="true"></span>
			<?=Text::sprintf('COM_ADMINTOOLS_CONFIGUREWAF_ERR_MAINPHPRENAMED_KNOWN', $this->mainPhpRenamedTo); ?>
		</p>
		<a class="btn btn-primary small text-light"
		   href="<?= Route::_(sprintf('index.php?option=com_admintools&view=Controlpanel&task=renameMainPhp&%s=1', Factory::getApplication()->getFormToken())) ?>">
			<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_ERR_MAINPHPRENAMED_DOIT'); ?>
		</a>
	</div>
<?php elseif($this->isMainPhpDisabled): ?>
	<p class="alert alert-danger small">
		<span class="fa fa-exclamation-triangle" aria-hidden="true"></span>
		<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_ERR_MAINPHPRENAMED_UNKNOWN'); ?>
	</p>
<?php elseif(!$this->pluginLoaded && !$this->isRescueMode): ?>
	<p class="alert alert-danger small">
		<span class="fa fa-exclamation-triangle" aria-hidden="true"></span>
		<?=Text::_('COM_ADMINTOOLS_CONFIGUREWAF_ERR_PLUGINNOTLOADED'); ?>
	</p>
<?php endif; ?>
