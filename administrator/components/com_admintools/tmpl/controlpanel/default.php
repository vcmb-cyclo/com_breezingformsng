<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var  \Akeeba\Component\AdminTools\Administrator\View\Controlpanel\HtmlView $this */

$showGraphs = $this->isPro && $this->showstats;

?>
<?= $this->loadAnyTemplate('Controlpanel/warnings') ?>

<div class="container pt-2 pb-3 px-2">
	<div class="row align-items-start">
		<div class="col-lg-<?= $showGraphs ? '6' : '12' ?>">
			<?php if(($this->debugAllPanels ?? false) || $this->isRescueMode): ?>
				<div class="alert alert-danger">
					<h3 class="alert-heading">
						<span class="icon-exclamation-triangle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('ERROR'); ?></span>
						<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_RESCUEMODE_HEAD'); ?>
					</h3>
					<p>
						<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_RESCUEMODE_MESSAGE'); ?>
					</p>
					<div class="d-flex flex-row gap-2 flex-wrap">
						<a class="btn btn-info"
						   href="https://www.akeeba.com/documentation/troubleshooter/atwafissues.html"
						   target="_blank"
						>
							<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_RESCUEMODE_BTN_HOWTOUNBLOCK') ?>
						</a>
						<a class="btn btn-danger btn-lg"
						   href="<?= Route::_('index.php?option=com_admintools&view=Controlpanel&task=endRescue') ?>"
						>
							<span class="fa fa-flag-checkered"></span>
							<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_RESCUEMODE_BTN_ENDRESCUE'); ?>
						</a>
					</div>
				</div>
			<?php else: ?>
				<?= $this->loadAnyTemplate('Controlpanel/plugin_warning') ?>
			<?php endif; ?>

			<?php if ($showGraphs): ?>
			<div id="selfBlocked" class="text-center my-2" style="display: none;">
				<a class="btn btn-success btn-lg"
				   href="<?= Route::_('index.php?option=com_admintools&view=Controlpanel&task=unblockme'); ?>">
					<span class="fa fa-unlock-alt"></span>
					<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_UNBLOCK_ME'); ?>
				</a>
			</div>
			<?php endif ?>

			<?php if ( ! ($this->hasValidPassword)): ?>
				<?= $this->loadAnyTemplate('Controlpanel/mainpassword'); ?>
			<?php endif; ?>

			<?= $this->loadAnyTemplate('Controlpanel/security') ?>
			<?= $this->loadAnyTemplate('Controlpanel/tools') ?>

			<?php if(ADMINTOOLS_PRO && !$this->needsQuickSetup): ?>
				<?= $this->loadAnyTemplate('Controlpanel/quicksetup') ?>
			<?php endif; ?>
		</div>

		<?php if($this->isPro && $this->showstats): ?>
		<div class="col-lg-6">
			<?php echo $this->loadAnyTemplate('Controlpanel/graphs') ?>
			<?php echo $this->loadAnyTemplate('Controlpanel/stats') ?>
		</div>
		<?php else: ?>
			<?php $this->document->addScriptOptions('admintools.Controlpanel.graphs', 0); ?>
		<?php endif; ?>
	</div>

	<div class="card mb-3">
		<h3 class="card-header bg-dark text-white">
			<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_INFORMATION'); ?>
		</h3>

		<div class="card-body">
			<div class="row align-items-start">
				<div class="col-lg-6">
					<div class="mb-3">
						<div>
							Admin Tools
							<?= (defined('ADMINTOOLS_PRO') ? ADMINTOOLS_PRO : 0) ? 'Professional' : 'Core' ?>
							<?= defined('ADMINTOOLS_VERSION') ? ADMINTOOLS_VERSION : 'dev' ?>
						</div>
						<div>
							<?= Text::sprintf('COM_ADMINTOOLS_CONTROLPANEL_LBL_RELEASED_ON', HTMLHelper::_('admintools.formatDate', clone Factory::getDate(defined('ADMINTOOLS_DATE') ? ADMINTOOLS_DATE : gmdate('Y-m-d')), false, Text::_('DATE_FORMAT_LC3'))) ?>
						</div>
					</div>

					<p class="text-muted">
						Copyright &copy; 2010&ndash;<?=date('Y'); ?> Nicholas K. Dionysopoulos /
						<a href="https://www.akeeba.com" target="_blank">Akeeba Ltd</a>
					</p>
				</div>
				<div class="col-lg-6">
					<div class="d-flex flex-column">
						<button type="button"
								id="btnchangelog" class="btn btn-outline-primary mb-2 me-2"
								data-bs-toggle="modal" data-bs-target="#akeeba-changelog">
							<span class="fa fa-clipboard-check"></span>
							CHANGELOG
						</button>

						<?php if ( ($this->debugAllPanels ?? false) || ! ($this->isPro)): ?>
							<div class="text-center mb-4">
								<a href="https://www.paypal.com/donate?hosted_button_id=6ZLKK32UVEPWA"
								   id="btndonation"
								   class="btn btn-outline-primary">
									<span class="fa fab fa-paypal"></span>
									<?= Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_DONATE') ?>
								</a>
							</div>
						<?php endif; ?>

						<p class="text-muted">
							If you use Admin Tools <?=ADMINTOOLS_PRO ? 'Professional' : 'Core'; ?>, please post a rating and
							a review at the <a target="_blank" href="http://extensions.joomla.org/extensions/extension/access-a-security/site-security/admin-tools<?=ADMINTOOLS_PRO ? '-professional' : ''; ?>">
								Joomla! Extensions Directory</a>.
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div id="disclaimer" class="card mb-3">
		<h3 class="card-header bg-info text-white">
			<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_DISCLAIMER'); ?>
		</h3>

		<div class="card-body">
			<p class="text-muted">
				<?=Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_DISTEXT'); ?>
			</p>
		</div>
	</div>
</div>

<div class="modal fade" id="akeeba-changelog" tabindex="-1"
	 aria-labelledby="akeeba-changelog-header" aria-hidden="true"
	 role="dialog">
	<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h3 id="akeeba-changelog-header">
					<?= Text::_('CHANGELOG') ?>
				</h3>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
			</div>
			<div class="modal-body p-3">
				<?= $this->formattedChangelog ?>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="admintools-dialog" tabindex="-1"
	 aria-labelledby="admintools-dialog-header" aria-hidden="true"
	 role="dialog">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h3 id="admintools-dialog-header"></h3>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
			</div>
			<div class="modal-body p-3" id="admintools-dialog-body"></div>
		</div>
	</div>
</div>