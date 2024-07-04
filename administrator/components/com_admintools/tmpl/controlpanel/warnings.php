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

$root      = realpath(JPATH_ROOT) ?: '';
$root      = trim($root);
$emptyRoot = empty($root);

if (version_compare(JVERSION, '4.999.999', 'lt'))
{
	$this->loadAnyTemplate('Controlpanel/joomla_eol');
}

?>
<?php if(($this->debugAllPanels ?? false) || isset($this->jwarnings) && !empty($this->jwarnings)): ?>
	<details class="alert alert-danger">
		<summary class="alert-heading fs-4 h4 m-0 p-0">
			<span class="icon-exclamation-triangle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('ERROR'); ?></span>
			<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_ERR_JCONFIG'); ?>
		</summary>
		<p><?= $this->jwarnings ?></p>
	</details>
<?php endif; ?>

<?php if(($this->debugAllPanels ?? false) || isset($this->frontEndSecretWordIssue) && !empty($this->frontEndSecretWordIssue)): ?>
	<details class="alert alert-danger">
		<summary class="alert-heading fs-4 h4 m-0 p-0">
			<span class="icon-exclamation-triangle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('ERROR'); ?></span>
			<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_ERR_FESECRETWORD_HEADER'); ?>
		</summary>
		<p>
			<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_ERR_FESECRETWORD_INTRO'); ?>
		</p>
		<p>
			<?= $this->frontEndSecretWordIssue; ?>
		</p>
		<p>
			<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_ERR_FESECRETWORD_WHATTODO_JOOMLA'); ?>
			<?= Text::sprintf('COM_ADMINTOOLS_CONTROLPANEL_ERR_FESECRETWORD_WHATTODO_COMMON', $this->newSecretWord); ?>
		</p>
		<p>
			<a class="btn btn-success"
			   href="<?= Route::_(sprintf('index.php?option=com_admintools&view=Controlpanel&task=resetSecretWord&%s=1', Factory::getApplication()->getFormToken())) ?>">
				<span class="fa fa-sync-alt"></span>
				<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_BTN_FESECRETWORD_RESET'); ?>
			</a>
		</p>
	</details>
<?php endif; ?>

<?php if (($this->debugAllPanels ?? false) || $emptyRoot): ?>
	<details class="alert alert-danger">
		<summary class="alert-heading fs-4 h4 m-0 p-0">
			<span class='icon-exclamation-triangle' aria-hidden='true'></span><span
					class='visually-hidden'><?php echo Text::_('ERROR'); ?></span>
			<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_EMPTYROOT_HEAD') ?>
		</summary>

		<p>
			<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_EMPTYROOT_BODY'); ?>
		</p>
	</details>
<?php endif; ?>

<?php if(($this->debugAllPanels ?? false) || $this->needsdlid):
	$updateSiteEditUrl = Route::_('index.php?option=com_installer&task=updatesite.edit&update_site_id=' . $this->updateSiteId)
	?>
	<details class="alert alert-info alert-dismissible">
		<summary class="alert-heading fs-4 h4 m-0 p-0">
			<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_MUSTENTERDLID'); ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
		</summary>
		<p>
			<?=Text::sprintf('COM_ADMINTOOLS_CONTROLPANEL_LBL_NEEDSDLID', 'https://www.akeeba.com/download/official/add-on-dlid.html'); ?>
		</p>
		<p>
			<?= Text::sprintf('COM_ADMINTOOLS_CONTROLPANEL_MSG_WHERETOENTERDLID', $updateSiteEditUrl) ?>
		</p>
		<p class="text-muted <?= version_compare(JVERSION, '5.0.999', 'gt') ? 'j51' : '' ?>">
			<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_MSG_JOOMLABUGGYUPDATES') ?>
		</p>
	</details>
<?php endif; ?>

<?php if(($this->debugAllPanels ?? false) || $this->serverConfigEdited): ?>
	<details class="alert alert-warning">
		<summary class="alert-heading fs-4 h4 m-0 p-0">
			<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_SERVERCONFIGWARN_HEAD'); ?>
		</summary>
		<p>
			<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_SERVERCONFIGWARN'); ?>
		</p>
		<p>
			<a href="<?= Route::_('index.php?option=com_admintools&view=Controlpanel&task=regenerateServerConfig') ?>"
			   class="btn btn-success">
				<span class="fa fa-check"></span>
				<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_SERVERCONFIGWARN_REGENERATE'); ?>
			</a>
			<a href="<?= Route::_('index.php?option=com_admintools&view=Controlpanel&task=ignoreServerConfigWarn') ?>"
			   class="btn btn-outline-danger">
				<span class="fa fa-eye-slash"></span>
				<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_SERVERCONFIGWARN_IGNORE'); ?>
			</a>
		</p>
	</details>
<?php endif; ?>
