<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version 1.9
 * @package BreezingForms
 * @copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024 by XDA+GIL
 * @license Released under the terms of the GNU General Public License
 **/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HTML_facileFormsPiece
{
	static function edit($option, $pkg, &$row, &$typelist)
	{
		global $ff_mossite, $ff_admsite, $ff_config;
		$action = $row->id ? BFText::_('COM_BREEZINGFORMS_PIECES_EDITPIECE') : BFText::_('COM_BREEZINGFORMS_PIECES_ADDPIECE');
		if ($row->id) {
			ToolBarHelper::custom('prev', 'arrow-left', '', BFText::_('COM_BREEZINGFORMS_PROCESS_PAGEPREV'), false);
			ToolBarHelper::custom('next', 'arrow-right', '', BFText::_('COM_BREEZINGFORMS_PROCESS_PAGENEXT'), false);
			ToolBarHelper::custom('test', 'eye', '', 'Test', false);
		}
		ToolBarHelper::custom('save', 'save.png', 'save_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_SAVE'), false);
		ToolBarHelper::custom('cancel', 'cancel.png', 'cancel_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_QUICKMODE_CLOSE'), false);
		?>
		<script type="text/javascript" src="<?php echo $ff_admsite; ?>/admin/areautils.js"></script>
		<script type="text/javascript">
						<!--
						function checkIdentifier(value)
						{
							var invalidChars = /\W/;
							var error = '';
							if (value == '')
								error += "<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_ENTERNAME'); ?>\n";
							else
			if (invalidChars.test(value))
				error += "<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_ENTERIDENT'); ?>\n";
			return error;
						} // checkIdentifier

			function submitbutton(pressbutton) {
				var form = document.adminForm;
				var error = '';
				if (pressbutton != 'cancel' && pressbutton != 'test' && pressbutton != 'prev' && pressbutton != 'next') {
					error += checkIdentifier(form.name.value, 'name');
					if (form.title.value == '') error += "<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_ENTTITLE'); ?>\n";
				} // if
				if (error != '')
					alert(error);
				else
					submitform(pressbutton);
			} // submitbutton

			onload = function () {
				document.adminForm.title.focus();
			} // onload
			//-->
		</script>
		<form action="index.php" method="post" name="adminForm" id="adminForm" class="adminForm">
			<table cellpadding="4" cellspacing="1" border="0" class="adminform" style="width:100%;">
				<tr>
					<td></td>
					<td nowrap>
						<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_TITLE'); ?>:
					</td>
					<td nowrap>
						<input type="text" size="50" maxlength="50" name="title" value="<?php echo $row->title; ?>"
							class="inputbox" />
						<?php
						echo '<span><span title="' . bf_ToolTipText(BFText::_('COM_BREEZINGFORMS_PIECES_TIPTITLE')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
					</td>
					<td nowrap>
						<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_TYPE'); ?>:
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
						<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_PACKAGE'); ?>:
					</td>
					<td nowrap>
						<input type="text" size="30" maxlength="30" id="package" name="package"
							value="<?php echo $row->package; ?>" class="inputbox" />
					</td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td nowrap>
						<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_NAME'); ?>:
					</td>
					<td nowrap>
						<input type="text" size="30" maxlength="30" id="name" name="name" value="<?php echo $row->name; ?>"
							class="inputbox" />
						<?php
						echo '<span><span title="' . bf_ToolTipText(BFText::_('COM_BREEZINGFORMS_PIECES_TIPNAME')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
					</td>
					<td nowrap>
						<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_PUBLISHED'); ?>:
						<?php echo HTMLHelper::_('select.booleanlist', "published", "", $row->published); ?>
					</td>
				</tr>
				<tr>
					<td></td>
					<td nowrap colspan="3">
						<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_DESCRIPTION'); ?>:
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
						<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_CODE'); ?>:
						<br />

						<?php
						$params = array('syntax' => 'javascript');
						$editor = Editor::getInstance('codemirror');
						echo $editor->display('code', $row->code, '100%', 300, 40, 20, false, 'code', null, null, $params);
						?>

					</td>
				</tr>
			</table>
			<input type="hidden" name="pkg" value="<?php echo $pkg; ?>" />
			<input type="hidden" name="id" value="<?php echo $row->id; ?>" />
			<input type="hidden" name="option" value="<?php echo $option; ?>" />
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="act" value="managepieces" />
		</form>
		<?php
	} // edit

	static function typeName($type)
	{
		switch ($type) {
			case 'Untyped':
				return BFText::_('COM_BREEZINGFORMS_PIECES_UNTYPED');
			case 'Before Form':
				return BFText::_('COM_BREEZINGFORMS_PIECES_BEFOREFORM');
			case 'After Form':
				return BFText::_('COM_BREEZINGFORMS_PIECES_AFTERFORM');
			case 'Begin Submit':
				return BFText::_('COM_BREEZINGFORMS_PIECES_BEGINSUBMIT');
			case 'End Submit':
				return BFText::_('COM_BREEZINGFORMS_PIECES_ENDSUBMIT');
			default:
				;
		} // switch
		return '???';
	} // typeName

	static function listitems($option, &$rows, &$pkglist, $pkg, $showInternal, $search, $total, $limit, $limitstart, $pageSizes)
	{
		global $ff_config, $ff_version;
		$sort = BFRequest::getCmd('sort', 'name');
		$dir = strtoupper(BFRequest::getCmd('dir', 'ASC'));
		$dir = $dir === 'DESC' ? 'DESC' : 'ASC';
		$baseQuery = 'index.php?option=' . $option .
			'&act=managepieces' .
			'&pkg=' . urlencode($pkg) .
			'&show_internal=' . (int) $showInternal .
			'&search=' . urlencode($search) .
			'&limit=' . (int) $limit .
			'&limitstart=' . (int) $limitstart;
		$toggleDir = function ($column) use ($sort, $dir) {
			if ($sort === $column) {
				return $dir === 'ASC' ? 'DESC' : 'ASC';
			}
			return 'ASC';
		};
		$pageCount = $limit > 0 ? (int) ceil($total / $limit) : 1;
		$pageCount = max(1, $pageCount);
		$currentPage = $limit > 0 ? ((int) floor($limitstart / $limit) + 1) : 1;
		$currentPage = min(max($currentPage, 1), $pageCount);
		$startNo = $total > 0 ? $limitstart + 1 : 0;
		$endNo = $total > 0 ? min($limitstart + $limit, $total) : 0;
		$shownPageNumbers = array();
		if ($pageCount <= 4) {
			for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
				$shownPageNumbers[] = $pageNo;
			}
		} else {
			$shownPageNumbers = array(1, 2, $pageCount - 1, $pageCount, max(1, $currentPage - 1), $currentPage, min($pageCount, $currentPage + 1));
			$shownPageNumbers = array_values(array_unique($shownPageNumbers));
			sort($shownPageNumbers);
		}
		$gotoLabel = rtrim(BFText::_('COM_BREEZINGFORMS_GO_TO_PAGE'), '.');
		?>
			<script type="text/javascript">
								<!--
								var bfPiecesPageCount = <?php echo (int) $pageCount; ?>;
								function bfPiecesSyncPackage(form)
								{
									if (!form) {
										return;
									}
									if (form.pkgsel && form.pkg) {
										form.pkg.value = form.pkgsel.value === '' ? '- blank -' : form.pkgsel.value;
									}
								}

								function bfPiecesSubmitList(resetLimitStart)
								{
									var form = document.adminForm;
									if (!form) {
										return false;
									}
									if (resetLimitStart && form.limitstart) {
										form.limitstart.value = 0;
									}
									bfPiecesSyncPackage(form);
									Joomla.submitform('', form);
									return false;
								}

								function submitbutton(pressbutton)
								{
									var form = document.adminForm;
									switch (pressbutton) {
										case 'copy':
										case 'publish':
										case 'unpublish':
										case 'remove':
											if (form.boxchecked.value==0) {
												alert("<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_SELPIECESFIRST'); ?>");
												return;
											} // if
											break;
										default:
											break;
									} // switch
									if (pressbutton == 'remove') {
										if (!confirm("<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_ASKDELETE'); ?>")) {
											return;
										}
									}
									bfPiecesSyncPackage(form);
									Joomla.submitform(pressbutton, form);
								} // submitbutton

								function bfPiecesGoToPage(pageNo)
								{
									var form = document.adminForm;
									var limit = parseInt(form.limit.value, 10);
									var page = parseInt(pageNo, 10);
									if (isNaN(limit) || limit <= 0) {
										limit = 10;
									}
									if (isNaN(page) || page < 1) {
										page = 1;
									}
									if (page > bfPiecesPageCount) {
										page = bfPiecesPageCount;
									}
									form.limitstart.value = (page - 1) * limit;
									return bfPiecesSubmitList(false);
								}

								function bfPiecesChangePageSize(pageSize)
								{
									var form = document.adminForm;
									var size = parseInt(pageSize, 10);
									if (isNaN(size) || size <= 0) {
										return false;
									}
									form.limit.value = size;
									form.limitstart.value = 0;
									return bfPiecesSubmitList(false);
								}

								function bfPiecesGotoPageFromInput()
								{
									var input = document.getElementById('bfPiecesGotoPage');
									if (!input) {
										return false;
									}
									return bfPiecesGoToPage(input.value);
								}

			<?php

			ToolBarHelper::custom('new', 'new.png', 'new_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_NEW'), false);
			ToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_COPY'), false);
			ToolBarHelper::custom('publish', 'publish.png', 'publish_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_PUBLISH'), false);
			ToolBarHelper::custom('unpublish', 'unpublish.png', 'unpublish_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_UNPUBLISH'), false);
			ToolBarHelper::custom('remove', 'delete.png', 'delete_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_DELETE'), false);
			?>

				function listItemTask(id, task) {
					var f = document.adminForm;
					cb = eval('f.' + id);
					if (cb) {
					for (i = 0; true; i++) {
						cbx = eval('f.cb' + i);
						if (!cbx) break;
						cbx.checked = false;
					} // for
					cb.checked = true;
					f.boxchecked.value = 1;
					Joomla.submitbutton(task);
				}
					return false;
				} // listItemTask

				//-->
			</script>
		<form action="index.php" method="post" name="adminForm" id="adminForm">

				<label class="bfPackageSelector">
					<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_PACKAGE'); ?>
					<select id="pkgsel" name="pkgsel" class="inputbox" size="1" onchange="return bfPiecesSubmitList(true);">
					<?php
					if (count($pkglist))
						foreach ($pkglist as $pkgEntry) {
							$selected = '';
							if ($pkgEntry[0])
								$selected = ' selected';
							$label = $pkgEntry[1] === '' ? BFText::_('COM_BREEZINGFORMS_ALL_FILTER') : $pkgEntry[1];
							echo '<option value="' . $pkgEntry[1] . '"' . $selected . '>' . $label . '&nbsp;</option>';
						} // foreach
					?>
				</select>
			</label>
				<label class="bfPackageSelector">
					<input type="hidden" name="show_internal" value="0" />
					<input type="checkbox" name="show_internal" value="1" onchange="return bfPiecesSubmitList(true);"
						<?php echo $showInternal ? 'checked' : ''; ?> />
					Afficher les fonctions internes (préfixe _fonction)
				</label>
				<label class="bfPackageSelector bfFilterTools">
					Filtre
					<input type="text" name="search" id="search" class="inputbox"
						value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>" onchange="return bfPiecesSubmitList(true);"
						onkeydown="if(event.key==='Enter'){event.preventDefault();bfPiecesSubmitList(true);}" />
			</label>
			<div style="clear: both;"></div>

				<div class="jtable-main-container bf-manage-list-pagination-container" id="bfPiecesPaginationContainer">
				<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist table table-striped">
				<tr>
					<th style="width: 25px;" nowrap align="right">
						<a href="<?php echo $baseQuery . '&sort=id&dir=' . $toggleDir('id'); ?>">ID</a>
					</th>
					<th style="width: 25px;" nowrap align="center"><input type="checkbox" name="toggle" value=""
							onclick="Joomla.checkAll(this);" /></th>
					<th align="left">
						<a href="<?php echo $baseQuery . '&sort=title&dir=' . $toggleDir('title'); ?>">
							<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_TITLE'); ?>
						</a>
					</th>
					<th align="left">
						<a href="<?php echo $baseQuery . '&sort=name&dir=' . $toggleDir('name'); ?>">
							<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_NAME'); ?>
						</a>
					</th>
					<th align="left">
						<a href="<?php echo $baseQuery . '&sort=type&dir=' . $toggleDir('type'); ?>">
							<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_TYPE'); ?>
						</a>
					</th>
					<th align="left">
						<a href="<?php echo $baseQuery . '&sort=description&dir=' . $toggleDir('description'); ?>">
							<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_DESCRIPTION'); ?>
						</a>
					</th>
					<th align="left">
						<a href="<?php echo $baseQuery . '&sort=modified&dir=' . $toggleDir('modified'); ?>">
							<?php echo BFText::_('JGLOBAL_MODIFIED'); ?>
						</a>
					</th>
					<th align="center">
						<a href="<?php echo $baseQuery . '&sort=published&dir=' . $toggleDir('published'); ?>">
							<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_PUBLISHED'); ?>
						</a>
					</th>
				</tr>
				<?php
				$k = 0;
				for ($i = 0; $i < count($rows); $i++) {
					$row = $rows[$i];
					$desc = $row->description;
					if (strlen($desc) > $ff_config->limitdesc)
						$desc = substr($desc, 0, $ff_config->limitdesc) . '...';
					?>
					<tr class="row<?php echo $k; ?>">
						<td nowrap valign="top" align="right">
							<?php echo $row->id; ?>
						</td>
						<td nowrap valign="top" align="center"><input type="checkbox" id="cb<?php echo $i; ?>" name="ids[]"
								value="<?php echo $row->id; ?>" onclick="Joomla.isChecked(this.checked);" /></td>
						<td valign="top" align="left"><a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>','edit')">
								<?php echo $row->title; ?>
							</a></td>
						<td valign="top" align="left">
							<?php echo $row->name; ?>
						</td>
						<td valign="top" align="left">
							<?php echo HTML_facileFormsPiece::typeName($row->type); ?>
						</td>
						<td valign="top" align="left">
							<?php echo htmlspecialchars($desc, ENT_QUOTES); ?>
						</td>
						<td valign="top" align="left">
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
						<td valign="top" align="center">
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
					$k = 1 - $k;
				} // for
				?>
				</table>
				<div class="jtable-bottom-panel">
					<div class="jtable-left-area">
						<span class="jtable-page-list">
							<?php
							$firstDisabled = $currentPage <= 1;
							$lastDisabled = $currentPage >= $pageCount;
							?>
							<span class="jtable-page-number-first<?php echo $firstDisabled ? ' jtable-page-number-disabled' : ''; ?>"<?php echo $firstDisabled ? '' : ' onclick="return bfPiecesGoToPage(1);"'; ?>>&lt;&lt;</span>
							<span class="jtable-page-number-previous<?php echo $firstDisabled ? ' jtable-page-number-disabled' : ''; ?>"<?php echo $firstDisabled ? '' : ' onclick="return bfPiecesGoToPage(' . ($currentPage - 1) . ');"'; ?>>&lt;</span>
							<?php
							$previousPageNo = 0;
							foreach ($shownPageNumbers as $pageNo) {
								if (($pageNo - $previousPageNo) > 1) {
									echo '<span class="jtable-page-number-space">...</span>';
								}
								$isActive = $pageNo === $currentPage;
								echo '<span class="jtable-page-number' . ($isActive ? ' jtable-page-number-active jtable-page-number-disabled' : '') . '"' .
									($isActive ? '' : ' onclick="return bfPiecesGoToPage(' . (int) $pageNo . ');"') . '>' . (int) $pageNo . '</span>';
								$previousPageNo = $pageNo;
							}
							?>
							<span class="jtable-page-number-next<?php echo $lastDisabled ? ' jtable-page-number-disabled' : ''; ?>"<?php echo $lastDisabled ? '' : ' onclick="return bfPiecesGoToPage(' . ($currentPage + 1) . ');"'; ?>>&gt;</span>
							<span class="jtable-page-number-last<?php echo $lastDisabled ? ' jtable-page-number-disabled' : ''; ?>"<?php echo $lastDisabled ? '' : ' onclick="return bfPiecesGoToPage(' . $pageCount . ');"'; ?>>&gt;&gt;</span>
						</span>
						<span class="jtable-page-size-change">
							<span>Row count: </span>
							<select name="limit" onchange="return bfPiecesChangePageSize(this.value);">
								<?php foreach ($pageSizes as $pageSize) { ?>
									<option value="<?php echo (int) $pageSize; ?>"<?php echo (int) $pageSize === (int) $limit ? ' selected="selected"' : ''; ?>><?php echo (int) $pageSize; ?></option>
								<?php } ?>
							</select>
						</span>
						<span class="jtable-goto-page">
							<span><?php echo htmlspecialchars($gotoLabel, ENT_QUOTES); ?>: </span>
							<input type="text" id="bfPiecesGotoPage" maxlength="10" value="<?php echo (int) $currentPage; ?>"
								onkeydown="if(event.key==='Enter'){event.preventDefault();bfPiecesGotoPageFromInput();}" />
						</span>
					</div>
					<div class="jtable-right-area">
						<span class="jtable-page-info"><?php echo $total > 0 ? ('Showing ' . (int) $startNo . '-' . (int) $endNo . ' of ' . (int) $total) : ''; ?></span>
					</div>
				</div>
				</div>
				<input type="hidden" name="boxchecked" value="0" />
				<input type="hidden" name="option" value="<?php echo $option; ?>" />
				<input type="hidden" name="act" value="managepieces" />
				<input type="hidden" name="task" value="" />
				<input type="hidden" name="limitstart" value="<?php echo (int) $limitstart; ?>" />
				<input type="hidden" name="pkg" value="<?php echo htmlspecialchars($pkg, ENT_QUOTES); ?>" />
			</form>
		<?php
	} // listitems

	static function test($option, $pkg, &$row, $functionName, $paramNames, $paramDefaults, $paramValues = array(), $result = null, $output = '', $error = '', $safeMode = 1, $autoRun = false, $errorDetails = array())
	{
		ToolBarHelper::custom('edit', 'cancel.png', 'cancel_f2.png', 'Retour', false);
		ToolBarHelper::custom('prev', 'arrow-left', '', BFText::_('COM_BREEZINGFORMS_PROCESS_PAGEPREV'), false);
		ToolBarHelper::custom('next', 'arrow-right', '', BFText::_('COM_BREEZINGFORMS_PROCESS_PAGENEXT'), false);
		?>
		<?php if ($autoRun) { ?>
			<script type="text/javascript">
				window.addEventListener('load', function () {
					document.getElementById('adminForm').submit();
				});
			</script>
		<?php } ?>
		<form action="index.php" method="post" name="adminForm" id="adminForm" class="adminForm">
			<div class="d-flex justify-content-between align-items-center mb-3">
				<h2 class="m-0">Test PHP Piece</h2>
				<button type="submit" class="btn btn-primary">
					<span class="icon-eye" aria-hidden="true"></span>
					Lancer
				</button>
			</div>
			<h3><?php echo htmlspecialchars($row->title, ENT_QUOTES); ?></h3>
			<div class="card mb-3 bg-light">
				<div class="card-body">
					<div class="row">
						<div class="col-sm-6 col-md-4">
							<strong>PHP Piece ID :</strong> <?php echo (int) $row->id; ?>
						</div>
						<div class="col-sm-6 col-md-4">
							<strong>Package :</strong> <?php echo htmlspecialchars($row->package, ENT_QUOTES); ?>
						</div>
						<div class="col-sm-6 col-md-4">
							<strong>Function :</strong> <?php echo htmlspecialchars($functionName, ENT_QUOTES); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="card mb-3 bg-light">
				<div class="card-body">
					<div class="row">
						<div class="col-sm-6 col-md-3">
							<strong>Created :</strong> <?php echo $row->created ? HTMLHelper::date($row->created, 'Y-m-d H:i', true) : '-'; ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong>Created by :</strong> <?php echo htmlspecialchars((string) $row->created_by, ENT_QUOTES); ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong>Modified :</strong> <?php echo $row->modified ? HTMLHelper::date($row->modified, 'Y-m-d H:i', true) : '-'; ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong>Modified by :</strong> <?php echo htmlspecialchars((string) $row->modified_by, ENT_QUOTES); ?>
						</div>
					</div>
				</div>
			</div>
			<?php if (!empty($row->description)) { ?>
				<div class="card mb-3">
					<div class="card-header">Description</div>
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
							Piece code
						</button>
					</h2>
					<div id="bfPieceCodeCollapse" class="accordion-collapse collapse" aria-labelledby="bfPieceCodeHeading"
						data-bs-parent="#bfPieceCodeAccordion">
						<div class="accordion-body bg-light">
							<pre><?php echo htmlspecialchars($row->code, ENT_QUOTES); ?></pre>
						</div>
					</div>
				</div>

				<?php if (empty($functionName)) { ?>
				<p>Unable to detect a function signature in this piece.</p>
			<?php } else { ?>
				<table cellpadding="4" cellspacing="1" border="0" class="adminform" style="width:100%;">
					<tr>
						<th align="left">Parameter</th>
						<th align="left">Value</th>
					</tr>
					<?php
					if (!count($paramNames)) {
						?>
						<tr>
							<td>NO parameter</td>
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
											<span class="icon-eye" aria-hidden="true"></span>
											Lancer
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
					Invalide: <?php echo htmlspecialchars($error, ENT_QUOTES); ?>
					<?php if ($output !== '') { ?>
						<div><strong>Output:</strong></div>
						<pre><?php echo htmlspecialchars($output, ENT_QUOTES); ?></pre>
					<?php } ?>
					<?php if ($result !== null) { ?>
						<div><strong>Result:</strong></div>
						<pre><?php echo htmlspecialchars(var_export($result, true), ENT_QUOTES); ?></pre>
					<?php } ?>
					<?php if (!empty($errorDetails)) { ?>
						<div><strong>Exception:</strong></div>
						<pre><?php echo htmlspecialchars(print_r($errorDetails, true), ENT_QUOTES); ?></pre>
					<?php } ?>
					<div><strong>Parameters:</strong></div>
					<pre><?php echo htmlspecialchars(print_r(array_combine($paramNames, $paramValues), true), ENT_QUOTES); ?></pre>
				</div>
			<?php } ?>
			<?php if ($error === '' && $output !== '') { ?>
				<p><strong>Output:</strong></p>
				<pre><?php echo htmlspecialchars($output, ENT_QUOTES); ?></pre>
			<?php } ?>
			<?php if ($error === '' && $result !== null) { ?>
				<div class="alert <?php echo $isEmptyResult ? 'alert-warning' : ($isSuccess ? 'alert-success' : 'alert-danger'); ?>">
					<strong>Result:</strong>
					<pre><?php echo htmlspecialchars(var_export($result, true), ENT_QUOTES); ?></pre>
					<?php if ($isEmptyResult) { ?>
						<div>
							<span class="icon-warning text-warning" aria-hidden="true"></span>
							Warning: resultat vide
						</div>
					<?php } elseif ($isSuccess) { ?>
						<div>
							<span class="icon-check text-success" aria-hidden="true"></span>
							Valide
						</div>
					<?php } else { ?>
						<div>
							<span class="icon-times text-danger" aria-hidden="true"></span>
							Invalide: resultat faux
						</div>
					<?php } ?>
					<?php if (!$isSuccess && !$isEmptyResult) { ?>
						<div><strong>Parameters:</strong></div>
						<pre><?php echo htmlspecialchars(print_r(array_combine($paramNames, $paramValues), true), ENT_QUOTES); ?></pre>
					<?php } ?>
				</div>
			<?php } ?>

			<input type="hidden" name="option" value="<?php echo $option; ?>" />
			<input type="hidden" name="task" value="testrun" />
			<input type="hidden" name="act" value="managepieces" />
			<input type="hidden" name="pkg" value="<?php echo $pkg; ?>" />
			<input type="hidden" name="ids[]" value="<?php echo $row->id; ?>" />
			<input type="hidden" name="test_function" value="<?php echo htmlspecialchars($functionName, ENT_QUOTES); ?>" />
			<input type="hidden" name="test_context" value="1" />
		</form>
		<?php
	} // test

} // class HTML_facileFormsPiece
?>
