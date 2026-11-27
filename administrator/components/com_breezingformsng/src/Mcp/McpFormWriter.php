<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp;

\defined('_JEXEC') or die;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormOptionsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ContentBuilderFormSync;
use Vcmb\Component\BreezingformsNG\Administrator\Service\FormVersioning;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ItemName;

/**
 * Creates forms and applies bounded, permission-checked updates through MCP.
 *
 * @phpstan-type QuickModeElement array{
 *     attributes: array{id: string},
 *     properties: array<string, mixed>,
 *     children: list<never>
 * }
 * @phpstan-type QuickModePage array{
 *     attributes: array{id: string},
 *     properties: array<string, mixed>,
 *     children: list<QuickModeElement>
 * }
 * @phpstan-type QuickModeTree array{
 *     attributes: array{id: string},
 *     properties: array<string, mixed>,
 *     children: list<QuickModePage>
 * }
 */
final class McpFormWriter
{
    private readonly McpFormInputNormalizer $input;
    private readonly McpQuickModeTree $tree;

    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly FormOptionsModel $options,
        private readonly FormModel $forms,
        private readonly QuickmodeModel $quickmode
    ) {
        $this->input = new McpFormInputNormalizer($db);
        $this->tree = new McpQuickModeTree($options, $quickmode, $this->input);
    }

    private function stringValue(mixed $value): string
    {
        return $this->input->stringValue($value);
    }

    private function intValue(mixed $value): int
    {
        return $this->input->intValue($value);
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array{id: int, name: string, title: string, published: false, field_count: int, versioned: bool, warnings?: list<string>}
     * @throws ToolException
     */
    public function create(array $arguments, User $user): array
    {
        if (!$user->authorise('core.create', 'com_breezingformsng')) {
            throw new ToolException('You are not allowed to create forms.');
        }

        $name = trim($this->stringValue($arguments['name'] ?? null));
        $title = trim($this->stringValue($arguments['title'] ?? null));
        if ($name === '' || $title === '') {
            throw new ToolException('Form name and title must not be empty.');
        }

        if (ItemName::problem($name) !== null) {
            throw new ToolException('The form name may only contain letters, digits and underscores.');
        }

        $description = trim($this->stringValue($arguments['description'] ?? ''));
        $pageIntro = $this->normalizePageIntro($arguments['page_intro'] ?? '');
        $fields = $this->normalizeFields($arguments['fields'] ?? []);
        $warnings = [];
        $this->db->transactionStart();
        try {
            $id = $this->quickmode->save(0, $this->quickmodeTree($name, $title, $description, $fields, $pageIntro), 0, false);
            $warnings = $this->codeWarnings();
            $versioned = $this->recordVersion($id);
            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            throw $e;
        }

        return [
            'id' => $id,
            'name' => $name,
            'title' => $title,
            'published' => false,
            'field_count' => count($fields),
            'versioned' => $versioned,
        ] + $warnings;
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array{id: int, name: string, title: string, published: bool, settings: array{width: int, widthmode: int, height: int, heightmode: int, autoheight: bool, email_notifications: bool}, versioned: bool}
     * @throws ToolException
     */
    public function update(array $arguments, User $user): array
    {
        if (!$user->authorise('core.edit', 'com_breezingformsng')) {
            throw new ToolException('You are not allowed to edit forms.');
        }

        $id = $this->intValue($arguments['id'] ?? null);
        $form = $this->options->getForm($id);
        if ($form === null || (int) $form->published === -2) {
            throw new ToolException('Form not found or cannot be edited.');
        }

        $data = ['id' => $id];
        foreach (['name', 'title', 'description'] as $field) {
            if (!array_key_exists($field, $arguments)) {
                continue;
            }

            $value = trim($this->stringValue($arguments[$field]));
            if ($field !== 'description' && $value === '') {
                throw new ToolException(sprintf('Form %s must not be empty.', $field));
            }
            $data[$field] = $value;
        }

        foreach (['width', 'widthmode', 'height', 'heightmode', 'autoheight'] as $field) {
            if (array_key_exists($field, $arguments)) {
                $data[$field] = $arguments[$field];
            }
        }

        if (array_key_exists('email_notifications', $arguments)) {
            $data['emailntf'] = $arguments['email_notifications'] ? 1 : 0;
        }
        if (array_key_exists('notification_recipient', $arguments)) {
            $recipient = trim($this->stringValue($arguments['notification_recipient']));
            if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                throw new ToolException('The notification recipient must be one valid email address.');
            }
            $data['emailadr'] = $recipient;
        }
        if (array_key_exists('email_notifications', $arguments) || array_key_exists('notification_recipient', $arguments)) {
            $notificationsEnabled = (bool) ($data['emailntf'] ?? $form->emailntf);
            $recipient = $this->stringValue($data['emailadr'] ?? $form->emailadr);
            if ($notificationsEnabled && filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                throw new ToolException('Set one valid notification recipient before enabling administrator notification emails.');
            }
        }

        if (array_key_exists('published', $arguments)) {
            if (!$user->authorise('core.edit.state', 'com_breezingformsng')) {
                throw new ToolException('You are not allowed to publish or unpublish forms.');
            }
            $data['published'] = $arguments['published'] ? 1 : 0;
        }

        if (array_key_exists('width', $data) || array_key_exists('widthmode', $data)) {
            $width = $this->intValue($data['width'] ?? $form->width);
            $widthMode = $this->intValue($data['widthmode'] ?? $form->widthmode);
            $minimumWidth = $widthMode === 1 ? 1 : 100;
            $maximumWidth = $widthMode === 1 ? 100 : 2000;
            if ($width < $minimumWidth || $width > $maximumWidth) {
                throw new ToolException(sprintf('The width must be between %d and %d for the selected width mode.', $minimumWidth, $maximumWidth));
            }
        }

        if (count($data) === 1) {
            throw new ToolException('Give at least one form metadata or settings change to update.');
        }

        if ((int) ($form->checked_out ?? 0) > 0) {
            throw new ToolException('The form is already checked out and cannot be edited now.');
        }

        if (!$this->forms->checkout($id)) {
            throw new ToolException('The form is checked out by another user and cannot be edited now.');
        }

        try {
            $versioning = new FormVersioning($this->db, $this->forms);
            $versioning->keepOrigin($id);
            $this->options->saveForm($data);
            $this->syncQuickModeRoot($id, $data);

            try {
                $versioning->record($id);
                $versioned = true;
            } catch (\Throwable) {
                // The metadata has been saved; report the history failure without
                // claiming that the mutation itself failed.
                $versioned = false;
            }
        } finally {
            $this->forms->checkin($id);
        }

        return [
            'id' => $id,
            'name' => array_key_exists('name', $data) ? $data['name'] : (string) $form->name,
            'title' => array_key_exists('title', $data) ? $data['title'] : (string) $form->title,
            'published' => (int) ($data['published'] ?? $form->published) === 1,
            'settings' => [
                'width' => (int) ($data['width'] ?? $form->width),
                'widthmode' => (int) ($data['widthmode'] ?? $form->widthmode),
                'height' => (int) ($data['height'] ?? $form->height),
                'heightmode' => (int) ($data['heightmode'] ?? $form->heightmode),
                'autoheight' => (bool) ($data['autoheight'] ?? $form->autoheight),
                'email_notifications' => (bool) ($data['emailntf'] ?? $form->emailntf),
            ],
            'versioned' => $versioned,
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array{id: int, added: list<string>, updated: list<string>, removed: list<string>, pages_added: int, pages_updated: list<int>, pages_removed: list<int>, visibility_rules_updated: bool, visibility_rule_count: int, field_count: int, versioned: bool, contentbuilder_synced: bool, warnings?: list<string>}
     * @throws ToolException
     */
    public function manageFields(array $arguments, User $user): array
    {
        if (!$user->authorise('core.edit', 'com_breezingformsng')) {
            throw new ToolException('You are not allowed to edit forms.');
        }

        $id = $this->intValue($arguments['id'] ?? null);
        $pagesToAdd = $arguments['add_pages'] ?? 0;
        if (!is_int($pagesToAdd) || $pagesToAdd < 0 || $pagesToAdd > 10) {
            throw new ToolException('At most 10 QuickMode pages can be added per call.');
        }
        $pagesToRemove = $arguments['remove_pages'] ?? [];
        if (!is_array($pagesToRemove) || !array_is_list($pagesToRemove) || count($pagesToRemove) > 10) {
            throw new ToolException('Page removals must be a list of at most 10 page numbers.');
        }
        if ($pagesToAdd > 0 && $pagesToRemove !== []) {
            throw new ToolException('Add pages and remove pages in separate calls so page numbers remain unambiguous.');
        }
        $pageUpdates = $arguments['update_pages'] ?? [];
        if (!is_array($pageUpdates) || !array_is_list($pageUpdates) || count($pageUpdates) > 10) {
            throw new ToolException('Page updates must be a list of at most 10 items.');
        }
        $visibilityRulesProvided = array_key_exists('visibility_rules', $arguments);
        $visibilityRules = $arguments['visibility_rules'] ?? [];
        if (!is_array($visibilityRules) || !array_is_list($visibilityRules) || count($visibilityRules) > 30) {
            throw new ToolException('Visibility rules must be a list of at most 30 rules.');
        }
        $form = $this->options->getForm($id);
        if ($form === null || (int) $form->published === -2) {
            throw new ToolException('Form not found or cannot be edited.');
        }

        $tree = $this->managedTree($id);
        $originalTree = $tree;
        $current = [];
        $currentPages = [];
        $currentNodeIds = [];
        $currentFieldNames = [];
        foreach ($tree['children'] as $pageIndex => $page) {
            foreach ($page['children'] as $node) {
                $properties = $node['properties'];
                $key = strtolower($this->stringValue($properties['bfName'] ?? null));
                if (isset($current[$key])) {
                    throw new ToolException('This form has duplicate field names and cannot be safely edited through MCP.');
                }
                $current[$key] = $properties;
                $currentPages[$key] = $pageIndex + 1;
                $currentNodeIds[$key] = $this->stringValue($node['attributes']['id'] ?? null);
                $currentFieldNames[] = $this->stringValue($properties['bfName'] ?? null);
            }
        }
        $existingVisibility = McpVisibilityRules::read(
            $tree['properties']['toggleFields'] ?? '',
            $currentFieldNames
        );
        if ($visibilityRulesProvided && !$existingVisibility['supported']) {
            throw new ToolException('This form has visibility rules that MCP cannot safely preserve. Edit them in the QuickMode editor.');
        }

        $add = $this->normalizeFields($arguments['add'] ?? []);
        $targetPageCount = count($tree['children']) + $pagesToAdd;
        $pageIntroByNumber = [];
        foreach ($pageUpdates as $pageUpdate) {
            if (
                !is_array($pageUpdate) || !is_int($pageUpdate['page'] ?? null)
                || $pageUpdate['page'] < 1 || $pageUpdate['page'] > $targetPageCount
                || !array_key_exists('intro', $pageUpdate)
            ) {
                throw new ToolException('Each page update must identify an existing or newly added page and provide its intro text.');
            }
            $pageNumber = $pageUpdate['page'];
            if (isset($pageIntroByNumber[$pageNumber])) {
                throw new ToolException('A page can only be updated once per call.');
            }
            $pageIntroByNumber[$pageNumber] = $this->normalizePageIntro($pageUpdate['intro']);
        }
        foreach ($add as $field) {
            if ($field['page'] > $targetPageCount) {
                throw new ToolException(sprintf('Page %d does not exist on this form.', $field['page']));
            }
        }
        $updates = $arguments['update'] ?? [];
        $removals = $arguments['remove'] ?? [];
        if (
            !is_array($updates) || !array_is_list($updates) || count($updates) > 30
            || !is_array($removals) || !array_is_list($removals) || count($removals) > 30
        ) {
            throw new ToolException('Field updates and removals must be lists of at most 30 items.');
        }
        if (
            $add === [] && $updates === [] && $removals === [] && $pagesToAdd === 0
            && $pagesToRemove === [] && $pageIntroByNumber === []
            && !$visibilityRulesProvided
        ) {
            throw new ToolException('Give at least one field or page change to make.');
        }

        $updateByName = [];
        $renameTargets = [];
        $addedNameKeys = array_fill_keys(array_map(static fn (array $field): string => strtolower($field['name']), $add), true);
        foreach ($updates as $change) {
            if (!is_array($change)) {
                throw new ToolException('Each field update must be an object.');
            }
            $name = trim($this->stringValue($change['name'] ?? ''));
            $key = strtolower($name);
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,99}$/D', $name) || !isset($current[$key])) {
                throw new ToolException('Every updated field must identify an existing field by name.');
            }
            if (isset($updateByName[$key])) {
                throw new ToolException('A field can be updated only once per call.');
            }
            unset($change['name']);
            if (array_key_exists('new_name', $change)) {
                if (!is_string($change['new_name'])) {
                    throw new ToolException('A new field name must be text.');
                }
                $newName = trim($change['new_name']);
                if (
                    !preg_match('/^[A-Za-z][A-Za-z0-9_]{0,99}$/D', $newName)
                    || preg_match('/^bfFakeName(?:[2-4])?$/iD', $newName)
                ) {
                    throw new ToolException('New field names must be valid and cannot use reserved submit-control names.');
                }
                $newKey = strtolower($newName);
                if (($newKey !== $key && isset($current[$newKey])) || isset($addedNameKeys[$newKey])) {
                    throw new ToolException('A field with that new name already exists.');
                }
                if ($newName !== $this->stringValue($current[$key]['bfName'] ?? null)) {
                    if (isset($renameTargets[$newKey]) && $renameTargets[$newKey] !== $key) {
                        throw new ToolException('Each field must have a different new name.');
                    }
                    if (!preg_match('/^(bfQuickModeElement[1-9][0-9]*)_/D', $currentNodeIds[$key])) {
                        throw new ToolException('This field has an unsupported QuickMode identity and cannot be renamed.');
                    }
                    $renameTargets[$newKey] = $key;
                    $change['new_name'] = $newName;
                } else {
                    unset($change['new_name']);
                }
            }
            if ($change === []) {
                throw new ToolException('Give a new name, type, label, placeholder or field setting for each field update.');
            }
            if (isset($change['type'])) {
                if (
                    !in_array($change['type'], ['text', 'textarea'], true)
                    || !in_array($current[$key]['bfType'] ?? '', ['bfTextfield', 'bfTextarea'], true)
                ) {
                    throw new ToolException('Existing fields can only change between text and textarea types.');
                }
            }
            if (array_key_exists('widget', $change)) {
                if (($current[$key]['bfType'] ?? '') !== 'bfTextfield') {
                    throw new ToolException('Widgets can only be used on text fields.');
                }
                $change['widget'] = $this->input->normalizeEditableWidget($change['widget']);
                // A consent box, an anti-robot or a rating would be lost with its settings.
                $storedWidget = \Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetOptions::widget($current[$key]);
                if (!$this->input->mayReplaceWidget($storedWidget)) {
                    throw new ToolException('The consent, anti-robot and rating widgets cannot be changed or removed through MCP; edit the field in the QuickMode editor.');
                }
            }
            $targetWidget = array_key_exists('widget', $change)
                ? $change['widget']
                : \Vcmb\Component\BreezingformsNG\Site\Service\Widget\WidgetOptions::widget($current[$key]);
            if (
                $targetWidget !== ''
                && array_intersect_key($change, ['type' => 1, 'size' => 1, 'max_length' => 1]) !== []
            ) {
                throw new ToolException('A field that uses a widget (phone, date and time, country or address) cannot change type, size or maximum length through MCP; edit it in the QuickMode editor.');
            }
            if (
                array_key_exists('page', $change)
                && (!is_int($change['page']) || $change['page'] < 1 || $change['page'] > $targetPageCount)
            ) {
                throw new ToolException('A field can only be assigned to a page that already exists on the form.');
            }
            if (array_key_exists('options', $change)) {
                if (!in_array($current[$key]['bfType'] ?? '', ['bfSelect', 'bfRadioGroup', 'bfCheckboxGroup'], true)) {
                    throw new ToolException('Choice options can only be changed on select, radio or checkbox-group fields.');
                }
                $choiceType = $current[$key]['bfType'];
                $allowMultipleSelections = $choiceType === 'bfCheckboxGroup'
                    || ($choiceType === 'bfSelect' && (bool) ($change['multiple'] ?? $current[$key]['multiple']));
                $change['options'] = $this->normalizeChoices($change['options'], $allowMultipleSelections);
                if ($change['options'] === []) {
                    throw new ToolException('Choice fields must keep at least one option.');
                }
            }
            if (array_key_exists('multiple', $change)) {
                if (($current[$key]['bfType'] ?? '') !== 'bfSelect' || !is_bool($change['multiple'])) {
                    throw new ToolException('Only select fields support the multiple setting.');
                }
                if (
                    !$change['multiple'] && !array_key_exists('options', $change)
                    && (bool) ($current[$key]['multiple'] ?? false)
                ) {
                    throw new ToolException('When disabling multiple selection, include the options so the single default can be validated.');
                }
            }
            if (array_key_exists('default_value', $change)) {
                if (
                    !in_array($current[$key]['bfType'] ?? '', ['bfTextfield', 'bfTextarea'], true)
                    || !is_string($change['default_value']) || mb_strlen($change['default_value']) > 4000
                ) {
                    throw new ToolException('A default value can only be set on text or textarea fields and is limited to 4000 characters.');
                }
                $this->assertPlainText($change['default_value']);
            }
            if (array_key_exists('max_length', $change)) {
                if (
                    !in_array($current[$key]['bfType'] ?? '', ['bfTextfield', 'bfTextarea'], true)
                    || !is_int($change['max_length']) || $change['max_length'] < 0 || $change['max_length'] > 10000
                ) {
                    throw new ToolException('Maximum length must be from 0 to 10000 and can only be set on text or textarea fields.');
                }
            }
            if (
                array_key_exists('readonly', $change)
                && (!in_array($current[$key]['bfType'] ?? '', ['bfTextfield', 'bfTextarea'], true)
                    || !is_bool($change['readonly']))
            ) {
                throw new ToolException('Only text and textarea fields support the read-only setting.');
            }
            $currentFieldType = $current[$key]['bfType'] ?? '';
            $targetFieldType = $change['type'] ?? match ($currentFieldType) {
                'bfTextfield' => 'text',
                'bfTextarea' => 'textarea',
                default => '',
            };
            foreach (['size' => 'text', 'width' => 'textarea', 'height' => 'textarea'] as $property => $supportedType) {
                if (!array_key_exists($property, $change)) {
                    continue;
                }
                if ($targetFieldType !== $supportedType) {
                    throw new ToolException(sprintf('The %s setting is only supported on %s fields.', $property, $supportedType));
                }
                $change[$property] = $this->normalizeCssDimension($change[$property], $property);
            }
            if (array_key_exists('checked', $change) || array_key_exists('value', $change)) {
                if (($current[$key]['bfType'] ?? '') !== 'bfCheckbox') {
                    throw new ToolException('Only checkbox fields support checked and value settings.');
                }
                if (array_key_exists('checked', $change) && !is_bool($change['checked'])) {
                    throw new ToolException('A checkbox checked setting must be boolean.');
                }
                if (array_key_exists('value', $change)) {
                    $change['value'] = $this->normalizeCheckboxValue($change['value']);
                }
            }
            if (array_key_exists('required', $change) || array_key_exists('required_message', $change)) {
                $change['requiredConfig'] = $this->normalizeRequiredChange($change, $current[$key]);
                unset($change['required'], $change['required_message']);
            }
            if (
                isset($change['type'])
                && $change['type'] !== ($current[$key]['bfType'] === 'bfTextfield' ? 'text' : 'textarea')
                    && ($this->intValue($current[$key]['validationId'] ?? 0) !== 0
                    || $this->intValue($current[$key]['initId'] ?? 0) !== 0
                    || $this->intValue($current[$key]['actionId'] ?? 0) !== 0
                    || $this->stringValue($current[$key]['validationCode'] ?? '') !== ''
                    || $this->stringValue($current[$key]['initCode'] ?? '') !== ''
                    || $this->stringValue($current[$key]['actionCode'] ?? '') !== ''
                    || !empty($current[$key]['password']) || !empty($current[$key]['mailback'])
                    || !empty($current[$key]['mailbackAsSender']) || !empty($current[$key]['mailbackfile']))
            ) {
                throw new ToolException('A field with scripts, validation, password or mailback settings cannot change type through MCP.');
            }
            foreach (['label', 'placeholder'] as $property) {
                if (array_key_exists($property, $change)) {
                    $change[$property] = trim($this->stringValue($change[$property]));
                    if (($property === 'label' && $change[$property] === '') || mb_strlen($change[$property]) > 255) {
                        throw new ToolException('Field labels are required and field text is limited to 255 characters.');
                    }
                    $this->assertPlainText($change[$property]);
                }
            }
            if (array_key_exists('hint', $change)) {
                if (!is_string($change['hint']) || mb_strlen($change['hint']) > 1000) {
                    throw new ToolException('Field hints must be plain text limited to 1000 characters.');
                }
                $change['hint'] = trim($change['hint']);
                $this->assertPlainText($change['hint']);
            }
            $updateByName[$key] = $change;
        }

        if ($renameTargets !== []) {
            $this->assertFieldsCanBeRenamed($form, $tree);
            if ($this->submissionCount($id) > 0) {
                throw new ToolException('Fields cannot be renamed while the form has submissions.');
            }
        }

        $removeByName = [];
        foreach ($removals as $name) {
            if (!is_string($name)) {
                throw new ToolException('Field removals must identify fields by name.');
            }
            $name = trim($name);
            $key = strtolower($name);
            if (!isset($current[$key]) || isset($removeByName[$key])) {
                throw new ToolException('Every removed field must identify a different existing field by name.');
            }
            if (isset($updateByName[$key])) {
                throw new ToolException('A field cannot be updated and removed in the same call.');
            }
            $removeByName[$key] = $name;
        }

        foreach ($add as $field) {
            $key = strtolower($field['name']);
            if (isset($current[$key])) {
                throw new ToolException('A field with that name already exists.');
            }
        }

        if ($removeByName !== []) {
            $recordCount = $this->submissionCount($id);
            if ($recordCount > 0) {
                throw new ToolException('Fields cannot be removed while the form has submissions; existing records are preserved.');
            }
        }
        if (
            array_filter($updateByName, static fn (array $change): bool => array_key_exists('options', $change)
            || array_key_exists('multiple', $change) || array_key_exists('value', $change)) !== []
            && $this->submissionCount($id) > 0
        ) {
            throw new ToolException('Choice settings and checkbox values cannot be changed while the form has submissions; historical values are preserved.');
        }

        $removePageSet = [];
        foreach ($pagesToRemove as $pageNumber) {
            if (
                !is_int($pageNumber) || $pageNumber < 1 || $pageNumber > count($tree['children'])
                || isset($removePageSet[$pageNumber])
            ) {
                throw new ToolException('Every removed page must identify a different existing page number.');
            }
            $removePageSet[$pageNumber] = true;
        }
        if (count($removePageSet) >= count($tree['children'])) {
            throw new ToolException('A QuickMode form must keep at least one page.');
        }
        if (array_intersect_key($pageIntroByNumber, $removePageSet) !== []) {
            throw new ToolException('A page cannot be updated and removed in the same call.');
        }
        if ($removePageSet !== [] && $this->submissionCount($id) > 0) {
            throw new ToolException('Pages cannot be removed while the form has submissions; historical page structure is preserved.');
        }

        /** @var list<QuickModePage> $resultPages */
        $resultPages = [];
        /** @var array<int, list<QuickModeElement>> $movedFields */
        $movedFields = [];
        /** @var list<string> $allFields */
        $allFields = [];
        /** @var list<string> $updated */
        $updated = [];
        /** @var list<string> $removed */
        $removed = [];
        foreach ($tree['children'] as $pageIndex => $page) {
            $result = [];
            foreach ($page['children'] as $node) {
                $key = strtolower($this->stringValue($node['properties']['bfName'] ?? null));
                if (isset($removeByName[$key])) {
                    $removed[] = $this->stringValue($node['properties']['bfName'] ?? null);
                    continue;
                }
                if (isset($updateByName[$key])) {
                    $change = $updateByName[$key];
                    if (isset($change['type'])) {
                        $node['properties']['bfType'] = $change['type'] === 'text' ? 'bfTextfield' : 'bfTextarea';
                    }
                    foreach (['label', 'placeholder'] as $property) {
                        if (array_key_exists($property, $change)) {
                            $node['properties'][$property] = $change[$property];
                        }
                    }
                    if (array_key_exists('hint', $change)) {
                        $node['properties']['hint'] = $change['hint'];
                    }
                    if (array_key_exists('widget', $change)) {
                        $node['properties']['widget'] = $change['widget'];
                    }
                    foreach (['size', 'width', 'height'] as $property) {
                        if (array_key_exists($property, $change)) {
                            $node['properties'][$property] = $change[$property];
                        }
                    }
                    if (array_key_exists('readonly', $change)) {
                        $node['properties']['readonly'] = $change['readonly'];
                    }
                    if (array_key_exists('default_value', $change)) {
                        $node['properties']['value'] = $change['default_value'];
                    }
                    if (array_key_exists('max_length', $change) || isset($change['type'])) {
                        $oldType = $current[$key]['bfType'];
                        $newType = $change['type'] ?? ($oldType === 'bfTextfield' ? 'text' : 'textarea');
                        $length = $change['max_length'] ?? $this->intValue($node['properties'][$oldType === 'bfTextfield' ? 'maxLength' : 'maxlength'] ?? 0);
                        if ($newType === 'text') {
                            $node['properties']['maxLength'] = $length > 0 ? $length : '';
                            unset($node['properties']['maxlength']);
                        } else {
                            $node['properties']['maxlength'] = $length;
                            $node['properties']['showMaxlengthCounter'] = (bool) ($node['properties']['showMaxlengthCounter'] ?? true);
                            unset($node['properties']['maxLength']);
                        }
                    }
                    if (array_key_exists('options', $change)) {
                        $choiceProperty = $node['properties']['bfType'] === 'bfSelect' ? 'list' : 'group';
                        $node['properties'][$choiceProperty] = $this->serializeChoices($change['options']);
                    }
                    foreach (['multiple', 'checked', 'value'] as $property) {
                        if (array_key_exists($property, $change)) {
                            $node['properties'][$property] = $change[$property];
                        }
                    }
                    if (isset($change['new_name'])) {
                        $node['properties']['bfName'] = $change['new_name'];
                        if (preg_match('/^(bfQuickModeElement[1-9][0-9]*)_/', $this->stringValue($node['attributes']['id'] ?? null), $matches) === 1) {
                            $node['attributes']['id'] = $matches[1] . '_' . strtolower($change['new_name']);
                        }
                    }
                    if (isset($change['requiredConfig'])) {
                        $this->applyRequiredConfiguration($node['properties'], $change['requiredConfig']);
                    }
                    $targetPage = $change['page'] ?? $currentPages[$key];
                    if ($targetPage !== $pageIndex + 1) {
                        $movedFields[$targetPage - 1][] = $node;
                        $updated[] = $this->stringValue($node['properties']['bfName'] ?? null);
                        continue;
                    }
                    $updated[] = $this->stringValue($node['properties']['bfName'] ?? null);
                }
                $result[] = $node;
            }
            $page['children'] = $result;
            $resultPages[] = $page;
        }

        $highestPageId = 0;
        foreach ($resultPages as $page) {
            preg_match('/^bfQuickModePage([1-9][0-9]*)$/D', $this->stringValue($page['attributes']['id'] ?? null), $matches);
            $highestPageId = max($highestPageId, (int) ($matches[1] ?? 0));
        }
        for ($pageIndex = 0; $pageIndex < $pagesToAdd; $pageIndex++) {
            $resultPages[] = [
                'attributes' => ['id' => 'bfQuickModePage' . ($highestPageId + $pageIndex + 1)],
                'properties' => [
                    'type' => 'page',
                    'pageNumber' => $highestPageId + $pageIndex + 1,
                    'pageIntro' => '',
                ],
                'children' => [],
            ];
        }

        $pagesUpdated = [];
        foreach ($pageIntroByNumber as $introPage => $intro) {
            $pageNumber = (int) $introPage;
            $resultPages[$pageNumber - 1]['properties']['pageIntro'] = $intro;
            $resultPages[$pageNumber - 1]['properties']['pageNumber'] = $pageNumber;
            $pagesUpdated[] = $pageNumber;
        }

        foreach ($resultPages as $pageKey => &$page) {
            $pageIndex = (int) $pageKey;
            foreach ($movedFields[$pageIndex] ?? [] as $node) {
                $page['children'][] = $node;
            }
            foreach ($add as $field) {
                if ($field['page'] === $pageIndex + 1) {
                    $page['children'][] = $this->elementNode($field, count($page['children'] ?? []) + 1);
                }
            }
            if (!isset($page['children']) || !is_array($page['children'])) {
                throw new ToolException('This form has a malformed page that cannot be safely saved.');
            }
            foreach ($page['children'] as $index => &$node) {
                if (!is_array($node) || !is_array($node['properties'] ?? null)) {
                    throw new ToolException('This form has a malformed field that cannot be safely saved.');
                }
                $node['properties']['orderNumber'] = $index + 1;
                $allFields[] = $this->stringValue($node['properties']['bfName'] ?? null);
            }
            unset($node);
        }
        unset($page);

        $pagesRemoved = [];
        foreach ($resultPages as $pageKey => $page) {
            $pageIndex = (int) $pageKey;
            $pageNumber = $pageIndex + 1;
            if (!isset($removePageSet[$pageNumber])) {
                continue;
            }
            if (($page['children'] ?? []) !== []) {
                throw new ToolException(sprintf('Page %d still has fields; move or remove them before deleting the page.', $pageNumber));
            }
            $pagesRemoved[] = $pageNumber;
            unset($resultPages[$pageIndex]);
        }
        if ($pagesRemoved !== []) {
            $resultPages = array_values($resultPages);
            foreach ($resultPages as $pageKey => &$page) {
                $pageIndex = (int) $pageKey;
                $page['attributes']['id'] = 'bfQuickModePage' . ($pageIndex + 1);
                $page['properties']['pageNumber'] = $pageIndex + 1;
            }
            unset($page);
        }
        if ($visibilityRulesProvided) {
            $tree['properties']['toggleFields'] = McpVisibilityRules::serialize($visibilityRules, $allFields);
        } elseif ($renameTargets !== [] || $removeByName !== []) {
            // A rename or deletion must not silently leave references to missing fields.
            // Unknown legacy syntax cannot be inspected safely, so preserve it by refusing the change.
            if (!$existingVisibility['supported']) {
                throw new ToolException('This form has visibility rules that MCP cannot safely preserve. Edit them in the QuickMode editor.');
            }
            try {
                $rewritten = McpVisibilityRules::serialize($existingVisibility['rules'], $allFields);
            } catch (ToolException) {
                throw new ToolException('Update visibility rules when renaming or removing a field referenced by them.');
            }
            // The form script matches names with their case: a rename of the case only must follow.
            if ($existingVisibility['rules'] !== []) {
                $tree['properties']['toggleFields'] = $rewritten;
            }
        }
        $tree['properties']['name'] = (string) $form->name;
        $tree['properties']['title'] = (string) $form->title;
        $tree['properties']['description'] = (string) $form->description;
        $tree['properties']['mailNotification'] = (int) ($form->emailntf ?? 0) > 0;
        $tree['properties']['mailRecipient'] = (string) ($form->emailadr ?? '');
        $tree['children'] = $resultPages;

        if ((int) ($form->checked_out ?? 0) > 0 || !$this->forms->checkout($id)) {
            throw new ToolException('The form is checked out and cannot be edited now.');
        }

        $transactionStarted = false;
        $warnings = [];

        try {
            $lockedForm = $this->options->getForm($id);
            $lockedTree = json_decode($this->quickmode->getTemplateCode($id), true);
            if (
                $lockedForm === null || (int) ($lockedForm->version ?? -1) !== (int) ($form->version ?? -2)
                || (string) ($lockedForm->modified ?? '') !== (string) ($form->modified ?? '')
                || $lockedTree !== $originalTree
            ) {
                throw new ToolException('The form changed while this request was being prepared; read it again and retry.');
            }

            $historySensitiveChange = $removeByName !== [] || $removePageSet !== [] || $renameTargets !== []
                || array_filter($updateByName, static fn (array $change): bool => array_key_exists('options', $change)
                    || array_key_exists('multiple', $change) || array_key_exists('value', $change)) !== [];
            if ($historySensitiveChange && $this->submissionCount($id, true) > 0) {
                throw new ToolException('This change is no longer safe because the form now has submissions.');
            }

            $this->db->transactionStart();
            $transactionStarted = true;
            $versioning = new FormVersioning($this->db, $this->forms);
            $versioning->keepOrigin($id);
            $this->quickmode->save($id, $tree, (int) $form->published, false);
            $warnings = $this->codeWarnings();
            try {
                $versioning->record($id);
                $versioned = true;
            } catch (\Throwable) {
                $versioned = false;
            }
            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            if ($transactionStarted) {
                $this->db->transactionRollback();
            }
            throw $e;
        } finally {
            // Whatever failed after the checkout, the form is released.
            $this->forms->checkin($id);
        }

        $contentBuilderSynced = (new ContentBuilderFormSync(\Joomla\CMS\Factory::getApplication(), $this->db))->sync($id);

        return [
            'id' => $id,
            'added' => array_column($add, 'name'),
            'updated' => $updated,
            'removed' => $removed,
            'pages_added' => $pagesToAdd,
            'pages_updated' => $pagesUpdated,
            'pages_removed' => $pagesRemoved,
            'visibility_rules_updated' => $visibilityRulesProvided,
            'visibility_rule_count' => $visibilityRulesProvided ? count($visibilityRules) : 0,
            'field_count' => count($allFields),
            'versioned' => $versioned,
            'contentbuilder_synced' => $contentBuilderSynced,
        ] + $warnings;
    }

    /**
     * What the last QuickMode save left out for lack of the "code" permission: validation scripts, code of
     * the elements, payment download files. The change is made without them, which the caller is told.
     *
     * @return array{warnings?: list<string>}
     */
    private function codeWarnings(): array
    {
        if ($this->quickmode->getIgnoredCodeColumns() === []) {
            return [];
        }

        return ['warnings' => ['Scripts, validation scripts and download file paths need the code permission: they were left as they were stored (none for a new field).']];
    }

    /** @param array<string, mixed> $arguments
     *  @return array{id: int, state: string}
     *  @throws ToolException
     */
    public function trashForm(array $arguments, User $user): array
    {
        if (
            !$user->authorise('core.delete', 'com_breezingformsng')
            || !$user->authorise('core.edit.state', 'com_breezingformsng')
        ) {
            throw new ToolException('You are not allowed to move forms to the trash.');
        }

        $id = $this->intValue($arguments['id'] ?? null);
        $form = $this->options->getForm($id);
        if ($form === null || (int) $form->published === -2) {
            throw new ToolException('Form not found or already in the trash.');
        }
        if ((int) ($form->checked_out ?? 0) > 0) {
            throw new ToolException('The form is checked out and cannot be moved to the trash now.');
        }

        $ids = [$id];
        if (!$this->forms->publish($ids, -2)) {
            throw new ToolException('The form could not be moved to the trash.');
        }

        return ['id' => $id, 'state' => 'trashed'];
    }

    /** @param array<string, mixed> $arguments
     *  @return array{id: int, state: string}
     *  @throws ToolException
     */
    public function restoreForm(array $arguments, User $user): array
    {
        if (!$user->authorise('core.edit.state', 'com_breezingformsng')) {
            throw new ToolException('You are not allowed to restore forms.');
        }

        $id = $this->intValue($arguments['id'] ?? null);
        $form = $this->options->getForm($id);
        if ($form === null || (int) $form->published !== -2) {
            throw new ToolException('No trashed form has this id.');
        }
        if ((int) ($form->checked_out ?? 0) > 0) {
            throw new ToolException('The form is checked out and cannot be restored now.');
        }

        $ids = [$id];
        if (!$this->forms->publish($ids, 0)) {
            throw new ToolException('The form could not be restored.');
        }

        return ['id' => $id, 'state' => 'unpublished'];
    }

    private function recordVersion(int $id): bool
    {
        try {
            (new FormVersioning($this->db, $this->forms))->record($id);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return list<array{type: string, name: string, label: string, placeholder: string, hint: string, size: string, width: string, height: string, default_value: string, max_length: int, readonly: bool, widget: string, options: list<array{label: string, value: string, selected: bool}>, multiple: bool, checked: bool, value: string, required: bool, required_message: string, page: int}> */
    private function normalizeFields(mixed $fields): array
    {
        return $this->input->normalizeFields($fields);
    }

    /** @param list<array{type: string, name: string, label: string, placeholder: string, hint: string, size: string, width: string, height: string, default_value: string, max_length: int, readonly: bool, widget: string, options: list<array{label: string, value: string, selected: bool}>, multiple: bool, checked: bool, value: string, required: bool, required_message: string, page: int}> $fields
     *  @return array<string, mixed>
     */
    private function quickmodeTree(string $name, string $title, string $description, array $fields, string $pageIntro): array
    {
        return $this->tree->create($name, $title, $description, $fields, $pageIntro);
    }

    /** @param array{type: string, name: string, label: string, placeholder: string, hint: string, size: string, width: string, height: string, default_value: string, max_length: int, readonly: bool, widget: string, options: list<array{label: string, value: string, selected: bool}>, multiple: bool, checked: bool, value: string, required: bool, required_message: string, page: int} $field
     *  @return array<string, mixed>
     */
    private function elementNode(array $field, int $order): array
    {
        return $this->tree->element($field, $order);
    }

    /**
     * @return QuickModeTree
     * @throws ToolException
     */
    private function managedTree(int $formId): array
    {
        return $this->tree->managed($formId);
    }

    private function assertPlainText(string $text): void
    {
        $this->input->assertPlainText($text);
    }

    private function normalizeCssDimension(mixed $value, string $property): string
    {
        return $this->input->normalizeCssDimension($value, $property);
    }

    private function normalizePageIntro(mixed $intro): string
    {
        return $this->input->normalizePageIntro($intro);
    }

    /** @return list<array{label: string, value: string, selected: bool}> */
    private function normalizeChoices(mixed $choices, bool $allowMultipleSelections = true): array
    {
        return $this->input->normalizeChoices($choices, $allowMultipleSelections);
    }

    /** @param list<array{label: string, value: string, selected: bool}> $choices */
    private function serializeChoices(array $choices): string
    {
        return $this->input->serializeChoices($choices);
    }

    private function normalizeCheckboxValue(mixed $value): string
    {
        return $this->input->normalizeCheckboxValue($value);
    }

    /**
     * The QuickMode editor rewrites the form row from the root of its tree on every
     * save: the tree must carry the identity and notification changes made here.
     *
     * @param array<string, mixed> $data
     */
    private function syncQuickModeRoot(int $id, array $data): void
    {
        $tree = json_decode($this->quickmode->getTemplateCode($id), true);
        if (!is_array($tree) || !isset($tree['properties']) || !is_array($tree['properties'])) {
            return;
        }

        $map = ['name' => 'name', 'title' => 'title', 'description' => 'description', 'emailadr' => 'mailRecipient'];
        foreach ($map as $column => $property) {
            if (array_key_exists($column, $data)) {
                $tree['properties'][$property] = $this->stringValue($data[$column]);
            }
        }
        if (array_key_exists('emailntf', $data)) {
            $tree['properties']['mailNotification'] = $this->intValue($data['emailntf']) > 0;
        }

        $encoded = base64_encode(json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $column = $this->db->quoteName('template_code');
        $this->db->setQuery(
            $this->db->createQuery()
                ->update($this->db->quoteName('#__facileforms_forms'))
                ->set($column . " = ''")
                ->where($this->db->quoteName('id') . ' = :form')
                ->bind(':form', $id, ParameterType::INTEGER)
        )->execute();

        // Written in chunks, as the QuickMode model does, to stay under the packet limit.
        foreach (str_split($encoded, 60000) as $chunk) {
            $this->db->setQuery(
                $this->db->createQuery()
                    ->update($this->db->quoteName('#__facileforms_forms'))
                    ->set($column . ' = CONCAT(' . $column . ', :chunk)')
                    ->where($this->db->quoteName('id') . ' = :form')
                    ->bind(':chunk', $chunk)
                    ->bind(':form', $id, ParameterType::INTEGER)
            )->execute();
        }
    }

    /** Submission counts already read in this request; the checks after the form lock ask for a fresh one. */
    /** @var array<int, int> */
    private array $submissionCounts = [];

    private function submissionCount(int $formId, bool $fresh = false): int
    {
        if (!$fresh && isset($this->submissionCounts[$formId])) {
            return $this->submissionCounts[$formId];
        }

        return $this->submissionCounts[$formId] = $this->intValue($this->db->setQuery(
            $this->db->createQuery()
                ->select('COUNT(*)')
                ->from($this->db->quoteName('#__facileforms_records'))
                ->where($this->db->quoteName('form') . ' = :form')
                ->bind(':form', $formId, ParameterType::INTEGER)
        )->loadResult());
    }

    /** @param array<string, mixed> $change
     *  @param array<string, mixed> $current
     *  @return array{required: bool, message: string}
     */
    private function normalizeRequiredChange(array $change, array $current): array
    {
        if (!in_array($current['bfType'] ?? '', ['bfTextfield', 'bfTextarea'], true)) {
            throw new ToolException('Only text and textarea fields can use the required setting.');
        }

        $validationFunction = $this->stringValue($current['validationFunctionName'] ?? '');
        $isRequired = $validationFunction === 'ff_valuenotempty'
            && $this->intValue($current['validationCondition'] ?? 0) === 1
            && $this->stringValue($current['validationCode'] ?? '') === '';
        $hasValidation = $this->intValue($current['validationId'] ?? 0) !== 0
            || $this->stringValue($current['validationCode'] ?? '') !== ''
            || $validationFunction !== ''
            || $this->intValue($current['validationCondition'] ?? 0) !== 0;

        if (array_key_exists('required', $change) && !is_bool($change['required'])) {
            throw new ToolException('A required setting must be boolean.');
        }
        $required = $change['required'] ?? $isRequired;
        if (array_key_exists('required_message', $change) && !is_string($change['required_message'])) {
            throw new ToolException('A required message must be plain text.');
        }
        $message = array_key_exists('required_message', $change)
            ? trim($change['required_message'])
            : ($isRequired ? $this->stringValue($current['validationMessage'] ?? '') : '');
        if (mb_strlen($message) > 255) {
            throw new ToolException('A required message must be plain text of at most 255 characters.');
        }
        $this->assertPlainText($message);

        if (array_key_exists('required_message', $change) && !$required) {
            throw new ToolException('A required message can only be set when the field is required.');
        }
        if ($required && $hasValidation && !$isRequired) {
            throw new ToolException('A field with another validation cannot be made required through MCP.');
        }
        if ($required) {
            $this->requiredValidationScriptId();
        }

        return ['required' => $required, 'message' => $message];
    }

    /** @param array<string, mixed> $properties
     *  @param array{required: bool, message: string} $configuration
     */
    private function applyRequiredConfiguration(array &$properties, array $configuration): void
    {
        if ($configuration['required']) {
            $properties['validationId'] = $this->requiredValidationScriptId();
            $properties['validationCode'] = '';
            $properties['validationFunctionName'] = 'ff_valuenotempty';
            $properties['validationCondition'] = 1;
            $properties['validationMessage'] = $configuration['message'];

            return;
        }

        if (
            ($properties['validationFunctionName'] ?? '') === 'ff_valuenotempty'
            && $this->intValue($properties['validationCondition'] ?? 0) === 1
            && $this->stringValue($properties['validationCode'] ?? '') === ''
        ) {
            $properties['validationId'] = 0;
            $properties['validationCode'] = '';
            $properties['validationFunctionName'] = '';
            $properties['validationCondition'] = 0;
            $properties['validationMessage'] = '';
        }
    }

    private function requiredValidationScriptId(): int
    {
        return $this->input->requiredValidationScriptId();
    }

    /** @param QuickModeTree $tree
     */
    private function assertFieldsCanBeRenamed(\stdClass $form, array $tree): void
    {
        foreach (['script1id', 'script2id', 'piece1id', 'piece2id', 'piece3id', 'piece4id'] as $column) {
            if (!empty($form->$column)) {
                throw new ToolException('Fields cannot be renamed while the form has a script or piece that may reference field names.');
            }
        }
        foreach (['script1code', 'script2code', 'piece1code', 'piece2code', 'piece3code', 'piece4code'] as $column) {
            if (trim((string) ($form->$column ?? '')) !== '') {
                throw new ToolException('Fields cannot be renamed while the form has custom script code.');
            }
        }
        foreach (
            [
            'email_custom_template', 'mb_email_custom_template', 'mailchimp_email_field',
            'mailchimp_checkbox_field', 'mailchimp_text_html_mobile_field',
            'mailchimp_unsubscribe_field', 'salesforce_fields',
            ] as $column
        ) {
            if (trim((string) ($form->$column ?? '')) !== '') {
                throw new ToolException('Fields cannot be renamed while a form template or integration may reference field names.');
            }
        }

        $root = $tree['properties'];
        if (
            trim($this->stringValue($root['submittedScriptCode'] ?? '')) !== ''
            || $this->intValue($root['submittedScriptCondidtion'] ?? -1) !== -1
        ) {
            throw new ToolException('Fields cannot be renamed while a submitted script is configured.');
        }
        foreach ($tree['children'] as $page) {
            foreach ($page['children'] as $node) {
                $properties = $node['properties'];
                if (
                    $this->intValue($properties['validationId'] ?? 0) !== 0
                    || $this->intValue($properties['initId'] ?? 0) !== 0
                    || $this->intValue($properties['actionId'] ?? 0) !== 0
                    || trim($this->stringValue($properties['validationCode'] ?? '')) !== ''
                    || trim($this->stringValue($properties['initCode'] ?? '')) !== ''
                    || trim($this->stringValue($properties['actionCode'] ?? '')) !== ''
                    || !empty($properties['validationFunctionName'])
                    || !empty($properties['initFunctionName'])
                    || !empty($properties['actionFunctionName'])
                    || !empty($properties['password']) || !empty($properties['mailback'])
                    || !empty($properties['mailbackAsSender']) || !empty($properties['mailbackfile'])
                    || !empty($properties['mailbackAccept']) || !empty($properties['mailbackConnectWith'])
                ) {
                    throw new ToolException('Fields cannot be renamed while any field has scripts, validation, password or mailback settings.');
                }
            }
        }
    }
}
