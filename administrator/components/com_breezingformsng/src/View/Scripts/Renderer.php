<?php
/**
 * BreezingForms NG - A Joomla Forms Application
 * 
 * @version 6.0.0
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Scripts;

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\BreadcrumbHelper;

class Renderer
{
	private const AREA_SMALL = 4;
	private const AREA_MEDIUM = 12;
	private const AREA_LARGE = 20;
	private const DESCRIPTION_LIMIT = 100;
	private static function registerEditLabels(): void
	{
		$keys = [
			'COM_BREEZINGFORMSNG_SCRIPTS_CREATEACTCODE',
			'COM_BREEZINGFORMSNG_SCRIPTS_CREATEFINICODE',
			'COM_BREEZINGFORMSNG_SCRIPTS_CREATEINICODE',
			'COM_BREEZINGFORMSNG_SCRIPTS_CREATESUBCODE',
			'COM_BREEZINGFORMSNG_SCRIPTS_CREATEUNTCODE',
			'COM_BREEZINGFORMSNG_SCRIPTS_CREATEVALCODE',
			'COM_BREEZINGFORMSNG_SCRIPTS_ENTERIDENT',
			'COM_BREEZINGFORMSNG_SCRIPTS_ENTERNAME',
			'COM_BREEZINGFORMSNG_SCRIPTS_ENTNAMEFIRST',
			'COM_BREEZINGFORMSNG_SCRIPTS_ENTTITLE',
			'COM_BREEZINGFORMSNG_SCRIPTS_EXISTAPP',
			'COM_BREEZINGFORMSNG_SCRIPTS_OLDBELOW',
			'COM_BREEZINGFORMSNG_SCRIPTS_UNKNOWNTYPE',
			'COM_BREEZINGFORMSNG_TEST_ENTER_FUNCTION_NAME',
			'COM_BREEZINGFORMSNG_TEST_NO_CHANGES',
			'COM_BREEZINGFORMSNG_TEST_NO_UNIT_TEST_DEFINED',
			'COM_BREEZINGFORMSNG_TEST_OUTPUT',
			'COM_BREEZINGFORMSNG_TEST_PASSED_SHORT',
			'COM_BREEZINGFORMSNG_TEST_SAVE_FIRST_SCRIPT',
			'COM_BREEZINGFORMSNG_TEST_SAVE_SCRIPT_BEFORE_CONTINUE',
			'COM_BREEZINGFORMSNG_TEST_SAVE_SCRIPT_BEFORE_NAVIGATION',
			'COM_BREEZINGFORMSNG_TEST_SAVE_SCRIPT_BEFORE_TESTS',
			'COM_BREEZINGFORMSNG_TEST_SAVE_SCRIPT_BEFORE_UNIT_TESTS',
			'COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_NONE',
		];

		foreach ($keys as $key) {
			Text::script($key);
		}
	}

	private static function registerTestLabels(): void
	{
		$keys = [
			'COM_BREEZINGFORMSNG_TEST_ENTER_FUNCTION_NAME_TO_TEST',
			'COM_BREEZINGFORMSNG_TEST_INVALID_FUNCTION_NAME',
			'COM_BREEZINGFORMSNG_TEST_NO_UNIT_TEST_DEFINED',
			'COM_BREEZINGFORMSNG_TEST_OUTPUT',
			'COM_BREEZINGFORMSNG_TEST_PASSED_SHORT',
			'COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES_ON_OPEN',
			'COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES_1',
			'COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES_MORE',
			'COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_NONE',
		];

		foreach ($keys as $key) {
			Text::script($key);
		}
	}

	static function edit($option, $pkg, &$row, &$typelist)
	{
		/** @var CMSApplication $app */
		$app = Factory::getApplication();
		$app->getInput()->set('hidemainmenu', 1);
		$action = $row->id ? Text::_('COM_BREEZINGFORMSNG_SCRIPTS_EDITSCRIPT') : Text::_('COM_BREEZINGFORMSNG_SCRIPTS_ADDSCRIPT');

		$pageTitle = BreadcrumbHelper::render([
			['label' => Text::_('COM_BREEZINGFORMSNG'), 'url' => 'index.php?option=com_breezingformsng'],
			['label' => Text::_('COM_BREEZINGFORMSNG_MANAGESCRIPTS'), 'url' => 'index.php?option=com_breezingformsng&view=scripts'],
			['label' => $row->id && $row->name !== '' ? (string) $row->name : Text::_('COM_BREEZINGFORMSNG_SCRIPTS_ADDSCRIPT')],
		]);
		$app->getDocument()->setTitle(strip_tags($pageTitle));
		ToolbarHelper::title($pageTitle, 'logo_left');

		$hasPersistedUnitTests = $row->id && trim((string) $row->unit_tests) !== '';
		$safePersistedUnitTests = json_encode((string) $row->unit_tests);
		$initialState = array(
			'title' => (string) $row->title,
			'type' => (string) $row->type,
			'package' => (string) $row->package,
			'name' => (string) $row->name,
			'published' => (string) $row->published,
			'description' => (string) $row->description,
			'code' => (string) $row->code,
			'unit_tests' => (string) $row->unit_tests
		);
		$safeInitialState = json_encode($initialState);
		$unitTestsHelp = Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_HELP');
		HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
		if ($row->id) {
			ToolBarHelper::custom('scripts.previous', 'arrow-left', '', Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGEPREV'), false);
			ToolBarHelper::custom('scripts.next', 'arrow-right', '', Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGENEXT'), false);
			ToolBarHelper::custom('scripts.test', 'eye', '', Text::_('COM_BREEZINGFORMSNG_TEST'), false);
		}
		ToolBarHelper::custom('scripts.save', 'save.png', 'save_f2.png', Text::_('COM_BREEZINGFORMSNG_TOOLBAR_SAVE'), false);
		ToolBarHelper::custom('scripts.cancel', 'cancel.png', 'cancel_f2.png', Text::_('COM_BREEZINGFORMSNG_TOOLBAR_QUICKMODE_CLOSE'), false);
		?>
		<?php
		$document = $app->getDocument();
		$document->getWebAssetManager()->useScript('com_breezingformsng.scripts-edit');
		$document->addScriptOptions('com_breezingformsng.scripts-edit', [
			'initialState' => $initialState,
			'persistedUnitTests' => (string) $row->unit_tests,
			'hasSavedRecord' => (bool) $row->id,
		]);
		self::registerEditLabels();
		?>
		<form action="index.php?option=<?php echo htmlspecialchars($option, ENT_QUOTES); ?>&amp;view=scripts" method="post" name="adminForm" id="adminForm" class="adminForm">
			<table cellpadding="4" cellspacing="1" border="0" class="adminform" style="width:100%;">
				<tr>
					<td></td>
					<td nowrap>
						<?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_TITLE'); ?>:
					</td>
					<td nowrap>
						<input type="text" size="70" maxlength="50" id="title" name="title" value="<?php echo $row->title; ?>"
							class="inputbox" />
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_SCRIPTS_TIPTITLE')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
					</td>
					<td nowrap>
						<?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_TYPE'); ?>:
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_SCRIPTS_TIPTYPE')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
						<select id="type" name="type" class="inputbox" size="1">
							<?php
							for ($t = 0; $t < count($typelist); $t++) {
								$tl = $typelist[$t];
								$selected = '';
								if ($tl[0] == $row->type)
									$selected = ' selected';
								echo '<option value="' . $tl[0] . '"' . $selected . '>' . $tl[1] . '</option>';
							} // for
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td></td>
					<td nowrap>
						<?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_PACKAGE'); ?>:
					</td>
					<td nowrap>
						<input type="text" size="30" maxlength="30" id="package" name="package"
							value="<?php echo $row->package; ?>" class="inputbox" />
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_SCRIPTS_TIPPACKAGE')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
					</td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td nowrap>
						<?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_NAME'); ?>:
					</td>
					<td nowrap>
						<input type="text" size="30" maxlength="30" id="name" name="name" value="<?php echo $row->name; ?>"
							class="inputbox" />
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_SCRIPTS_TIPNAME')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
					</td>
					<td nowrap>
						<?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_PUBLISHED'); ?>:
						<?php echo HTMLHelper::_('select.booleanlist', "published", "", $row->published); ?>
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_SCRIPTS_TIPPUBLISHED')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
					</td>
				</tr>
				<tr>
					<td></td>
					<td nowrap colspan="3">
						<?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_DESCRIPTION'); ?>:
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_SCRIPTS_TIPDESCRIPTION')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
						<button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('description').rows = <?php echo self::AREA_SMALL; ?>;">[<?php echo self::AREA_SMALL; ?>]</button>
						<button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('description').rows = <?php echo self::AREA_MEDIUM; ?>;">[<?php echo self::AREA_MEDIUM; ?>]</button>
						<button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('description').rows = <?php echo self::AREA_LARGE; ?>;">[<?php echo self::AREA_LARGE; ?>]</button>
						<br />
						<textarea wrap="off" name="description" id="description" style="width:100%;" rows="12"
							class="inputbox"><?php echo $row->description; ?></textarea>
					</td>
				</tr>

				<tr>
					<td></td>
					<td nowrap colspan="3">
						<?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_CODE'); ?>:
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_SCRIPTS_TIPCODE')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
						<br />

						<?php
						$params = array('syntax' => 'javascript');
						$editor = Editor::getInstance('codemirror');
						echo $editor->display('code', $row->code, '100%', 300, 40, 20, false, 'code', null, null, $params);
						?>

					</td>
				</tr>
				<tr>
					<td></td>
					<td nowrap colspan="3">
						<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>:
						<?php
						echo '<span><span title="' . htmlspecialchars($unitTestsHelp, ENT_QUOTES) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
						<button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('unit_tests').rows = <?php echo self::AREA_SMALL; ?>;">[<?php echo self::AREA_SMALL; ?>]</button>
						<button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('unit_tests').rows = <?php echo self::AREA_MEDIUM; ?>;">[<?php echo self::AREA_MEDIUM; ?>]</button>
						<button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('unit_tests').rows = <?php echo self::AREA_LARGE; ?>;">[<?php echo self::AREA_LARGE; ?>]</button>
						<br />
						<textarea wrap="off" name="unit_tests" id="unit_tests" style="width:100%;" rows="8"
							class="inputbox"><?php echo htmlspecialchars((string) $row->unit_tests, ENT_QUOTES); ?></textarea>
						<div class="mt-2 text-muted">
							<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_FORMAT_HINT'); ?>:
							<code>'12/ 02/2023 ' -> '12/02/2023'</code><br />
							<code>' abc ' -> 'abc'</code><br />
							<code>'' -> ''</code><br />
							<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_TYPES_HINT'); ?>
						</div>
						<div class="mt-3">
							<button
								type="button"
								id="bf-edit-unit-tests-button"
								class="btn btn-secondary"
								onclick="return runUnitTestsFromEdit();"
								<?php echo $hasPersistedUnitTests ? '' : 'disabled="disabled" aria-disabled="true" title="' . ($row->id ? htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_NONE'), ENT_QUOTES) : htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_TEST_SAVE_FIRST_SCRIPT'), ENT_QUOTES)) . '"'; ?>>
								<span class="icon-play" aria-hidden="true"></span>
								<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>
							</button>
						</div>
						<div id="bf-edit-script-unit-tests-status" class="alert mt-3" style="display:none;">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>:</strong>
							<div id="bf-edit-script-unit-tests-summary"></div>
							<div id="bf-edit-script-unit-tests-details-wrap" style="display:none;">
								<div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_DETAIL'); ?>:</strong></div>
								<pre id="bf-edit-script-unit-tests-details"></pre>
							</div>
						</div>
					</td>
				</tr>
			</table>
			<input type="hidden" name="pkg" value="<?php echo $pkg; ?>" />
			<input type="hidden" name="id" value="<?php echo $row->id; ?>" />
			<input type="hidden" name="option" value="<?php echo $option; ?>" />
			<input type="hidden" name="view" value="scripts" />
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="test_mode" value="" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
		<?php
	} // edit

	static function typeName($type)
	{
		switch ($type) {
			case 'Untyped':
				return Text::_('COM_BREEZINGFORMSNG_SCRIPTS_UNTYPED');
			case 'Element Init':
				return Text::_('COM_BREEZINGFORMSNG_SCRIPTS_ELEMENTINIT');
			case 'Element Action':
				return Text::_('COM_BREEZINGFORMSNG_SCRIPTS_ELEMENTACTION');
			case 'Element Validation':
				return Text::_('COM_BREEZINGFORMSNG_SCRIPTS_ELEMENTVALID');
			case 'Form Init':
				return Text::_('COM_BREEZINGFORMSNG_SCRIPTS_FORMINIT');
			case 'Form Submitted':
				return Text::_('COM_BREEZINGFORMSNG_SCRIPTS_FORMSUBMIT');
			default:
				;
		} // switch
		return '???';
	} // typeName

	static function listitems($option, &$rows, &$pkglist, $pkg, $search, $total, $limit, $limitstart, $pagination = null, $listOrder = 'a.name', $listDirn = 'asc', $filterState = '')
	{
		Factory::getApplication()->getInput()->set('hidemainmenu', 0);
		$listOrder = (string) $listOrder;
		$listDirn = strtolower((string) $listDirn);
		$listDirn = $listDirn === 'desc' ? 'desc' : 'asc';
		?>
		<?php
		Text::script('COM_BREEZINGFORMSNG_SCRIPTS_SELSCRIPTSFIRST');
		Text::script('COM_BREEZINGFORMSNG_SCRIPTS_ASKDELETE');
		?>
		<form action="index.php?option=<?php echo htmlspecialchars($option, ENT_QUOTES); ?>&amp;view=scripts" method="post" name="adminForm" id="adminForm">

			<label class="bfPackageSelector">

				<?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_PACKAGE'); ?>
					<select id="pkgsel" name="pkgsel" class="inputbox" size="1" onchange="return bfScriptsSubmitList(true);">
					<?php
					if (count($pkglist))
						foreach ($pkglist as $pkgEntry) {
							$selected = '';
							if ($pkgEntry[0])
								$selected = ' selected';
							$label = $pkgEntry[1] === '' ? Text::_('COM_BREEZINGFORMSNG_ALL_FILTER') : $pkgEntry[1];
							echo '<option value="' . $pkgEntry[1] . '"' . $selected . '>' . $label . '&nbsp;</option>';
						} // foreach
					?>
				</select>

			</label>
				<label class="bfPackageSelector bfFilterTools">
					<?php echo Text::_('COM_BREEZINGFORMSNG_FILTER'); ?>
					<input type="text" name="search" id="search" class="inputbox"
						value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>" onchange="return bfScriptsSubmitList(true);"
						onkeydown="if(event.key==='Enter'){event.preventDefault();bfScriptsSubmitList(true);}" />
				</label>
				<label class="bfPackageSelector">
					<select name="filter_state" id="filter_state" class="inputbox form-select form-select-sm"
						onchange="return bfScriptsSubmitList(true);">
						<option value=""><?php echo Text::_('JOPTION_SELECT_PUBLISHED'); ?></option>
						<option value="P"<?php echo $filterState === 'P' ? ' selected="selected"' : ''; ?>><?php echo Text::_('JPUBLISHED'); ?></option>
						<option value="U"<?php echo $filterState === 'U' ? ' selected="selected"' : ''; ?>><?php echo Text::_('JUNPUBLISHED'); ?></option>
					</select>
				</label>
			<div style="clear: both;"></div>

				<div class="bf-manage-list-pagination-container table-responsive" id="bfScriptsPaginationContainer">
				<table class="adminlist table table-striped" id="bfScriptsList" data-name="breezingformsng-scripts">
				<thead>
				<tr>
					<th class="w-1 text-nowrap">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
					</th>
					<th class="w-1 text-center"><input class="form-check-input" type="checkbox" name="toggle" value=""
							onclick="Joomla.checkAll(this);" /></th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_SCRIPTS_PACKAGE', 'a.package', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_SCRIPTS_TITLE', 'a.title', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_SCRIPTS_NAME', 'a.name', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_SCRIPTS_TYPE', 'a.type', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_SCRIPTS_DESCRIPTION', 'a.description', $listDirn, $listOrder); ?>
					</th>
					<th class="text-nowrap">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_MODIFIED', 'a.modified', $listDirn, $listOrder); ?>
					</th>
					<th class="w-1 text-center">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_SCRIPTS_PUBLISHED', 'a.published', $listDirn, $listOrder); ?>
					</th>
				</tr>
				</thead>
				<tbody>
				<?php if (count($rows) === 0) { ?>
					<tr><td colspan="9" class="text-center text-muted py-4"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></td></tr>
				<?php } else {
				for ($i = 0; $i < count($rows); $i++) {
					$row = $rows[$i];
					$desc = $row->description;
					if (strlen($desc) > self::DESCRIPTION_LIMIT)
						$desc = substr($desc, 0, self::DESCRIPTION_LIMIT) . '...';
					$editLink = 'index.php?option=' . htmlspecialchars($option, ENT_QUOTES) . '&amp;view=scripts&amp;task=scripts.edit&amp;pkg=' . urlencode($pkg) . '&amp;ids[]=' . (int) $row->id;
					?>
					<tr>
						<td class="text-nowrap">
							<?php echo (int) $row->id; ?>
						</td>
						<td class="text-center"><input class="form-check-input" type="checkbox" id="cb<?php echo $i; ?>" name="ids[]"
								value="<?php echo $row->id; ?>" onclick="Joomla.isChecked(this.checked);" /></td>
						<td>
							<?php echo htmlspecialchars((string) $row->package, ENT_QUOTES); ?>
						</td>
						<td><a href="<?php echo $editLink; ?>"><?php echo htmlspecialchars((string) $row->title, ENT_QUOTES); ?></a></td>
						<td>
							<a href="<?php echo $editLink; ?>"><?php echo htmlspecialchars((string) $row->name, ENT_QUOTES); ?></a>
						</td>
						<td>
							<?php echo self::typeName($row->type); ?>
						</td>
						<td>
							<?php echo htmlspecialchars($desc, ENT_QUOTES); ?>
						</td>
						<td class="text-nowrap">
							<?php
							$lastModified = $row->modified ?? $row->created ?? '';
							echo $lastModified ? HTMLHelper::date($lastModified, 'Y-m-d H:i') : '-';
							?>
						</td>
						<td class="text-center">
							<?php
							if ($row->published == "1") {
								?><a class="tbody-icon active" href="javascript:void(0);"
									onClick="return listItemTask('cb<?php echo $i; ?>','unpublish')"><span class="icon-publish"
										aria-hidden="true"></span></a>
								<?php
							} else {
								?><a class="tbody-icon" href="javascript:void(0);"
									onClick="return listItemTask('cb<?php echo $i; ?>','publish')"><span class="icon-unpublish"
										aria-hidden="true"></span></a>
								<?php
							} // if
							?>
						</td>
					</tr>
					<?php
				} // for
				} // if count ?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="9">
							<?php echo $pagination ? $pagination->getPaginationLinks('joomla.pagination.links', ['showLimitBox' => true]) : ''; ?>
						</td>
					</tr>
				</tfoot>
				</table>
				</div>
				<input type="hidden" name="boxchecked" value="0" />
				<input type="hidden" name="option" value="<?php echo $option; ?>" />
				<input type="hidden" name="view" value="scripts" />
				<input type="hidden" name="task" value="" />
				<input type="hidden" name="pkg" value="<?php echo htmlspecialchars($pkg, ENT_QUOTES); ?>" />
				<input type="hidden" name="filter_order" value="<?php echo htmlspecialchars($listOrder, ENT_QUOTES); ?>" />
				<input type="hidden" name="filter_order_Dir" value="<?php echo htmlspecialchars($listDirn, ENT_QUOTES); ?>" />
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		<?php
	} // listitems

	static function test($option, $pkg, &$row, $functionName, $paramNames, $paramDefaults, $autoRun = false, $testMode = '')
	{
		/** @var CMSApplication $app */
		$app = Factory::getApplication();
		$app->getInput()->set('hidemainmenu', 1);
		ToolBarHelper::custom('scripts.edit', 'undo', '', Text::_('COM_BREEZINGFORMSNG_TEST_BACK'), false);
		ToolBarHelper::custom('scripts.previous', 'arrow-left', '', Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGEPREV'), false);
		ToolBarHelper::custom('scripts.next', 'arrow-right', '', Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGENEXT'), false);
		$document = $app->getDocument();
		$document->getWebAssetManager()->useScript('com_breezingformsng.scripts-test');
		$document->addScriptOptions('com_breezingformsng.scripts-test', [
			'code' => (string) $row->code,
			'functionName' => (string) $functionName,
			'unitTests' => (string) $row->unit_tests,
			'testMode' => (string) $testMode,
			'autoRun' => (bool) $autoRun,
			'hasUnitTests' => trim((string) $row->unit_tests) !== '',
		]);
		self::registerTestLabels();
		?>
		<form action="index.php?option=<?php echo htmlspecialchars($option, ENT_QUOTES); ?>&amp;view=scripts" method="post" name="adminForm" id="adminForm" class="adminForm">
			<div id="bf-script-auto-unit-warning" class="alert alert-warning" style="display:none;">
				<span class="icon-warning text-warning" aria-hidden="true"></span>
				<span id="bf-script-auto-unit-warning-text"></span>
			</div>
			<div class="d-flex justify-content-between align-items-center mb-3">
				<h2 class="m-0"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_SCRIPT'); ?></h2>
				<button type="button" class="btn btn-primary" onclick="return bfRunAllScriptTests();">
					<span class="icon-play" aria-hidden="true"></span>
					<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RUN'); ?>
				</button>
			</div>
			<h3><?php echo htmlspecialchars((string) $row->title, ENT_QUOTES); ?></h3>
			<div class="card mb-3 bg-light">
				<div class="card-body">
					<div class="row">
						<div class="col-sm-6 col-md-3">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_SCRIPT_ID'); ?>:</strong> <?php echo (int) $row->id; ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_PACKAGE'); ?>:</strong> <?php echo htmlspecialchars((string) $row->package, ENT_QUOTES); ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_NAME'); ?>:</strong> <?php echo htmlspecialchars((string) $row->name, ENT_QUOTES); ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_TYPE'); ?>:</strong> <?php echo htmlspecialchars(self::typeName((string) $row->type), ENT_QUOTES); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="card mb-3 bg-light">
				<div class="card-body">
					<div class="row">
						<div class="col-sm-6 col-md-3">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_CREATED'); ?>:</strong> <?php echo !empty($row->created) ? HTMLHelper::date($row->created, 'Y-m-d H:i', true) : '-'; ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_CREATED_BY'); ?>:</strong> <?php echo htmlspecialchars((string) ($row->created_by ?? ''), ENT_QUOTES); ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_MODIFIED'); ?>:</strong> <?php echo !empty($row->modified) ? HTMLHelper::date($row->modified, 'Y-m-d H:i', true) : '-'; ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_MODIFIED_BY'); ?>:</strong> <?php echo htmlspecialchars((string) ($row->modified_by ?? ''), ENT_QUOTES); ?>
						</div>
					</div>
				</div>
			</div>
			<?php if (!empty($row->description)) { ?>
				<div class="card mb-3">
					<div class="card-header"><?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_DESCRIPTION'); ?></div>
					<div class="card-body">
						<div class="form-control bg-light" style="white-space: pre-wrap;">
							<?php echo HTMLHelper::_('content.prepare', $row->description); ?>
						</div>
					</div>
				</div>
			<?php } ?>
			<div class="accordion" id="bfScriptCodeAccordion">
				<div class="accordion-item bg-light">
					<h2 class="accordion-header" id="bfScriptCodeHeading">
						<button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse"
							data-bs-target="#bfScriptCodeCollapse" aria-expanded="false" aria-controls="bfScriptCodeCollapse">
							<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_SCRIPT_CODE'); ?>
						</button>
					</h2>
					<div id="bfScriptCodeCollapse" class="accordion-collapse collapse" aria-labelledby="bfScriptCodeHeading"
						data-bs-parent="#bfScriptCodeAccordion">
						<div class="accordion-body bg-light">
							<pre><?php echo htmlspecialchars((string) $row->code, ENT_QUOTES); ?></pre>
						</div>
					</div>
				</div>
			</div>
			<?php if (trim((string) $row->unit_tests) !== '') { ?>
				<div class="accordion mt-3" id="bfScriptUnitTestsAccordion">
					<div class="accordion-item bg-light">
						<h2 class="accordion-header" id="bfScriptUnitTestsHeading">
							<button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse"
								data-bs-target="#bfScriptUnitTestsCollapse" aria-expanded="false" aria-controls="bfScriptUnitTestsCollapse">
								<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>
							</button>
						</h2>
						<div id="bfScriptUnitTestsCollapse" class="accordion-collapse collapse" aria-labelledby="bfScriptUnitTestsHeading"
							data-bs-parent="#bfScriptUnitTestsAccordion">
							<div class="accordion-body bg-light">
								<pre><?php echo htmlspecialchars((string) $row->unit_tests, ENT_QUOTES); ?></pre>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
			<div class="card mb-3 bg-light">
				<div class="card-body">
					<label for="bf-script-function"><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_FUNCTION'); ?></strong></label>
					<input type="text" id="bf-script-function" class="form-control" value="<?php echo htmlspecialchars((string) $functionName, ENT_QUOTES); ?>" />
					<small class="text-muted"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_VALUES_HELP'); ?></small>
				</div>
			</div>
			<div class="card mb-3">
				<div class="card-header"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_ARGUMENTS'); ?></div>
				<div class="card-body">
					<table cellpadding="4" cellspacing="0" border="0" class="adminlist table table-striped">
						<tr>
							<th><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PARAMETER'); ?></th>
							<th><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_VALUE'); ?></th>
							<th></th>
						</tr>
						<?php if (!count($paramNames)) { ?>
							<tr>
								<td colspan="3"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_NO_PARAMETER_DETECTED'); ?></td>
							</tr>
						<?php } else { ?>
							<?php $lastParamIndex = count($paramNames) - 1; ?>
							<?php for ($i = 0; $i < count($paramNames); $i++) { ?>
								<?php
								$name = $paramNames[$i];
								$default = isset($paramDefaults[$i]) ? $paramDefaults[$i] : '';
								?>
								<tr>
									<td><?php echo htmlspecialchars($name, ENT_QUOTES); ?></td>
									<td>
										<input
											type="text"
											class="inputbox bf-test-arg"
											data-param="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>"
											value="<?php echo htmlspecialchars($default, ENT_QUOTES); ?>" />
									</td>
									<td>
										<?php if ($i === $lastParamIndex) { ?>
											<button type="button" class="btn btn-primary" onclick="return bfRunAllScriptTests();">
												<span class="icon-play" aria-hidden="true"></span>
												<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RUN'); ?>
											</button>
										<?php } ?>
									</td>
								</tr>
							<?php } ?>
						<?php } ?>
					</table>
				</div>
			</div>
			<div id="bf-script-test-error" class="alert alert-danger bf-piece-test-alert" style="display:none;">
				<span class="icon-times text-danger" aria-hidden="true"></span>
				<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_INVALID'); ?>: <span id="bf-script-test-error-message"></span>
				<div id="bf-script-test-error-output-wrap" style="display:none;">
					<div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_OUTPUT'); ?>:</strong></div>
					<pre id="bf-script-test-error-output"></pre>
				</div>
				<div id="bf-script-test-error-result-wrap" style="display:none;">
					<div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RESULT'); ?>:</strong></div>
					<pre id="bf-script-test-error-result"></pre>
				</div>
				<div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PARAMETERS'); ?>:</strong></div>
				<pre id="bf-script-test-error-params"></pre>
			</div>
			<div id="bf-script-test-output-wrap" style="display:none;">
				<p><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_OUTPUT'); ?>:</strong></p>
				<pre id="bf-script-test-output"></pre>
			</div>
			<div id="bf-script-test-status" class="alert" style="display:none;">
				<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RESULT'); ?>:</strong>
				<pre id="bf-script-test-result"></pre>
				<div id="bf-script-test-status-warning" style="display:none;">
					<span class="icon-warning text-warning" aria-hidden="true"></span>
					<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_WARNING_EMPTY_RESULT'); ?>
				</div>
				<div id="bf-script-test-status-success" style="display:none;">
					<span class="icon-check text-success" aria-hidden="true"></span>
					<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_EXECUTED'); ?>
				</div>
				<div id="bf-script-test-status-invalid" style="display:none;">
					<span class="icon-times text-danger" aria-hidden="true"></span>
					<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_INVALID_FALSE_RESULT'); ?>
				</div>
				<div id="bf-script-test-status-params-wrap" style="display:none;">
					<div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PARAMETERS'); ?>:</strong></div>
					<pre id="bf-script-test-status-params"></pre>
				</div>
			</div>
			<div id="bf-script-unit-tests-status" class="alert" style="display:none;">
				<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>:</strong>
				<div id="bf-script-unit-tests-summary"></div>
				<div id="bf-script-unit-tests-details-wrap" style="display:none;">
					<div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_DETAIL'); ?>:</strong></div>
					<pre id="bf-script-unit-tests-details"></pre>
				</div>
			</div>
			<input type="hidden" name="option" value="<?php echo $option; ?>" />
			<input type="hidden" name="view" value="scripts" />
			<input type="hidden" name="task" value="scripts.test" />
			<input type="hidden" name="pkg" value="<?php echo htmlspecialchars((string) $pkg, ENT_QUOTES); ?>" />
			<input type="hidden" name="ids[]" value="<?php echo (int) $row->id; ?>" />
			<input type="hidden" name="test_context" value="1" />
			<input type="hidden" name="test_mode" value="<?php echo htmlspecialchars((string) $testMode, ENT_QUOTES); ?>" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
		<?php
	} // test

}
?>
