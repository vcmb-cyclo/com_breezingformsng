<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * The "advanced options" settings block (General/Email/Scripts/Pieces/
 * MailChimp/Salesforce/Dropbox). Rendered both by the classic
 * forms.edit&advanced=1 screen and by the QuickMode editor's "Options" tab -
 * see FormsAdvancedOptionsHtml::render(). Expects $f, $pkg, $editor,
 * $tabEntryCounts, $initScripts, $submittedScripts, $pieceBefore,
 * $pieceAfter, $pieceBeginSubmit, $pieceEndSubmit and $tabId in scope.
 */

defined('_JEXEC') or die;

/**
 * @var \stdClass $f
 * @var string $pkg
 * @var \Joomla\CMS\Editor\Editor $editor
 * @var string $tabId
 * @var array<string, int> $tabEntryCounts
 * @var array<int, \stdClass> $initScripts
 * @var array<int, \stdClass> $submittedScripts
 * @var array<int, \stdClass> $pieceBefore
 * @var array<int, \stdClass> $pieceAfter
 * @var array<int, \stdClass> $pieceBeginSubmit
 * @var array<int, \stdClass> $pieceEndSubmit
 */

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\FormsAdvancedOptionsHtml;

HTMLHelper::_('bootstrap.tab');

$labelTip = static fn (string $key): string => ' title="' . htmlspecialchars(Text::_($key), ENT_QUOTES, 'UTF-8') . '"';

$mailEditor = Editor::getInstance(Factory::getApplication()->get('editor'));
$mailFieldNames = FormsAdvancedOptionsHtml::mailFieldNames((int) ($f->id ?? 0), (int) ($f->emaillog ?? 1));
$mbCustomEmail = (int) ($f->mb_email_type ?? 0) === 1;
$mbDefaultBody = FormsAdvancedOptionsHtml::mailbackDefaultBody($f);
$mbEmailHtml = FormsAdvancedOptionsHtml::mailbackHtmlSelection($f);
$customEmail = (int) ($f->email_type ?? 0) === 1;
$defaultBody = FormsAdvancedOptionsHtml::notificationDefaultBody($f);
$emailHtml = FormsAdvancedOptionsHtml::notificationHtmlSelection($f);

