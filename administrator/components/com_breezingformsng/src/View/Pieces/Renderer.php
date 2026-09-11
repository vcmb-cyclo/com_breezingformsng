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

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Pieces;

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
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
			'COM_BREEZINGFORMSNG_PIECES_ENTERIDENT',
			'COM_BREEZINGFORMSNG_PIECES_ENTERNAME',
			'COM_BREEZINGFORMSNG_PIECES_ENTTITLE',
			'COM_BREEZINGFORMSNG_TEST_INVALID_SERVER_RESPONSE',
			'COM_BREEZINGFORMSNG_TEST_NO_CHANGES',
			'COM_BREEZINGFORMSNG_TEST_PASSED_SHORT',
			'COM_BREEZINGFORMSNG_TEST_RUNNING',
			'COM_BREEZINGFORMSNG_TEST_SAVE_FIRST_PIECE',
			'COM_BREEZINGFORMSNG_TEST_SAVE_PIECE_BEFORE_CONTINUE',
			'COM_BREEZINGFORMSNG_TEST_SAVE_PIECE_BEFORE_NAVIGATION',
			'COM_BREEZINGFORMSNG_TEST_SAVE_PIECE_BEFORE_TESTS',
			'COM_BREEZINGFORMSNG_TEST_SAVE_PIECE_BEFORE_UNIT_TESTS',
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
		$action = $row->id ? Text::_('COM_BREEZINGFORMSNG_PIECES_EDITPIECE') : Text::_('COM_BREEZINGFORMSNG_PIECES_ADDPIECE');

		$pageTitle = BreadcrumbHelper::render([
			['label' => Text::_('COM_BREEZINGFORMSNG'), 'url' => 'index.php?option=com_breezingformsng'],
			['label' => Text::_('COM_BREEZINGFORMSNG_MANAGEPIECES'), 'url' => 'index.php?option=com_breezingformsng&view=pieces'],
			['label' => $row->id && $row->name !== '' ? (string) $row->name : Text::_('COM_BREEZINGFORMSNG_PIECES_ADDPIECE')],
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
			ToolBarHelper::custom('pieces.previous', 'arrow-left', '', Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGEPREV'), false);
			ToolBarHelper::custom('pieces.next', 'arrow-right', '', Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGENEXT'), false);
			ToolBarHelper::custom('pieces.test', 'eye', '', Text::_('COM_BREEZINGFORMSNG_TEST'), false);
		}
		ToolBarHelper::custom('pieces.save', 'save.png', 'save_f2.png', Text::_('COM_BREEZINGFORMSNG_TOOLBAR_SAVE'), false);
		ToolBarHelper::custom('pieces.cancel', 'cancel.png', 'cancel_f2.png', Text::_('COM_BREEZINGFORMSNG_TOOLBAR_QUICKMODE_CLOSE'), false);
		?>
		<?php
		$document = $app->getDocument();
		$document->getWebAssetManager()->useScript('com_breezingformsng.pieces-edit');
		$document->addScriptOptions('com_breezingformsng.pieces-edit', [
			'initialState' => $initialState,
			'hasSavedRecord' => (bool) $row->id,
			'csrfToken' => Session::getFormToken(),
		]);
		self::registerEditLabels();
		?>
		<form action="index.php?option=<?php echo htmlspecialchars($option, ENT_QUOTES); ?>&amp;view=pieces" method="post" name="adminForm" id="adminForm" class="adminForm">
			<table cellpadding="4" cellspacing="1" border="0" class="adminform" style="width:100%;">
				<tr>
					<td></td>
					<td nowrap>
						<?php echo Text::_('COM_BREEZINGFORMSNG_PIECES_TITLE'); ?>:
					</td>
					<td nowrap>
						<input type="text" size="50" maxlength="50" id="title" name="title" value="<?php echo $row->title; ?>"
							class="inputbox" />
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_PIECES_TIPTITLE')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
					</td>
					<td nowrap>
						<?php echo Text::_('COM_BREEZINGFORMSNG_PIECES_TYPE'); ?>:
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_PIECES_TIPTYPE')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
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
						<?php echo Text::_('COM_BREEZINGFORMSNG_PIECES_PACKAGE'); ?>:
					</td>
					<td nowrap>
						<input type="text" size="30" maxlength="30" id="package" name="package"
							value="<?php echo $row->package; ?>" class="inputbox" />
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_PIECES_TIPPACKAGE')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
					</td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td nowrap>
						<?php echo Text::_('COM_BREEZINGFORMSNG_PIECES_NAME'); ?>:
					</td>
					<td nowrap>
						<input type="text" size="30" maxlength="30" id="name" name="name" value="<?php echo $row->name; ?>"
							class="inputbox" />
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_PIECES_TIPNAME')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
					</td>
					<td nowrap>
						<?php echo Text::_('COM_BREEZINGFORMSNG_PIECES_PUBLISHED'); ?>:
						<?php echo HTMLHelper::_('select.booleanlist', "published", "", $row->published); ?>
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_PIECES_TIPPUBLISHED')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
					</td>
				</tr>
				<tr>
					<td></td>
					<td nowrap colspan="3">
						<?php echo Text::_('COM_BREEZINGFORMSNG_PIECES_DESCRIPTION'); ?>:
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_PIECES_TIPDESCRIPTION')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
						<br />
						<?php
						$params = array('syntax' => 'html');
						$editor = Editor::getInstance('codemirror');
						echo $editor->display('description', $row->description, '100%', 200, 40, 10, false, 'description', null, null, $params);
						?>
					</td>
				</tr>
				<tr>
					<td></td>
					<td nowrap colspan="3">
						<?php echo Text::_('COM_BREEZINGFORMSNG_PIECES_CODE'); ?>:
						<?php
						echo '<span><span title="' . HTMLHelper::tooltipText(Text::_('COM_BREEZINGFORMSNG_PIECES_TIPCODE')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
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
								id="bf-edit-piece-unit-tests-button"
								class="btn btn-secondary"
								onclick="return runPieceUnitTestsFromEdit();"
								<?php echo $hasPersistedUnitTests ? '' : 'disabled="disabled" aria-disabled="true" title="' . ($row->id ? htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_NONE'), ENT_QUOTES) : htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_TEST_SAVE_FIRST_PIECE'), ENT_QUOTES)) . '"'; ?>>
								<span class="icon-play" aria-hidden="true"></span>
								<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>
							</button>
						</div>
						<div id="bf-edit-piece-unit-tests-status" class="alert mt-3" style="display:none;">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>:</strong>
							<div id="bf-edit-piece-unit-tests-summary"></div>
							<div id="bf-edit-piece-unit-tests-details-wrap" style="display:none;">
								<div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_DETAIL'); ?>:</strong></div>
								<pre id="bf-edit-piece-unit-tests-details"></pre>
							</div>
						</div>
					</td>
				</tr>
			</table>
			<input type="hidden" name="pkg" value="<?php echo $pkg; ?>" />
			<input type="hidden" name="id" value="<?php echo $row->id; ?>" />
			<input type="hidden" name="option" value="<?php echo $option; ?>" />
			<input type="hidden" name="view" value="pieces" />
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
				return Text::_('COM_BREEZINGFORMSNG_PIECES_UNTYPED');
			case 'Before Form':
				return Text::_('COM_BREEZINGFORMSNG_PIECES_BEFOREFORM');
			case 'After Form':
				return Text::_('COM_BREEZINGFORMSNG_PIECES_AFTERFORM');
			case 'Begin Submit':
				return Text::_('COM_BREEZINGFORMSNG_PIECES_BEGINSUBMIT');
			case 'End Submit':
				return Text::_('COM_BREEZINGFORMSNG_PIECES_ENDSUBMIT');
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
		Text::script('COM_BREEZINGFORMSNG_PIECES_SELPIECESFIRST');
		Text::script('COM_BREEZINGFORMSNG_PIECES_ASKDELETE');
		?>
		<form action="index.php?option=<?php echo htmlspecialchars($option, ENT_QUOTES); ?>&amp;view=pieces" method="post" name="adminForm" id="adminForm">

				<label class="bfPackageSelector">
					<?php echo Text::_('COM_BREEZINGFORMSNG_PIECES_PACKAGE'); ?>
					<select id="pkgsel" name="pkgsel" class="inputbox" size="1" onchange="return bfPiecesSubmitList(true);">
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
						value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>" onchange="return bfPiecesSubmitList(true);"
						onkeydown="if(event.key==='Enter'){event.preventDefault();bfPiecesSubmitList(true);}" />
			</label>
				<label class="bfPackageSelector">
					<select name="filter_state" id="filter_state" class="inputbox form-select form-select-sm"
						onchange="return bfPiecesSubmitList(true);">
						<option value=""><?php echo Text::_('JOPTION_SELECT_PUBLISHED'); ?></option>
						<option value="P"<?php echo $filterState === 'P' ? ' selected="selected"' : ''; ?>><?php echo Text::_('JPUBLISHED'); ?></option>
						<option value="U"<?php echo $filterState === 'U' ? ' selected="selected"' : ''; ?>><?php echo Text::_('JUNPUBLISHED'); ?></option>
					</select>
				</label>
			<div style="clear: both;"></div>

				<div class="bf-manage-list-pagination-container table-responsive" id="bfPiecesPaginationContainer">
				<table class="adminlist table table-striped" id="bfPiecesList" data-name="breezingformsng-pieces">
				<thead>
				<tr>
					<th class="w-1 text-nowrap">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
					</th>
					<th class="w-1 text-center"><input class="form-check-input" type="checkbox" name="toggle" value=""
							onclick="Joomla.checkAll(this);" /></th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_PIECES_PACKAGE', 'a.package', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_PIECES_TITLE', 'a.title', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_PIECES_NAME', 'a.name', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_PIECES_TYPE', 'a.type', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_PIECES_DESCRIPTION', 'a.description', $listDirn, $listOrder); ?>
					</th>
					<th class="text-nowrap">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_MODIFIED', 'a.modified', $listDirn, $listOrder); ?>
					</th>
					<th class="w-1 text-center">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_BREEZINGFORMSNG_PIECES_PUBLISHED', 'a.published', $listDirn, $listOrder); ?>
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
					$editLink = 'index.php?option=' . htmlspecialchars($option, ENT_QUOTES) . '&amp;view=pieces&amp;task=pieces.edit&amp;pkg=' . urlencode($pkg) . '&amp;ids[]=' . (int) $row->id;
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
							$lastModified = null;
							if (property_exists($row, 'modified') && !empty($row->modified)) {
								$lastModified = $row->modified;
							} elseif (property_exists($row, 'created') && !empty($row->created)) {
								$lastModified = $row->created;
							}
							echo $lastModified ? HTMLHelper::date($lastModified, 'Y-m-d H:i', true) : '-';
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
				<input type="hidden" name="view" value="pieces" />
				<input type="hidden" name="task" value="" />
				<input type="hidden" name="pkg" value="<?php echo htmlspecialchars($pkg, ENT_QUOTES); ?>" />
				<input type="hidden" name="filter_order" value="<?php echo htmlspecialchars($listOrder, ENT_QUOTES); ?>" />
				<input type="hidden" name="filter_order_Dir" value="<?php echo htmlspecialchars($listDirn, ENT_QUOTES); ?>" />
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		<?php
	} // listitems

	static function test($option, $pkg, &$row, $functionName, $paramNames, $paramDefaults, $paramValues = array(), $result = null, $output = '', $error = '', $safeMode = 1, $autoRun = false, $errorDetails = array(), $testMode = '', $unitTestResult = array(), $autoOpened = 0)
	{
		/** @var CMSApplication $app */
		$app = Factory::getApplication();
		$app->getInput()->set('hidemainmenu', 1);
		ToolBarHelper::custom('pieces.edit', 'undo', '', Text::_('COM_BREEZINGFORMSNG_TEST_BACK'), false);
		ToolBarHelper::custom('pieces.previous', 'arrow-left', '', Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGEPREV'), false);
		ToolBarHelper::custom('pieces.next', 'arrow-right', '', Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGENEXT'), false);
			$hasUnitTests = trim((string) $row->unit_tests) !== '';
			$shouldAutoRunUnitTestsOnly = !$autoRun && $hasUnitTests && $testMode !== 'unit' && $result === null && $error === '' && empty($unitTestResult);
			$showAutoOpenUnitWarning = ((int) $autoOpened === 1) &&
				!empty($unitTestResult) &&
				(
					(isset($unitTestResult['error']) && $unitTestResult['error'] !== '') ||
					(!empty($unitTestResult['failures']))
				);
			$autoOpenUnitFailureCount = !empty($unitTestResult['failures']) ? count($unitTestResult['failures']) : 0;
			$autoOpenUnitWarningText = $autoOpenUnitFailureCount > 0
				? Text::plural('COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES', $autoOpenUnitFailureCount)
				: Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES_ON_OPEN');
			$document = $app->getDocument();
			$document->getWebAssetManager()->useScript('com_breezingformsng.pieces-test');
			$document->addScriptOptions('com_breezingformsng.pieces-test', [
				'autoSubmit' => $autoRun || $testMode === 'unit' || $shouldAutoRunUnitTestsOnly,
				'forceUnitTestMode' => $shouldAutoRunUnitTestsOnly,
			]);
			?>
		<form action="index.php" method="post" name="adminForm" id="adminForm" class="adminForm">
			<?php if ($showAutoOpenUnitWarning) { ?>
				<div id="bf-piece-auto-unit-warning" class="alert alert-warning">
					<span class="icon-warning text-warning" aria-hidden="true"></span>
					<?php echo htmlspecialchars($autoOpenUnitWarningText, ENT_QUOTES); ?>
				</div>
			<?php } ?>
			<div class="d-flex justify-content-between align-items-center mb-3">
				<h2 class="m-0"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PHP_PIECE'); ?></h2>
				<button type="submit" class="btn btn-primary">
					<span class="icon-play" aria-hidden="true"></span>
					<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RUN'); ?>
				</button>
			</div>
			<h3><?php echo htmlspecialchars($row->title, ENT_QUOTES); ?></h3>
			<div class="card mb-3 bg-light">
				<div class="card-body">
					<div class="row">
						<div class="col-sm-6 col-md-4">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PHP_PIECE_ID'); ?>:</strong> <?php echo (int) $row->id; ?>
						</div>
						<div class="col-sm-6 col-md-4">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPTS_PACKAGE'); ?>:</strong> <?php echo htmlspecialchars($row->package, ENT_QUOTES); ?>
						</div>
						<div class="col-sm-6 col-md-4">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_FUNCTION'); ?>:</strong> <?php echo htmlspecialchars($functionName, ENT_QUOTES); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="card mb-3 bg-light">
				<div class="card-body">
					<div class="row">
						<div class="col-sm-6 col-md-3">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_CREATED'); ?>:</strong> <?php echo $row->created ? HTMLHelper::date($row->created, 'Y-m-d H:i', true) : '-'; ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_CREATED_BY'); ?>:</strong> <?php echo htmlspecialchars((string) $row->created_by, ENT_QUOTES); ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_MODIFIED'); ?>:</strong> <?php echo $row->modified ? HTMLHelper::date($row->modified, 'Y-m-d H:i', true) : '-'; ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_MODIFIED_BY'); ?>:</strong> <?php echo htmlspecialchars((string) $row->modified_by, ENT_QUOTES); ?>
						</div>
					</div>
				</div>
			</div>
			<?php if (!empty($row->description)) { ?>
				<div class="card mb-3">
					<div class="card-header"><?php echo Text::_('COM_BREEZINGFORMSNG_PIECES_DESCRIPTION'); ?></div>
					<div class="card-body">
						<div class="form-control bg-light" style="white-space: pre-wrap;">
							<?php echo HTMLHelper::_('content.prepare', $row->description); ?>
						</div>
					</div>
				</div>
			<?php } ?>
			<div class="accordion" id="bfPieceCodeAccordion">
				<div class="accordion-item bg-light">
					<h2 class="accordion-header" id="bfPieceCodeHeading">
						<button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse"
							data-bs-target="#bfPieceCodeCollapse" aria-expanded="false" aria-controls="bfPieceCodeCollapse">
							<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PIECE_CODE'); ?>
						</button>
					</h2>
					<div id="bfPieceCodeCollapse" class="accordion-collapse collapse" aria-labelledby="bfPieceCodeHeading"
						data-bs-parent="#bfPieceCodeAccordion">
						<div class="accordion-body bg-light">
							<pre><?php echo htmlspecialchars($row->code, ENT_QUOTES); ?></pre>
						</div>
					</div>
				</div>
				<?php if (trim((string) $row->unit_tests) !== '') { ?>
					<div class="accordion-item bg-light mt-3">
						<h2 class="accordion-header" id="bfPieceUnitTestsHeading">
							<button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse"
								data-bs-target="#bfPieceUnitTestsCollapse" aria-expanded="false" aria-controls="bfPieceUnitTestsCollapse">
								<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>
							</button>
						</h2>
						<div id="bfPieceUnitTestsCollapse" class="accordion-collapse collapse" aria-labelledby="bfPieceUnitTestsHeading"
							data-bs-parent="#bfPieceCodeAccordion">
							<div class="accordion-body bg-light">
								<pre><?php echo htmlspecialchars((string) $row->unit_tests, ENT_QUOTES); ?></pre>
							</div>
						</div>
					</div>
				<?php } ?>

				<?php if (empty($functionName)) { ?>
				<p><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNABLE_TO_DETECT_FUNCTION_SIGNATURE_PIECE'); ?></p>
			<?php } else { ?>
				<table cellpadding="4" cellspacing="1" border="0" class="adminform" style="width:100%;">
					<tr>
						<th align="left"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PARAMETER'); ?></th>
						<th align="left"><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_VALUE'); ?></th>
					</tr>
					<?php
					if (!count($paramNames)) {
						?>
						<tr>
							<td><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_NO_PARAMETER'); ?></td>
							<td>-</td>
							<td></td>
						</tr>
						<?php
					} else {
						$lastParamIndex = count($paramNames) - 1;
						for ($i = 0; $i < count($paramNames); $i++) {
							$name = $paramNames[$i];
							$default = isset($paramDefaults[$i]) ? $paramDefaults[$i] : '';
							$value = isset($paramValues[$i]) ? $paramValues[$i] : $default;
							?>
							<tr>
								<td><?php echo htmlspecialchars($name, ENT_QUOTES); ?> :</td>
								<td>
									<input type="hidden" name="test_param_names[]" value="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>" />
									<input type="hidden" name="test_param_defaults[]" value="<?php echo htmlspecialchars($default, ENT_QUOTES); ?>" />
									<input type="text" name="test_param_values[]" value="<?php echo htmlspecialchars($value, ENT_QUOTES); ?>" class="inputbox" />
								</td>
								<td>
									<?php if ($i === $lastParamIndex) { ?>
										<button type="submit" class="btn btn-primary">
											<span class="icon-play" aria-hidden="true"></span>
											<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RUN'); ?>
										</button>
									<?php } ?>
								</td>
							</tr>
							<?php
						}
					}
					?>
				</table>
			<?php } ?>

			<?php
			$isEmptyResult = $result === '';
			$isSuccess = $result !== false && $result !== null && !$isEmptyResult;
			?>
			<?php if ($error !== '') { ?>
				<div class="alert alert-danger bf-piece-test-alert">
					<span class="icon-times text-danger" aria-hidden="true"></span>
					<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_INVALID'); ?>: <?php echo htmlspecialchars($error, ENT_QUOTES); ?>
					<?php if ($output !== '') { ?>
						<div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_OUTPUT'); ?>:</strong></div>
						<pre><?php echo htmlspecialchars($output, ENT_QUOTES); ?></pre>
					<?php } ?>
					<?php if ($result !== null) { ?>
						<div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RESULT'); ?>:</strong></div>
						<pre><?php echo htmlspecialchars(var_export($result, true), ENT_QUOTES); ?></pre>
					<?php } ?>
					<?php if (!empty($errorDetails)) { ?>
						<div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_EXCEPTION'); ?>:</strong></div>
						<pre><?php echo htmlspecialchars(print_r($errorDetails, true), ENT_QUOTES); ?></pre>
					<?php } ?>
					<div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PARAMETERS'); ?>:</strong></div>
					<pre><?php echo htmlspecialchars(print_r(array_combine($paramNames, $paramValues), true), ENT_QUOTES); ?></pre>
				</div>
			<?php } ?>
			<?php if ($error === '' && $output !== '') { ?>
				<p><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_OUTPUT'); ?>:</strong></p>
				<pre><?php echo htmlspecialchars($output, ENT_QUOTES); ?></pre>
			<?php } ?>
			<?php if ($error === '' && $result !== null) { ?>
				<div class="alert <?php echo $isEmptyResult ? 'alert-warning' : ($isSuccess ? 'alert-success' : 'alert-danger'); ?>">
					<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_RESULT'); ?>:</strong>
					<pre><?php echo htmlspecialchars(var_export($result, true), ENT_QUOTES); ?></pre>
					<?php if ($isEmptyResult) { ?>
						<div>
							<span class="icon-warning text-warning" aria-hidden="true"></span>
							<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_WARNING_EMPTY_RESULT'); ?>
						</div>
					<?php } elseif ($isSuccess) { ?>
						<div>
							<span class="icon-check text-success" aria-hidden="true"></span>
							<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_EXECUTED'); ?>
						</div>
					<?php } else { ?>
						<div>
							<span class="icon-times text-danger" aria-hidden="true"></span>
							<?php echo Text::_('COM_BREEZINGFORMSNG_TEST_INVALID_FALSE_RESULT'); ?>
						</div>
					<?php } ?>
					<?php if (!$isSuccess && !$isEmptyResult) { ?>
						<div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PARAMETERS'); ?>:</strong></div>
						<pre><?php echo htmlspecialchars(print_r(array_combine($paramNames, $paramValues), true), ENT_QUOTES); ?></pre>
					<?php } ?>
				</div>
			<?php } ?>
			<?php if (!empty($unitTestResult)) { ?>
				<?php
				$unitAlertClass = isset($unitTestResult['error']) ? 'alert-danger' : (isset($unitTestResult['warning']) ? 'alert-warning' : (empty($unitTestResult['failures']) ? 'alert-success' : 'alert-warning'));
				?>
				<div class="alert <?php echo $unitAlertClass; ?>">
					<strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS'); ?>:</strong>
					<?php if (isset($unitTestResult['error'])) { ?>
						<div><?php echo htmlspecialchars($unitTestResult['error'], ENT_QUOTES); ?></div>
					<?php } elseif (isset($unitTestResult['warning'])) { ?>
						<div><?php echo htmlspecialchars($unitTestResult['warning'], ENT_QUOTES); ?></div>
					<?php } else { ?>
						<div><?php echo (int) $unitTestResult['passed']; ?>/<?php echo (int) $unitTestResult['total']; ?> <?php echo Text::_('COM_BREEZINGFORMSNG_TEST_PASSED_SHORT'); ?></div>
						<?php if (!empty($unitTestResult['failures'])) { ?>
							<div><strong><?php echo Text::_('COM_BREEZINGFORMSNG_TEST_DETAIL'); ?>:</strong></div>
							<pre><?php echo htmlspecialchars(implode("\n\n", $unitTestResult['failures']), ENT_QUOTES); ?></pre>
						<?php } ?>
					<?php } ?>
				</div>
			<?php } ?>

			<input type="hidden" name="option" value="<?php echo $option; ?>" />
			<input type="hidden" name="view" value="pieces" />
			<input type="hidden" name="task" value="pieces.runTest" />
			<input type="hidden" name="pkg" value="<?php echo $pkg; ?>" />
			<input type="hidden" name="ids[]" value="<?php echo $row->id; ?>" />
			<input type="hidden" name="test_function" value="<?php echo htmlspecialchars($functionName, ENT_QUOTES); ?>" />
			<input type="hidden" name="test_context" value="1" />
			<input type="hidden" name="test_mode" value="<?php echo htmlspecialchars((string) $testMode, ENT_QUOTES); ?>" />
			<input type="hidden" name="auto_open_tests" value="0" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
		<?php
	} // test

}
?>
