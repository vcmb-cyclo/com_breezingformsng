<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\Quickmode\HtmlView $this */
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\QuickmodeHtml;

$iconBase = Uri::root() . 'media/com_breezingformsng/images/quickmode/';
$published = $this->published;
$debugMode = $this->debugMode;

// Build root JSON node when creating a new form (no saved template_code yet).
if ($this->formId === 0 || $this->templateCode === '') {
    $formNameJs = addslashes($this->formName);
    $o = '{
        attributes: {
            "class": "bfQuickModeRootClass",
            id: "bfQuickModeRoot",
            mdata: JSON.stringify({ type: "root" })
        },
        properties: {
            type: "root",
            title: "' . $formNameJs . '",
            name: "",
            rollover: true,
            rolloverColor: "#ffc",
            toggleFields: "",
            description: "",
            mailNotification: false,
            mailRecipient: "",
            submitInclude: true,
            submitLabel: "submit",
            cancelInclude: false,
            cancelLabel: "reset",
            pagingInclude: true,
            pagingNextLabel: "next",
            pagingPrevLabel: "back",
            theme: "default",
            themebootstrap: "",
            themebootstrapbefore: "",
            themebootstrapLabelTop: false,
            themebootstrapThemeEngine: "bootstrap",
            themebootstrapUseHeroUnit: false,
            themebootstrapUseWell: false,
            themebootstrapUseProgress: false,
            fadeIn: false,
            lastPageThankYou: false,
            submittedScriptCondidtion: 0,
            submittedScriptCode: "",
            useErrorAlerts: false,
            useDefaultErrors: true,
            useBalloonErrors: false,
            joomlaHint: false
        },
        state: "open",
        data: { title: "' . $formNameJs . '", icon: "' . $iconBase . 'icon_form.png" },
        children: []
    }';
} else {
    $o = $this->templateCode;
}

echo QuickmodeHtml::showApplication(
    $this->formId,
    $this->formName,
    $this->formTitle,
    $this->formDesc,
    $this->emailntf,
    $this->emailadr,
    $this->published,
    $this->debugMode,
    $o,
    $this->elementScripts,
    $this->themes,
    $this->themesBootstrap,
    [
        'form' => $this->advancedOptionsForm,
        'editor' => $this->advancedOptionsEditor,
        'tabEntryCounts' => $this->advancedOptionsTabEntryCounts,
        'initScripts' => $this->advancedOptionsInitScripts,
        'submittedScripts' => $this->advancedOptionsSubmittedScripts,
        'pieceBefore' => $this->advancedOptionsPieceBefore,
        'pieceAfter' => $this->advancedOptionsPieceAfter,
        'pieceBeginSubmit' => $this->advancedOptionsPieceBeginSubmit,
        'pieceEndSubmit' => $this->advancedOptionsPieceEndSubmit,
    ]
);

if ($this->formId > 0) {
    // Shown for a form in debug mode; the debug toggle shows and hides it.
    echo LayoutHelper::render('audit.accordion', [
        'id' => 'bf-form-audit',
        'created' => $this->auditCreated,
        'createdBy' => $this->auditCreatedBy,
        'modified' => $this->auditModified,
        'modifiedBy' => $this->auditModifiedBy,
        'timezone' => $this->timezone,
        'hidden' => !$debugMode,
    ], JPATH_ADMINISTRATOR . '/components/com_breezingformsng/layouts');
}
