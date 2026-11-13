<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Recordstransfer;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordTransfer;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordstransferModel;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    private const LAYOUTS = ['select', 'mapping', 'confirm', 'report'];

    /** @var list<\stdClass> */
    public array $records = [];
    /** @var list<\stdClass> */
    public array $forms = [];
    /** @var list<array{id: int|string, name: string, title: string, type: string}> */
    public array $sourceElements = [];
    /** @var list<array{id: int|string, name: string, title: string, type: string}> */
    public array $targetElements = [];
    /** @var list<array{id: int|string, name: string, title: string, type: string}> The source fields, by name. */
    public array $sourceOptions = [];
    /** @var array<int, int> */
    public array $map = [];
    /** @var array<int, string> */
    public array $defaults = [];
    /** @var int[] */
    public array $keptIds = [];
    public int $sourceId = 0;
    public int $targetId = 0;
    public string $sourceTitle = '';
    public string $targetTitle = '';
    /** @var array<int, string> What the first record gives each target field. */
    public array $example = [];
    /** @var int[] Required fields of the target form. */
    public array $requiredIds = [];
    public bool $sourceDeleted = false;
    public bool $trash = false;
    public bool $canTrash = false;
    /** @var array<string, mixed> */
    public array $report = [];
    /** The screen shown: the layout of the request, rendered by the default layout. */
    public string $step = 'select';

    public function display($tpl = null)
    {
        $app = Factory::getApplication();

        if (!$app instanceof CMSApplication) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $user = $this->getCurrentUser();

        if (!$user->authorise('core.create', 'com_breezingformsng')) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $model = $this->getModel();

        if (!$model instanceof RecordstransferModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $layout = in_array($this->getLayout(), self::LAYOUTS, true) ? $this->getLayout() : 'select';
        $this->step = $layout;
        $this->setLayout('default');
        $transfer = $model->getTransfer();
        $needsTarget = $layout !== 'select';

        if (
            !isset($transfer['all'], $transfer['ids'], $transfer['source'])
            || ($needsTarget && (int) ($transfer['target'] ?? 0) < 1)
            || ($layout === 'report') !== is_array($transfer['report'] ?? null)
        ) {
            $app->enqueueMessage(Text::_('COM_BREEZINGFORMSNG_TRANSFER_ERROR_EXPIRED'), 'warning');
            $app->redirect(Route::_('index.php?option=com_breezingformsng&view=records', false));

            return;
        }

        $this->sourceId = (int) $transfer['source'];
        $this->targetId = (int) ($transfer['target'] ?? 0);
        $this->keptIds = array_map('intval', (array) $transfer['ids']);
        $this->map = array_map('intval', (array) ($transfer['map'] ?? []));
        $this->defaults = array_map('strval', (array) ($transfer['defaults'] ?? []));
        $this->trash = (bool) ($transfer['trash'] ?? false);
        $this->canTrash = $user->authorise('core.edit.state', 'com_breezingformsng');
        $this->report = (array) ($transfer['report'] ?? []);

        $this->forms = $model->getForms();
        usort($this->forms, fn (\stdClass $a, \stdClass $b): int => $this->compareNames((string) $a->title, (string) $b->title));
        $titles = [];

        foreach ($this->forms as $form) {
            $titles[(int) $form->id] = (string) $form->title;
        }

        $this->sourceDeleted = !isset($titles[$this->sourceId]);
        $this->sourceTitle = $titles[$this->sourceId] ?? '';
        $this->targetTitle = $titles[$this->targetId] ?? '';
        $this->records = $model->getRecords($layout === 'select' ? (array) $transfer['all'] : $this->keptIds);

        // A deleted form keeps no title: its records kept the one they were submitted with.
        if ($this->sourceTitle === '' && $this->records !== []) {
            $this->sourceTitle = (string) $this->records[0]->form_title;
        }

        if ($layout === 'mapping' || $layout === 'confirm') {
            $this->sourceElements = $model->getSourceElements($this->sourceId, (array) $transfer['all']);
            $this->targetElements = $model->getElements($this->targetId);
            $this->sourceOptions = $this->sourceElements;
            usort($this->sourceOptions, fn (array $a, array $b): int => $this->compareNames($this->elementText($a), $this->elementText($b)));
            if ($layout === 'confirm' && $this->records !== []) {
                $values = $model->getRecordValues((int) $this->records[0]->id, $this->sourceId, (string) $this->records[0]->name);

                foreach ($this->targetElements as $element) {
                    $targetId = (int) $element['id'];
                    $this->example[$targetId] = RecordTransfer::composeValue(
                        $this->map[$targetId] ?? 0,
                        $values,
                        $this->defaults[$targetId] ?? ''
                    );
                }
            }

            $this->requiredIds = array_keys(array_filter(
                $model->getTargetFields($this->targetId),
                static fn (array $field): bool => $field['required']
            ));
        }

        $app->getInput()->set('hidemainmenu', true);
        $this->prepareToolbar($layout);

        parent::display($tpl);
    }

    private function prepareToolbar(string $layout): void
    {
        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar() ?? throw new \RuntimeException('The toolbar is not available.');
        $base = 'index.php?option=com_breezingformsng&view=recordstransfer&layout=';

        match ($layout) {
            'select' => $toolbar->standardButton('next', 'JNEXT', 'recordstransfer.saveSelection')
                ->icon('icon-arrow-right')
                ->buttonClass('btn btn-success'),
            'mapping' => $toolbar->standardButton('next', 'JNEXT', 'recordstransfer.saveMapping')
                ->icon('icon-arrow-right')
                ->buttonClass('btn btn-success'),
            'confirm' => $toolbar->standardButton('transfer', 'COM_BREEZINGFORMSNG_TRANSFER_CONFIRM', 'recordstransfer.transfer')
                ->icon('icon-copy')
                ->buttonClass('btn btn-success'),
            default => null,
        };

        $previous = ['mapping' => 'select', 'confirm' => 'mapping'][$layout] ?? null;

        if ($previous !== null) {
            $toolbar->linkButton('back', 'JPREVIOUS')
                ->url(Route::_($base . $previous))
                ->icon('icon-arrow-left');
        }

        $toolbar->standardButton('cancel', $layout === 'report' ? 'JTOOLBAR_CLOSE' : 'JTOOLBAR_CANCEL', 'recordstransfer.cancel')
            ->icon('icon-times')
            ->buttonClass('btn btn-danger');
        $document->getWebAssetManager()->useScript('keepalive');

        if ($layout === 'mapping') {
            $document->getWebAssetManager()->useStyle('com_breezingformsng.records-transfer');
            // A default value is for a field that no source field fills.
            $document->getWebAssetManager()->registerAndUseScript(
                'com_breezingformsng.records-transfer',
                'media/com_breezingformsng/js/admin/records-transfer.js',
                ['version' => 'auto'],
                ['defer' => true],
                ['core']
            );
        }
    }

    protected function getDetailLabel(): ?string
    {
        return Text::_('COM_BREEZINGFORMSNG_TRANSFER_TITLE');
    }

    /**
     * @param array{id: int|string, name: string, title: string, type: string} $element
     */
    public function elementText(array $element): string
    {
        $title = trim((string) $element['title']);
        $name = (string) $element['name'];

        return $title === '' || $title === $name ? $name : $title . ' (' . $name . ')';
    }

    /**
     * @param array{id: int|string, name: string, title: string, type: string} $element
     */
    public function elementLabel(array $element): string
    {
        return $this->escape($this->elementText($element));
    }

    /**
     * Alphabetical order of names, ignoring the case and the numbers' length.
     */
    private function compareNames(string $a, string $b): int
    {
        return strnatcmp(mb_strtolower($a), mb_strtolower($b));
    }

    /**
     * @param array{id: int|string, name: string, title: string, type: string} $element
     *
     * @return string The URL of the icon the QuickMode tree of the form screen shows for the type of the field.
     */
    public function elementIconUrl(array $element): string
    {
        $icon = RecordTransfer::iconFor((string) $element['type']);

        return $icon === null ? '' : Uri::root(true) . '/media/com_breezingformsng/images/quickmode/' . $icon;
    }

    /**
     * @param array{id: int|string, name: string, title: string, type: string} $element
     *
     * @return string The CSS class that gives an option its icon, empty for a type without icon.
     */
    public function elementIconClass(array $element): string
    {
        $icon = RecordTransfer::iconFor((string) $element['type']);

        return $icon === null ? '' : 'bf-icon-' . substr($icon, 5, -4);
    }

    /**
     * The icon of the field, to put before its name.
     *
     * @param array{id: int|string, name: string, title: string, type: string} $element
     */
    public function elementIcon(array $element): string
    {
        $url = $this->elementIconUrl($element);

        return $url === '' ? '' : '<img src="' . $this->escape($url) . '" width="20" height="20" alt="" class="me-1 align-text-bottom">';
    }

    public function sourceFormLink(): string
    {
        // A deleted form has no editor to open.
        return $this->sourceDeleted ? $this->escape($this->sourceTitle) : $this->formLink($this->sourceId, $this->sourceTitle, 'bf-transfer-source-form');
    }

    public function targetFormLink(): string
    {
        return $this->formLink($this->targetId, $this->targetTitle, 'bf-transfer-target-form');
    }

    /**
     * The name of a form, linked to its editor in a new tab.
     */
    private function formLink(int $formId, string $title, string $id): string
    {
        $url = Route::_('index.php?option=com_breezingformsng&task=quickmode.display&form=' . $formId);

        return '<a id="' . $id . '" href="' . $this->escape($url) . '" target="_blank" rel="noopener noreferrer">'
            . $this->escape($title)
            . '<span class="visually-hidden">' . Text::_('JBROWSERTARGET_NEW') . '</span></a>';
    }
}
