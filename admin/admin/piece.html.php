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
			ToolBarHelper::custom('prev', 'arrow-left', '', 'Precedent', false);
			ToolBarHelper::custom('next', 'arrow-right', '', 'Suivant', false);
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
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td nowrap>
						<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_PUBLISHED'); ?>:
					</td>
					<td nowrap>
						<?php echo HTMLHelper::_('select.booleanlist', "published", "", $row->published); ?>
					</td>
					<td></td>
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
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td nowrap>
						<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_TYPE'); ?>:
					</td>
					<td nowrap>
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
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td nowrap colspan="2">
						<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_DESCRIPTION'); ?>:
						<br />
						<?php
						$params = array('syntax' => 'html');
						$editor = Editor::getInstance('codemirror');
						echo $editor->display('description', $row->description, '100%', 200, 40, 10, false, 'description', null, null, $params);
						?>
					</td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td nowrap colspan="2">
						<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_CODE'); ?>:
						<br />

						<?php
						$params = array('syntax' => 'javascript');
						$editor = Editor::getInstance('codemirror');
						echo $editor->display('code', $row->code, '100%', 300, 40, 20, false, 'code', null, null, $params);
						?>

					</td>
					<td></td>
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

	static function listitems($option, &$rows, &$pkglist, $pkg, $showInternal)
	{
		global $ff_config, $ff_version;
		$sort = BFRequest::getCmd('sort', 'name');
		$dir = strtoupper(BFRequest::getCmd('dir', 'ASC'));
		$dir = $dir === 'DESC' ? 'DESC' : 'ASC';
		$baseQuery = 'index.php?option=' . $option . '&act=managepieces&pkg=' . urlencode($pkg) . '&show_internal=' . (int) $showInternal;
		$toggleDir = function ($column) use ($sort, $dir) {
			if ($sort === $column) {
				return $dir === 'ASC' ? 'DESC' : 'ASC';
			}
			return 'ASC';
		};
		?>
		<script type="text/javascript">
							<!--
							function submitbutton(pressbutton)
							{
								var form = document.adminForm;
								switch (pressbutton) {
									case 'copy':
									case 'publish':
									case 'unpublish':
									case 'remove':
									case 'test':
										if (form.boxchecked.value==0) {
											alert("<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_SELPIECESFIRST'); ?>");
			return;
										} // if
			break;
									default:
			break;
								} // switch
			if (pressbutton == 'remove')
				if (!confirm("<?php echo BFText::_('COM_BREEZINGFORMS_PIECES_ASKDELETE'); ?>")) return;
			if (pressbutton == '' && form.pkgsel.value == '')
				form.pkg.value = '- blank -';
			else
				form.pkg.value = form.pkgsel.value;
			Joomla.submitform(pressbutton);
							} // submitbutton

			<?php

			ToolBarHelper::custom('new', 'new.png', 'new_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_NEW'), false);
			ToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_COPY'), false);
			ToolBarHelper::custom('publish', 'publish.png', 'publish_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_PUBLISH'), false);
			ToolBarHelper::custom('unpublish', 'unpublish.png', 'unpublish_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_UNPUBLISH'), false);
			ToolBarHelper::custom('test', 'eye', '', 'Test', false);
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
				<select id="pkgsel" name="pkgsel" class="inputbox" size="1" onchange="submitbutton('');">
					<?php
					if (count($pkglist))
						foreach ($pkglist as $pkg) {
							$selected = '';
							if ($pkg[0])
								$selected = ' selected';
							echo '<option value="' . $pkg[1] . '"' . $selected . '>' . $pkg[1] . '&nbsp;</option>';
						} // foreach
					?>
				</select>
			</label>
			<label class="bfPackageSelector">
				<input type="hidden" name="show_internal" value="0" />
				<input type="checkbox" name="show_internal" value="1" onchange="submitbutton('');"
					<?php echo $showInternal ? 'checked' : ''; ?> />
				Afficher les fonctions internes (_)
			</label>

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
					<th align="center">Test</th>
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
							$lastModified = $row->modified ?: $row->created;
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
						<td valign="top" align="center">
							<a class="tbody-icon" href="javascript:void(0);"
								onClick="return listItemTask('cb<?php echo $i; ?>','test')"><span class="icon-eye"
									aria-hidden="true"></span></a>
						</td>
					</tr>
					<?php
					$k = 1 - $k;
				} // for
				?>
			</table>
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="option" value="<?php echo $option; ?>" />
			<input type="hidden" name="act" value="managepieces" />
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="pkg" value="" />
		</form>
		<?php
	} // listitems

	static function test($option, $pkg, &$row, $functionName, $paramNames, $paramDefaults, $paramValues = array(), $result = null, $output = '', $error = '', $safeMode = 1, $autoRun = false, $errorDetails = array())
	{
		ToolBarHelper::custom('edit', 'cancel.png', 'cancel_f2.png', 'Retour', false);
		ToolBarHelper::custom('prev', 'arrow-left', '', 'Precedent', false);
		ToolBarHelper::custom('next', 'arrow-right', '', 'Suivant', false);
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
				<div class="alert alert-danger">
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
					<div class="accordion" id="bfPieceCodeAccordion">
						<div class="accordion-item">
							<h2 class="accordion-header" id="bfPieceCodeHeading">
								<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
									data-bs-target="#bfPieceCodeCollapse" aria-expanded="false" aria-controls="bfPieceCodeCollapse">
									Piece code
								</button>
							</h2>
							<div id="bfPieceCodeCollapse" class="accordion-collapse collapse" aria-labelledby="bfPieceCodeHeading"
								data-bs-parent="#bfPieceCodeAccordion">
								<div class="accordion-body">
									<pre><?php echo htmlspecialchars($row->code, ENT_QUOTES); ?></pre>
								</div>
							</div>
						</div>
					</div>
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