$missingPieces = FormsAdvancedOptionsHtml::missingPieces(
    $f,
    [1 => $pieceBefore, 2 => $pieceAfter, 3 => $pieceBeginSubmit, 4 => $pieceEndSubmit]
);
$paneErrors = [
    'form-pieces' => isset($missingPieces[1]) || isset($missingPieces[2]),
    'submit-pieces' => isset($missingPieces[3]) || isset($missingPieces[4]),
];
?>
  <ul class="nav nav-tabs bfng-tabs" id="<?= $tabId; ?>" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="tab-general" data-bs-toggle="tab" data-bs-target="#pane-general"
              type="button" role="tab"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_TAB_GENERAL'); ?><?php if ($tabEntryCounts['general'] > 0): ?> <span class="badge bg-primary text-white"><?= $tabEntryCounts['general']; ?></span><?php endif; ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-email" data-bs-toggle="tab" data-bs-target="#pane-email"
              type="button" role="tab"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_TAB_EMAIL'); ?><?php if ($tabEntryCounts['email'] > 0): ?> <span class="badge bg-primary text-white"><?= $tabEntryCounts['email']; ?></span><?php endif; ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-scripts" data-bs-toggle="tab" data-bs-target="#pane-scripts"
              type="button" role="tab"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_TAB_SCRIPTS'); ?><?php if ($tabEntryCounts['scripts'] > 0): ?> <span class="badge bg-primary text-white"><?= $tabEntryCounts['scripts']; ?></span><?php endif; ?></button>
    </li>
    <?php foreach ([
        'form-pieces' => Text::_('COM_BREEZINGFORMSNG_FORMS_FORMPIECES'),
        'submit-pieces' => Text::_('COM_BREEZINGFORMSNG_FORMS_SUBMPIECES'),
        'mailchimp' => 'MailChimp®',
        'salesforce' => 'Salesforce®',
        'dropbox' => 'Dropbox®',
    ] as $pane => $label): ?>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-<?= $pane; ?>" data-bs-toggle="tab" data-bs-target="#pane-<?= $pane; ?>"
              type="button" role="tab"><?= $label; ?><?php if (($tabEntryCounts[$pane] ?? 0) > 0): ?> <span class="badge bg-primary text-white"><?= $tabEntryCounts[$pane]; ?></span><?php endif; ?><?php if (!empty($paneErrors[$pane])): ?> <span class="icon-warning text-danger" title="<?= htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_FORMS_PIECE_MISSING'), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></span><span class="visually-hidden"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_PIECE_MISSING'); ?></span><?php endif; ?></button>
    </li>
    <?php endforeach; ?>
  </ul>

  <div class="tab-content border border-top-0 p-3 mb-3">

    <!-- TAB: GÉNÉRAL -->
    <div class="tab-pane fade show active" id="pane-general" role="tabpanel">

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_title"<?= $labelTip('COM_BREEZINGFORMSNG_QM_FORM_TITLE'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_TITLE'); ?> <span class="text-danger">*</span></label>
        <div class="col-sm-9"><input type="text" class="form-control" id="jf_title" name="title" required value="<?= htmlspecialchars($f->title ?? ''); ?>"></div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_name"<?= $labelTip('COM_BREEZINGFORMSNG_QM_FORM_NAME'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_NAME'); ?></label>
        <div class="col-sm-9"><input type="text" class="form-control" id="jf_name" name="name" value="<?= htmlspecialchars($f->name ?? ''); ?>"></div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_package"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_PACKAGE_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_PACKAGE'); ?></label>
        <div class="col-sm-9">
          <select class="form-select" id="jf_package" name="package">
            <option value=""><?= Text::_('COM_BREEZINGFORMSNG_FORMS_NONE'); ?></option>
            <?php foreach (FormsAdvancedOptionsHtml::packageNames($pkg) as $packageName) : ?>
              <option value="<?= htmlspecialchars($packageName, ENT_QUOTES, 'UTF-8'); ?>"<?= $packageName === $pkg ? ' selected' : ''; ?>><?= htmlspecialchars($packageName, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_PACKAGE_HINT'); ?></div>
        </div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_description"<?= $labelTip('COM_BREEZINGFORMSNG_QM_FORM_DESCRIPTION'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_DESCRIPTION'); ?></label>
        <div class="col-sm-9"><textarea class="form-control" id="jf_description" name="description" rows="3"><?= htmlspecialchars($f->description ?? ''); ?></textarea></div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_pages"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_PAGES_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_PAGES'); ?></label>
        <div class="col-sm-9"><input type="number" class="form-control w-auto" id="jf_pages" name="pages" min="1" value="<?= (int) ($f->pages ?? 1); ?>"></div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_class1"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_CLASS_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_CLASS'); ?></label>
        <div class="col-sm-9"><input type="text" class="form-control" id="jf_class1" name="class1" value="<?= htmlspecialchars($f->class1 ?? 'content_outline'); ?>"></div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_WIDTH_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_WIDTH'); ?></label>
        <div class="col-sm-9 d-flex gap-2 align-items-center">
          <input type="number" class="form-control w-auto" name="width" min="0" value="<?= (int) ($f->width ?? 400); ?>">
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="widthmode" id="wm0" value="0"<?= !(int) ($f->widthmode ?? 0) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="wm0">px</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="widthmode" id="wm1" value="1"<?= (int) ($f->widthmode ?? 0) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="wm1">%</label>
          </div>
        </div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_HEIGHT_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_HEIGHT'); ?></label>
        <div class="col-sm-9 d-flex gap-2 align-items-center">
          <input type="number" class="form-control w-auto" name="height" min="0" value="<?= (int) ($f->height ?? 500); ?>">
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="heightmode" id="hm0" value="0"<?= !(int) ($f->heightmode ?? 0) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="hm0"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_FIXED'); ?></label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="heightmode" id="hm1" value="1"<?= (int) ($f->heightmode ?? 0) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="hm1"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_AUTO'); ?></label>
          </div>
        </div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_PUBLISHED_TIP'); ?>><?= Text::_('JPUBLISHED'); ?></label>
        <div class="col-sm-9 d-flex gap-3">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="published" id="pub1" value="1"<?= (int) ($f->published ?? 1) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="pub1"><?= Text::_('JYES'); ?></label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="published" id="pub0" value="0"<?= !(int) ($f->published ?? 1) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="pub0"><?= Text::_('JNO'); ?></label>
          </div>
        </div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_ordering"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_ORDERING_TIP'); ?>><?= Text::_('JFIELD_ORDERING_LABEL'); ?></label>
        <div class="col-sm-9"><input type="number" class="form-control w-auto" id="jf_ordering" name="ordering" value="<?= (int) ($f->ordering ?? 0); ?>"></div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_RUNMODE_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_RUNMODE'); ?></label>
        <div class="col-sm-9">
          <select class="form-select" name="runmode">
            <option value="0"<?= (int) ($f->runmode ?? 0) === 0 ? ' selected' : ''; ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_RUNMODE_FRONTEND'); ?></option>
            <option value="1"<?= (int) ($f->runmode ?? 0) === 1 ? ' selected' : ''; ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_RUNMODE_BACKEND'); ?></option>
            <option value="2"<?= (int) ($f->runmode ?? 0) === 2 ? ' selected' : ''; ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_RUNMODE_BOTH'); ?></option>
          </select>
        </div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_DBLOG_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_DBLOG'); ?></label>
        <div class="col-sm-9 d-flex gap-3">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="dblog" id="dbl1" value="1"<?= (int) ($f->dblog ?? 1) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="dbl1"><?= Text::_('JYES'); ?></label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="dblog" id="dbl0" value="0"<?= !(int) ($f->dblog ?? 1) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="dbl0"><?= Text::_('JNO'); ?></label>
          </div>
        </div>
      </div>

    </div><!-- /tab general -->

    <!-- TAB: NOTIFICATIONS EMAIL -->
    <div class="tab-pane fade" id="pane-email" role="tabpanel">
      <ul class="nav nav-tabs" id="<?= $tabId; ?>-email" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="tab-email-admin" data-bs-toggle="tab" data-bs-target="#pane-email-admin" type="button" role="tab" aria-controls="pane-email-admin" aria-selected="true"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_EMAIL_ADMIN'); ?></button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-email-user" data-bs-toggle="tab" data-bs-target="#pane-email-user" type="button" role="tab" aria-controls="pane-email-user" aria-selected="false"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_EMAIL_USER'); ?></button>
        </li>
      </ul>
      <div class="tab-content pt-3">
        <div class="tab-pane fade show active" id="pane-email-admin" role="tabpanel" aria-labelledby="tab-email-admin">

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_EMAILNTF_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_EMAILNTF'); ?></label>
        <div class="col-sm-9 d-flex gap-3">
          <?php foreach ([0 => 'JNONE', 1 => 'JYES', 2 => 'COM_BREEZINGFORMSNG_FORMS_EMAILLOG_ONLY'] as $v => $key): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="emailntf" id="entf<?= $v; ?>" value="<?= $v; ?>"<?= (int) ($f->emailntf ?? 1) === $v ? ' checked' : ''; ?>>
              <label class="form-check-label" for="entf<?= $v; ?>"><?= Text::_($key); ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_emailadr"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_EMAILADR_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_EMAILADR'); ?></label>
        <div class="col-sm-9"><input type="text" class="form-control" id="jf_emailadr" name="emailadr" value="<?= htmlspecialchars($f->emailadr ?? ''); ?>"></div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_custom_mail_subject"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_MAILSUBJECT_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MAILSUBJECT'); ?></label>
        <div class="col-sm-9"><input type="text" class="form-control" id="jf_custom_mail_subject" name="custom_mail_subject" value="<?= htmlspecialchars($f->custom_mail_subject ?? ''); ?>"></div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_alt_mailfrom"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_MAILFROM_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MAILFROM'); ?></label>
        <div class="col-sm-9"><input type="text" class="form-control" id="jf_alt_mailfrom" name="alt_mailfrom" value="<?= htmlspecialchars($f->alt_mailfrom ?? ''); ?>"></div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_alt_fromname"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_FROMNAME_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_FROMNAME'); ?></label>
        <div class="col-sm-9"><input type="text" class="form-control" id="jf_alt_fromname" name="alt_fromname" value="<?= htmlspecialchars($f->alt_fromname ?? ''); ?>"></div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_emaillog"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_REPORT_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_REPORT'); ?></label>
        <div class="col-sm-9">
          <select class="form-select w-auto" id="jf_emaillog" name="emaillog">
            <?php foreach ([0 => 'COM_BREEZINGFORMSNG_FORMS_HDRONLY', 1 => 'COM_BREEZINGFORMSNG_FORMS_NONEMPTY', 2 => 'COM_BREEZINGFORMSNG_FORMS_ALLVALS'] as $v => $key) : ?>
              <option value="<?= $v; ?>"<?= (int) ($f->emaillog ?? 1) === $v ? ' selected' : ''; ?>><?= Text::_($key); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_emailxml"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_EMAIL_ATTACHMENT_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_ATTACHMENT'); ?></label>
        <div class="col-sm-9">
          <select class="form-select w-auto" id="jf_emailxml" name="emailxml">
            <?php // 2 is the former "XML: all values": the engine sends it as XML.
            $attachment = (int) ($f->emailxml ?? 0) === 2 ? 1 : (int) ($f->emailxml ?? 0);
            foreach ([0 => Text::_('JNO'), 1 => 'XML', 3 => 'CSV', 4 => 'PDF'] as $v => $label) : ?>
              <option value="<?= $v; ?>"<?= $attachment === $v ? ' selected' : ''; ?>><?= $label; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_EMAIL_TYPE_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MB_EMAIL_BODY'); ?></label>
        <div class="col-sm-9 d-flex gap-3">
          <?php foreach ([0 => 'COM_BREEZINGFORMSNG_EMAIL_TYPE_DEFAULT', 1 => 'COM_BREEZINGFORMSNG_EMAIL_TYPE_CUSTOM'] as $v => $key) : ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="email_type" id="etype<?= $v; ?>" value="<?= $v; ?>" data-bf-mail-template-toggle="bfEmailTemplate" data-bf-mail-default-toggle="bfEmailDefault"<?= $customEmail === ($v === 1) ? ' checked' : ''; ?>>
              <label class="form-check-label" for="etype<?= $v; ?>"><?= Text::_($key); ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div id="bfEmailDefault" class="row mb-3"<?= $customEmail ? ' hidden' : ''; ?>>
        <div class="col-sm-9 offset-sm-3">
          <div class="form-text mb-2">
            <?php if ($defaultBody['file'] !== '') : ?>
                <?= Text::sprintf('COM_BREEZINGFORMSNG_FORMS_MB_EMAIL_DEFAULT_FILE', htmlspecialchars($defaultBody['file'], ENT_QUOTES, 'UTF-8')); ?>
            <?php else : ?>
                <?= Text::_('COM_BREEZINGFORMSNG_FORMS_EMAIL_DEFAULT_HINT'); ?>
            <?php endif; ?>
          </div>
          <pre class="border rounded p-3 mb-0 overflow-auto bg-body-tertiary" style="max-height:400px;white-space:pre-wrap"><?= htmlspecialchars($defaultBody['content'], ENT_QUOTES, 'UTF-8'); ?></pre>
        </div>
      </div>

      <div id="bfEmailTemplate"<?= $customEmail ? '' : ' hidden'; ?>>
        <div class="row mb-3">
          <label class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_EMAIL_HTML_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_EMAIL_CUSTOM_HTML'); ?></label>
          <div class="col-sm-9 d-flex gap-3">
            <?php foreach ([1 => 'JYES', 0 => 'JNO'] as $v => $key) : ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="email_custom_html" id="ehtml<?= $v; ?>" value="<?= $v; ?>"<?= $emailHtml === $v ? ' checked' : ''; ?>>
                <label class="form-check-label" for="ehtml<?= $v; ?>"><?= Text::_($key); ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-3">
            <div class="form-text mb-2"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MB_EMAIL_FIELDS_HINT'); ?></div>
            <div class="overflow-auto" style="max-height:400px">
              <?php foreach ($mailFieldNames as $mailFieldName) : ?>
                    <?php foreach (['label', 'value'] as $mailFieldPart) : ?>
                        <?php $mailPlaceholder = htmlspecialchars('{' . $mailFieldName . ':' . $mailFieldPart . '}', ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn btn-link btn-sm d-block p-0 text-start" data-bf-mail-placeholder="<?= $mailPlaceholder; ?>" data-bf-mail-target="jf_email_custom_template"><?= $mailPlaceholder; ?></button>
                    <?php endforeach; ?>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="col-sm-9">
            <div class="form-text mb-2"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MB_EMAIL_SOURCE_HINT'); ?></div>
            <?= $mailEditor->display('email_custom_template', htmlspecialchars((string) ($f->email_custom_template ?? ''), ENT_QUOTES, 'UTF-8'), '100%', '400px', 60, 20, false, 'jf_email_custom_template'); ?>
          </div>
        </div>
      </div>

        </div>
        <div class="tab-pane fade" id="pane-email-user" role="tabpanel" aria-labelledby="tab-email-user">

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_MB_EMAILNTF_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MB_EMAILNTF'); ?></label>
        <div class="col-sm-9 d-flex gap-3">
          <?php foreach ([0 => 'JNONE', 1 => 'JYES', 2 => 'COM_BREEZINGFORMSNG_FORMS_EMAILLOG_ONLY'] as $v => $key): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="mb_emailntf" id="mbentf<?= $v; ?>" value="<?= $v; ?>"<?= (int) ($f->mb_emailntf ?? 1) === $v ? ' checked' : ''; ?>>
              <label class="form-check-label" for="mbentf<?= $v; ?>"><?= Text::_($key); ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_mb_custom_mail_subject"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_MB_MAILSUBJECT_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MB_MAILSUBJECT'); ?></label>
        <div class="col-sm-9"><input type="text" class="form-control" id="jf_mb_custom_mail_subject" name="mb_custom_mail_subject" value="<?= htmlspecialchars($f->mb_custom_mail_subject ?? ''); ?>"></div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_mb_alt_mailfrom"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_MB_MAILFROM_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MB_MAILFROM'); ?></label>
        <div class="col-sm-9"><input type="text" class="form-control" id="jf_mb_alt_mailfrom" name="mb_alt_mailfrom" value="<?= htmlspecialchars($f->mb_alt_mailfrom ?? ''); ?>"></div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_mb_alt_fromname"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_MB_FROMNAME_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MB_FROMNAME'); ?></label>
        <div class="col-sm-9"><input type="text" class="form-control" id="jf_mb_alt_fromname" name="mb_alt_fromname" value="<?= htmlspecialchars($f->mb_alt_fromname ?? ''); ?>"></div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_mb_emaillog"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_MB_REPORT_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_REPORT'); ?></label>
        <div class="col-sm-9">
          <select class="form-select w-auto" id="jf_mb_emaillog" name="mb_emaillog">
            <?php foreach ([0 => 'COM_BREEZINGFORMSNG_FORMS_HDRONLY', 1 => 'COM_BREEZINGFORMSNG_FORMS_NONEMPTY', 2 => 'COM_BREEZINGFORMSNG_FORMS_ALLVALS'] as $v => $key) : ?>
              <option value="<?= $v; ?>"<?= (int) ($f->mb_emaillog ?? 1) === $v ? ' selected' : ''; ?>><?= Text::_($key); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_mb_emailxml"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_MB_EMAILXML_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_ATTACHMENT'); ?></label>
        <div class="col-sm-9">
          <select class="form-select w-auto" id="jf_mb_emailxml" name="mb_emailxml">
            <?php // 2 is the former "XML: all values": the engine sends it as XML.
            $mbAttachment = (int) ($f->mb_emailxml ?? 0) === 2 ? 1 : (int) ($f->mb_emailxml ?? 0);
            foreach ([0 => Text::_('JNO'), 1 => 'XML', 3 => 'CSV', 4 => 'PDF'] as $v => $label) : ?>
              <option value="<?= $v; ?>"<?= $mbAttachment === $v ? ' selected' : ''; ?>><?= $label; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row mb-3">
        <label class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_MB_EMAIL_TYPE_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MB_EMAIL_BODY'); ?></label>
        <div class="col-sm-9 d-flex gap-3">
          <?php foreach ([0 => 'COM_BREEZINGFORMSNG_EMAIL_TYPE_DEFAULT', 1 => 'COM_BREEZINGFORMSNG_EMAIL_TYPE_CUSTOM'] as $v => $key) : ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="mb_email_type" id="mbetype<?= $v; ?>" value="<?= $v; ?>" data-bf-mail-template-toggle="bfMbEmailTemplate" data-bf-mail-default-toggle="bfMbEmailDefault"<?= $mbCustomEmail === ($v === 1) ? ' checked' : ''; ?>>
              <label class="form-check-label" for="mbetype<?= $v; ?>"><?= Text::_($key); ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div id="bfMbEmailDefault" class="row mb-3"<?= $mbCustomEmail ? ' hidden' : ''; ?>>
        <div class="col-sm-9 offset-sm-3">
          <div class="form-text mb-2">
            <?php if ($mbDefaultBody['file'] !== '') : ?>
                <?= Text::sprintf('COM_BREEZINGFORMSNG_FORMS_MB_EMAIL_DEFAULT_FILE', htmlspecialchars($mbDefaultBody['file'], ENT_QUOTES, 'UTF-8')); ?>
            <?php else : ?>
                <?= Text::sprintf('COM_BREEZINGFORMSNG_FORMS_MB_EMAIL_DEFAULT_HINT', Text::_('COM_BREEZINGFORMSNG_FORMS_REPORT'), Text::_('COM_BREEZINGFORMSNG_FORMS_EMAIL_ADMIN')); ?>
            <?php endif; ?>
          </div>
          <pre class="border rounded p-3 mb-0 overflow-auto bg-body-tertiary" style="max-height:400px;white-space:pre-wrap"><?= htmlspecialchars($mbDefaultBody['content'], ENT_QUOTES, 'UTF-8'); ?></pre>
        </div>
      </div>

      <div id="bfMbEmailTemplate"<?= $mbCustomEmail ? '' : ' hidden'; ?>>
        <div class="row mb-3">
          <label class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_MB_EMAIL_HTML_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_EMAIL_CUSTOM_HTML'); ?></label>
          <div class="col-sm-9 d-flex gap-3">
            <?php foreach ([1 => 'JYES', 0 => 'JNO'] as $v => $key) : ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="mb_email_custom_html" id="mbehtml<?= $v; ?>" value="<?= $v; ?>"<?= $mbEmailHtml === $v ? ' checked' : ''; ?>>
                <label class="form-check-label" for="mbehtml<?= $v; ?>"><?= Text::_($key); ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-3">
            <div class="form-text mb-2"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MB_EMAIL_FIELDS_HINT'); ?></div>
            <div class="overflow-auto" style="max-height:400px">
              <?php foreach ($mailFieldNames as $mailFieldName) : ?>
                    <?php foreach (['label', 'value'] as $mailFieldPart) : ?>
                        <?php $mailPlaceholder = htmlspecialchars('{' . $mailFieldName . ':' . $mailFieldPart . '}', ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn btn-link btn-sm d-block p-0 text-start" data-bf-mail-placeholder="<?= $mailPlaceholder; ?>" data-bf-mail-target="jf_mb_email_custom_template"><?= $mailPlaceholder; ?></button>
                    <?php endforeach; ?>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="col-sm-9">
            <div class="form-text mb-2"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_MB_EMAIL_SOURCE_HINT'); ?></div>
            <?= $mailEditor->display('mb_email_custom_template', htmlspecialchars((string) ($f->mb_email_custom_template ?? ''), ENT_QUOTES, 'UTF-8'), '100%', '400px', 60, 20, false, 'jf_mb_email_custom_template'); ?>
          </div>
        </div>
      </div>

        </div>
      </div>
    </div><!-- /tab email -->

    <!-- TAB: SCRIPTS & PIÈCES -->
    <div class="tab-pane fade" id="pane-scripts" role="tabpanel">

      <!-- Init script -->
      <div class="card mb-3">
        <div class="card-header"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_SCRIPT_INIT'); ?></div>
        <div class="card-body">
          <div class="row mb-2">
            <label class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_SCRIPT_INIT_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_SCRIPT_SOURCE'); ?></label>
            <div class="col-sm-9 d-flex gap-3">
              <?php foreach ([0 => 'JNONE', 1 => 'COM_BREEZINGFORMSNG_FORMS_LIBRARY', 2 => 'COM_BREEZINGFORMSNG_FORMS_INLINE'] as $v => $k): ?>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="script1cond" id="s1c<?= $v; ?>" value="<?= $v; ?>"
                         onchange="bfToggle('s1lib','s1code',this.value)"<?= (int) ($f->script1cond ?? 0) === $v ? ' checked' : ''; ?>>
                  <label class="form-check-label" for="s1c<?= $v; ?>"><?= Text::_($k); ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div id="s1lib" style="display:<?= (int) ($f->script1cond ?? 0) === 1 ? '' : 'none'; ?>">
            <?= FormsAdvancedOptionsHtml::bfSel($initScripts, 'script1id', (int) ($f->script1id ?? 0)); ?>
          </div>
          <div id="s1code" style="display:<?= (int) ($f->script1cond ?? 0) === 2 ? '' : 'none'; ?>">
            <?= $editor->display('script1code', htmlspecialchars($f->script1code ?? ''), '100%', '200px', 60, 10, false, 'jf_script1code', null, null, ['syntax' => 'javascript']); ?>
          </div>
        </div>
      </div>

      <!-- Submitted script -->
      <div class="card mb-3">
        <div class="card-header"><?= Text::_('COM_BREEZINGFORMSNG_FORMS_SCRIPT_SUBMITTED'); ?></div>
        <div class="card-body">
          <div class="row mb-2">
            <label class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_FORMS_SCRIPT_SUBMITTED_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_SCRIPT_SOURCE'); ?></label>
            <div class="col-sm-9 d-flex gap-3">
              <?php foreach ([0 => 'JNONE', 1 => 'COM_BREEZINGFORMSNG_FORMS_LIBRARY', 2 => 'COM_BREEZINGFORMSNG_FORMS_INLINE'] as $v => $k): ?>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="script2cond" id="s2c<?= $v; ?>" value="<?= $v; ?>"
                         onchange="bfToggle('s2lib','s2code',this.value)"<?= (int) ($f->script2cond ?? 0) === $v ? ' checked' : ''; ?>>
                  <label class="form-check-label" for="s2c<?= $v; ?>"><?= Text::_($k); ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div id="s2lib" style="display:<?= (int) ($f->script2cond ?? 0) === 1 ? '' : 'none'; ?>">
            <?= FormsAdvancedOptionsHtml::bfSel($submittedScripts, 'script2id', (int) ($f->script2id ?? 0)); ?>
          </div>
          <div id="s2code" style="display:<?= (int) ($f->script2cond ?? 0) === 2 ? '' : 'none'; ?>">
            <?= $editor->display('script2code', htmlspecialchars($f->script2code ?? ''), '100%', '200px', 60, 10, false, 'jf_script2code', null, null, ['syntax' => 'javascript']); ?>
          </div>
        </div>
      </div>

    </div><!-- /tab scripts -->

    <?php
    $pieceLabels = [
        1 => ['COM_BREEZINGFORMSNG_FORMS_PIECE_BEFORE', $pieceBefore, 'piece1cond', 'piece1id', 'piece1code', 'p1'],
        2 => ['COM_BREEZINGFORMSNG_FORMS_PIECE_AFTER', $pieceAfter, 'piece2cond', 'piece2id', 'piece2code', 'p2'],
        3 => ['COM_BREEZINGFORMSNG_FORMS_PIECE_BEGIN_SUBMIT', $pieceBeginSubmit, 'piece3cond', 'piece3id', 'piece3code', 'p3'],
        4 => ['COM_BREEZINGFORMSNG_FORMS_PIECE_END_SUBMIT', $pieceEndSubmit, 'piece4cond', 'piece4id', 'piece4code', 'p4'],
    ];
    foreach (['form-pieces' => [1, 2], 'submit-pieces' => [3, 4]] as $piecePane => $pieceIndexes): ?>
    <div class="tab-pane fade" id="pane-<?= $piecePane; ?>" role="tabpanel">
      <?php
      foreach ($pieceIndexes as $pieceIndex):
      [$label, $pieces, $condName, $idName, $codeName, $pfx] = $pieceLabels[$pieceIndex]; ?>
      <div class="card mb-3">
        <div class="card-header"><?= Text::_($label); ?></div>
        <div class="card-body">
          <div class="row mb-2">
            <label class="col-sm-3 col-form-label"<?= $labelTip($label . '_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_FORMS_SCRIPT_SOURCE'); ?></label>
            <div class="col-sm-9 d-flex gap-3">
              <?php foreach ([0 => 'JNONE', 1 => 'COM_BREEZINGFORMSNG_FORMS_LIBRARY', 2 => 'COM_BREEZINGFORMSNG_FORMS_INLINE'] as $v => $k): ?>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="<?= $condName; ?>" id="<?= $pfx; ?>c<?= $v; ?>"
                         value="<?= $v; ?>" onchange="bfToggle('<?= $pfx; ?>lib','<?= $pfx; ?>code',this.value)"
                         <?= (int) ($f->$condName ?? 0) === $v ? ' checked' : ''; ?>>
                  <label class="form-check-label" for="<?= $pfx; ?>c<?= $v; ?>"><?= Text::_($k); ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div id="<?= $pfx; ?>lib" style="display:<?= (int) ($f->$condName ?? 0) === 1 ? '' : 'none'; ?>">
            <?php if (isset($missingPieces[$pieceIndex])): ?>
              <div class="alert alert-danger" role="alert">
                <span class="icon-warning" aria-hidden="true"></span>
                <?= $missingPieces[$pieceIndex] > 0
                    ? Text::sprintf('COM_BREEZINGFORMSNG_FORMS_PIECE_NOT_FOUND', $missingPieces[$pieceIndex])
                    : Text::_('COM_BREEZINGFORMSNG_FORMS_PIECE_NOT_CHOSEN'); ?>
              </div>
            <?php endif; ?>
            <?= FormsAdvancedOptionsHtml::bfSel($pieces, $idName, (int) ($f->$idName ?? 0), isset($missingPieces[$pieceIndex]) ? 'aria-invalid="true"' : ''); ?>
          </div>
          <div id="<?= $pfx; ?>code" style="display:<?= (int) ($f->$condName ?? 0) === 2 ? '' : 'none'; ?>">
            <?= $editor->display($codeName, htmlspecialchars($f->$codeName ?? ''), '100%', '200px', 60, 10, false, 'jf_' . $codeName, null, null, ['syntax' => 'php']); ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <div class="tab-pane fade" id="pane-mailchimp" role="tabpanel">
      <?php foreach ([
          'mailchimp_api_key' => 'COM_BREEZINGFORMSNG_API_KEY',
          'mailchimp_list_id' => 'COM_BREEZINGFORMSNG_LIST_ID',
          'mailchimp_email_field' => 'COM_BREEZINGFORMSNG_EMAIL_FIELD',
          'mailchimp_checkbox_field' => 'COM_BREEZINGFORMSNG_CHECKBOX_FIELD',
          'mailchimp_unsubscribe_field' => 'COM_BREEZINGFORMSNG_UNSUBSCRIBE_FIELD',
          'mailchimp_text_html_mobile_field' => 'COM_BREEZINGFORMSNG_TEXT_HTML_MOBILE_FIELD',
          'mailchimp_mergevars' => 'COM_BREEZINGFORMSNG_MERGE_VARS',
      ] as $name => $label): ?>
      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_<?= $name; ?>"<?= $labelTip($label . '_TIP'); ?>><?= Text::_($label); ?></label>
        <div class="col-sm-9"><input class="form-control" id="jf_<?= $name; ?>" name="<?= $name; ?>" value="<?= htmlspecialchars($f->$name ?? ''); ?>"></div>
      </div>
      <?php endforeach; ?>
      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_mailchimp_default_type"<?= $labelTip('COM_BREEZINGFORMSNG_DEFAULT_TYPE_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_DEFAULT_TYPE'); ?></label>
        <div class="col-sm-9"><select class="form-select" id="jf_mailchimp_default_type" name="mailchimp_default_type">
          <?php foreach (['text' => 'Text', 'html' => 'HTML', 'mobile' => 'Mobile'] as $value => $label): ?>
          <option value="<?= $value; ?>"<?= ($f->mailchimp_default_type ?? 'text') === $value ? ' selected' : ''; ?>><?= $label; ?></option>
          <?php endforeach; ?>
        </select></div>
      </div>
      <?php foreach ([
          'mailchimp_double_optin' => 'COM_BREEZINGFORMSNG_DOUBLE_OPTIN',
          'mailchimp_delete_member' => 'COM_BREEZINGFORMSNG_UNSUBSCRIBE_DELETE_MEMBER',
          'mailchimp_send_errors' => 'COM_BREEZINGFORMSNG_SEND_ERRORS',
      ] as $name => $label): ?>
      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_<?= $name; ?>"<?= $labelTip($label . '_TIP'); ?>><?= Text::_($label); ?></label>
        <div class="col-sm-9 form-check form-switch"><input type="hidden" name="<?= $name; ?>" value="0"><input class="form-check-input" id="jf_<?= $name; ?>" type="checkbox" name="<?= $name; ?>" value="1"<?= !empty($f->$name) ? ' checked' : ''; ?>></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="tab-pane fade" id="pane-salesforce" role="tabpanel">
      <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="jf_salesforce_enabled"<?= $labelTip('COM_BREEZINGFORMSNG_SF_ENABLED_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_SF_ENABLED'); ?></label>
        <div class="col-sm-9 form-check form-switch"><input type="hidden" name="salesforce_enabled" value="0"><input class="form-check-input" id="jf_salesforce_enabled" type="checkbox" name="salesforce_enabled" value="1"<?= !empty($f->salesforce_enabled) ? ' checked' : ''; ?>></div>
      </div>
      <?php foreach ([
          'salesforce_token' => 'COM_BREEZINGFORMSNG_SF_TOKEN',
          'salesforce_username' => 'COM_BREEZINGFORMSNG_SF_USERNAME',
          'salesforce_type' => 'COM_BREEZINGFORMSNG_SF_TYPE',
      ] as $name => $label): ?>
      <div class="row mb-3"><label class="col-sm-3 col-form-label" for="jf_<?= $name; ?>"<?= $labelTip($label . '_TIP'); ?>><?= Text::_($label); ?></label><div class="col-sm-9"><input class="form-control" id="jf_<?= $name; ?>" name="<?= $name; ?>" value="<?= htmlspecialchars($f->$name ?? ''); ?>"></div></div>
      <?php endforeach; ?>
      <div class="row mb-3"><label class="col-sm-3 col-form-label" for="jf_salesforce_password"<?= $labelTip('COM_BREEZINGFORMSNG_SF_PASSWORD_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_SF_PASSWORD'); ?></label><div class="col-sm-9"><input type="password" class="form-control" id="jf_salesforce_password" name="salesforce_password" autocomplete="new-password"></div></div>
      <div class="row mb-3"><label class="col-sm-3 col-form-label" for="jf_salesforce_fields"<?= $labelTip('COM_BREEZINGFORMSNG_SF_FIELDS_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_SF_FIELDS'); ?></label><div class="col-sm-9"><input class="form-control" id="jf_salesforce_fields" name="salesforce_fields[]" value="<?= htmlspecialchars($f->salesforce_fields ?? ''); ?>"></div></div>
    </div>

    <div class="tab-pane fade" id="pane-dropbox" role="tabpanel">
      <?php foreach ([
          'dropbox_email' => 'COM_BREEZINGFORMSNG_DROPBOX_ACCESS_TOKEN',
          'dropbox_password' => 'COM_BREEZINGFORMSNG_DROPBOX_AUTH_CODE',
          'dropbox_folder' => 'COM_BREEZINGFORMSNG_DROPBOX_FOLDER',
      ] as $name => $label): ?>
      <div class="row mb-3"><label class="col-sm-3 col-form-label" for="jf_<?= $name; ?>"<?= $labelTip($label . '_TIP'); ?>><?= Text::_($label); ?></label><div class="col-sm-9"><input class="form-control" id="jf_<?= $name; ?>" name="<?= $name; ?>" value="<?= htmlspecialchars($f->$name ?? ''); ?>"></div></div>
      <?php endforeach; ?>
      <div class="row mb-3"><label class="col-sm-3 col-form-label" for="jf_dropbox_submission_enabled"<?= $labelTip('COM_BREEZINGFORMSNG_DROPBOX_UPLOAD_SUBMISSION_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_DROPBOX_UPLOAD_SUBMISSION'); ?></label><div class="col-sm-9 form-check form-switch"><input type="hidden" name="dropbox_submission_enabled" value="0"><input class="form-check-input" id="jf_dropbox_submission_enabled" type="checkbox" name="dropbox_submission_enabled" value="1"<?= !empty($f->dropbox_submission_enabled) ? ' checked' : ''; ?>></div></div>
      <div class="row mb-3"><span class="col-sm-3 col-form-label"<?= $labelTip('COM_BREEZINGFORMSNG_DROPBOX_SUBMISSION_TYPES_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_DROPBOX_SUBMISSION_TYPES'); ?></span><div class="col-sm-9 d-flex gap-3">
        <?php $dropboxTypes = explode(',', (string) ($f->dropbox_submission_types ?? 'pdf')); foreach (['pdf', 'csv', 'xml'] as $type): ?>
        <label class="form-check"><input class="form-check-input" type="checkbox" name="dropbox_submission_types[]" value="<?= $type; ?>"<?= in_array($type, $dropboxTypes, true) ? ' checked' : ''; ?>> <?= strtoupper($type); ?></label>
        <?php endforeach; ?>
      </div></div>
      <div class="row mb-3"><label class="col-sm-3 col-form-label" for="jf_dropbox_reset_auth"<?= $labelTip('COM_BREEZINGFORMSNG_DROPBOX_RESET_AUTH_TIP'); ?>><?= Text::_('COM_BREEZINGFORMSNG_DROPBOX_RESET_AUTH'); ?></label><div class="col-sm-9 form-check"><input class="form-check-input" id="jf_dropbox_reset_auth" type="checkbox" name="dropbox_reset_auth" value="1"></div></div>
    </div>

  </div><!-- /.tab-content -->
