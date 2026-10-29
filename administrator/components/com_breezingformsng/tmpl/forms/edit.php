<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Forms\HtmlView $this */
use Joomla\CMS\Factory;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\HTML\HTMLHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\FormsAdvancedOptionsHtml;

$f   = $this->form ?? throw new \RuntimeException('The form is not loaded.');
$pkg = $this->pkg ?: (string) ($f->package ?? '');

$tabId = 'bfFormTabs';

$editor = Editor::getInstance('codemirror');
$tabEntryCounts = FormsAdvancedOptionsHtml::countEntries($f);
?>
<form action="index.php?option=com_breezingformsng" method="post" name="adminForm" id="adminForm"
      class="bf-forms-edit">

<?php
FormsAdvancedOptionsHtml::render([
    'f' => $f,
    'pkg' => $pkg,
    'editor' => $editor,
    'tabId' => $tabId,
    'tabEntryCounts' => $tabEntryCounts,
    'initScripts' => $this->initScripts,
    'submittedScripts' => $this->submittedScripts,
    'pieceBefore' => $this->pieceBefore,
    'pieceAfter' => $this->pieceAfter,
    'pieceBeginSubmit' => $this->pieceBeginSubmit,
    'pieceEndSubmit' => $this->pieceEndSubmit,
]);
?>

  <input type="hidden" name="id" value="<?= (int) ($f->id ?? 0); ?>">
  <input type="hidden" name="task" value="">
  <?= HTMLHelper::_('form.token'); ?>
</form>

<?php
/** @var \Joomla\CMS\Application\CMSApplication $app */
$app = Factory::getApplication();
$bfDocument = $app->getDocument() ?? throw new \RuntimeException('The document is not available.');
$bfDocument->getWebAssetManager()->useScript('com_breezingformsng.admin-form');
$bfDocument->addScriptOptions('com_breezingformsng.admin-form', ['cancelTask' => 'forms.cancel']);
?>
