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

/** Creates forms and applies bounded, permission-checked updates through MCP. */
final class McpFormWriter
{
    private ?int $requiredValidationId = null;

    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly FormOptionsModel $options,
        private readonly FormModel $forms,
        private readonly QuickmodeModel $quickmode
    ) {
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array{id: int, name: string, title: string, published: false, field_count: int, versioned: bool}
     * @throws ToolException
     */
    public function create(array $arguments, User $user): array
    {
        if (!$user->authorise('core.create', 'com_breezingformsng')) {
            throw new ToolException('You are not allowed to create forms.');
        }

        $name = trim((string) $arguments['name']);
        $title = trim((string) $arguments['title']);
        if ($name === '' || $title === '') {
            throw new ToolException('Form name and title must not be empty.');
        }

        $description = trim((string) ($arguments['description'] ?? ''));
        $fields = $this->normalizeFields($arguments['fields'] ?? []);
        $this->db->transactionStart();
        try {
            $id = $this->quickmode->save(0, $this->quickmodeTree($name, $title, $description, $fields), 0, false);
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
        ];
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

        $id = (int) $arguments['id'];
        $form = $this->options->getForm($id);
        if ($form === null || (int) $form->published === -2) {
            throw new ToolException('Form not found or cannot be edited.');
        }

        $data = ['id' => $id];
        foreach (['name', 'title', 'description'] as $field) {
            if (!array_key_exists($field, $arguments)) {
                continue;
            }

            $value = trim((string) $arguments[$field]);
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
            $recipient = trim((string) $arguments['notification_recipient']);
            if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                throw new ToolException('The notification recipient must be one valid email address.');
            }
            $data['emailadr'] = $recipient;
        }
        if (array_key_exists('email_notifications', $arguments) || array_key_exists('notification_recipient', $arguments)) {
            $notificationsEnabled = (bool) ($data['emailntf'] ?? $form->emailntf);
            $recipient = (string) ($data['emailadr'] ?? $form->emailadr);
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
            $width = (int) ($data['width'] ?? $form->width);
            $widthMode = (int) ($data['widthmode'] ?? $form->widthmode);
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

            try {
                $versioned = $versioning->record($id);
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
     * @return array{id: int, added: list<string>, updated: list<string>, removed: list<string>, pages_added: int, pages_removed: list<int>, field_count: int, versioned: bool, contentbuilder_synced: bool}
     * @throws ToolException
     */
    public function manageFields(array $arguments, User $user): array
    {
        if (!$user->authorise('core.edit', 'com_breezingformsng')) {
            throw new ToolException('You are not allowed to edit forms.');
        }

        $id = (int) $arguments['id'];
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
        $form = $this->options->getForm($id);
        if ($form === null || (int) $form->published === -2) {
            throw new ToolException('Form not found or cannot be edited.');
        }

        $tree = $this->managedTree($id);
        $originalTree = $tree;
        $current = [];
        $currentPages = [];
        $currentNodeIds = [];
        foreach ($tree['children'] as $pageIndex => $page) {
            foreach ($page['children'] as $node) {
                $properties = $node['properties'];
                $key = strtolower($properties['bfName']);
                if (isset($current[$key])) {
                    throw new ToolException('This form has duplicate field names and cannot be safely edited through MCP.');
                }
                $current[$key] = $properties;
                $currentPages[$key] = $pageIndex + 1;
                $currentNodeIds[$key] = (string) $node['attributes']['id'];
            }
        }

        $add = $this->normalizeFields($arguments['add'] ?? []);
        $targetPageCount = count($tree['children']) + $pagesToAdd;
        foreach ($add as $field) {
            if ($field['page'] > $targetPageCount) {
                throw new ToolException(sprintf('Page %d does not exist on this form.', $field['page']));
            }
        }
        $updates = $arguments['update'] ?? [];
        $removals = $arguments['remove'] ?? [];
        if (!is_array($updates) || !array_is_list($updates) || count($updates) > 30
            || !is_array($removals) || !array_is_list($removals) || count($removals) > 30) {
            throw new ToolException('Field updates and removals must be lists of at most 30 items.');
        }
        if ($add === [] && $updates === [] && $removals === [] && $pagesToAdd === 0 && $pagesToRemove === []) {
            throw new ToolException('Give at least one field to add, update or remove.');
        }

        $updateByName = [];
        $renameTargets = [];
        $addedNameKeys = array_fill_keys(array_map(static fn (array $field): string => strtolower($field['name']), $add), true);
        foreach ($updates as $change) {
            if (!is_array($change)) {
                throw new ToolException('Each field update must be an object.');
            }
            $name = trim((string) ($change['name'] ?? ''));
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
                if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,99}$/D', $newName)
                    || preg_match('/^bfFakeName(?:[2-4])?$/iD', $newName)) {
                    throw new ToolException('New field names must be valid and cannot use reserved submit-control names.');
                }
                $newKey = strtolower($newName);
                if (($newKey !== $key && isset($current[$newKey])) || isset($addedNameKeys[$newKey])) {
                    throw new ToolException('A field with that new name already exists.');
                }
                if ($newName !== (string) $current[$key]['bfName']) {
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
                if (!in_array($change['type'], ['text', 'textarea'], true)
                    || !in_array($current[$key]['bfType'] ?? '', ['bfTextfield', 'bfTextarea'], true)) {
                    throw new ToolException('Existing fields can only change between text and textarea types.');
                }
            }
            if (array_key_exists('page', $change)
                && (!is_int($change['page']) || $change['page'] < 1 || $change['page'] > $targetPageCount)) {
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
                if (!$change['multiple'] && !array_key_exists('options', $change)
                    && (bool) ($current[$key]['multiple'] ?? false)) {
                    throw new ToolException('When disabling multiple selection, include the options so the single default can be validated.');
                }
            }
            if (array_key_exists('default_value', $change)) {
                if (!in_array($current[$key]['bfType'] ?? '', ['bfTextfield', 'bfTextarea'], true)
                    || !is_string($change['default_value']) || mb_strlen($change['default_value']) > 4000) {
                    throw new ToolException('A default value can only be set on text or textarea fields and is limited to 4000 characters.');
                }
                $this->assertPlainText($change['default_value']);
            }
            if (array_key_exists('max_length', $change)) {
                if (!in_array($current[$key]['bfType'] ?? '', ['bfTextfield', 'bfTextarea'], true)
                    || !is_int($change['max_length']) || $change['max_length'] < 0 || $change['max_length'] > 10000) {
                    throw new ToolException('Maximum length must be from 0 to 10000 and can only be set on text or textarea fields.');
                }
            }
            if (array_key_exists('readonly', $change)
                && (!in_array($current[$key]['bfType'] ?? '', ['bfTextfield', 'bfTextarea'], true)
                    || !is_bool($change['readonly']))) {
                throw new ToolException('Only text and textarea fields support the read-only setting.');
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
            if (isset($change['type'])
                && $change['type'] !== ($current[$key]['bfType'] === 'bfTextfield' ? 'text' : 'textarea')
                && ((int) ($current[$key]['validationId'] ?? 0) !== 0
                    || (int) ($current[$key]['initId'] ?? 0) !== 0
                    || (int) ($current[$key]['actionId'] ?? 0) !== 0
                    || (string) ($current[$key]['validationCode'] ?? '') !== ''
                    || (string) ($current[$key]['initCode'] ?? '') !== ''
                    || (string) ($current[$key]['actionCode'] ?? '') !== ''
                    || !empty($current[$key]['password']) || !empty($current[$key]['mailback'])
                    || !empty($current[$key]['mailbackAsSender']) || !empty($current[$key]['mailbackfile']))) {
                throw new ToolException('A field with scripts, validation, password or mailback settings cannot change type through MCP.');
            }
            foreach (['label', 'placeholder'] as $property) {
                if (array_key_exists($property, $change)) {
                    $change[$property] = trim((string) $change[$property]);
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
            $current[$key] = true;
        }

        if ($removeByName !== []) {
            $recordCount = $this->submissionCount($id);
            if ($recordCount > 0) {
                throw new ToolException('Fields cannot be removed while the form has submissions; existing records are preserved.');
            }
        }
        if (array_filter($updateByName, static fn (array $change): bool => array_key_exists('options', $change)
            || array_key_exists('multiple', $change) || array_key_exists('value', $change)) !== []
            && $this->submissionCount($id) > 0) {
            throw new ToolException('Choice settings and checkbox values cannot be changed while the form has submissions; historical values are preserved.');
        }

        $removePageSet = [];
        foreach ($pagesToRemove as $pageNumber) {
            if (!is_int($pageNumber) || $pageNumber < 1 || $pageNumber > count($tree['children'])
                || isset($removePageSet[$pageNumber])) {
                throw new ToolException('Every removed page must identify a different existing page number.');
            }
            $removePageSet[$pageNumber] = true;
        }
        if (count($removePageSet) >= count($tree['children'])) {
            throw new ToolException('A QuickMode form must keep at least one page.');
        }
        if ($removePageSet !== [] && $this->submissionCount($id) > 0) {
            throw new ToolException('Pages cannot be removed while the form has submissions; historical page structure is preserved.');
        }

        $resultPages = [];
        $movedFields = [];
        $allFields = [];
        $updated = [];
        $removed = [];
        foreach ($tree['children'] as $pageIndex => $page) {
            $result = [];
            foreach ($page['children'] as $node) {
                $key = strtolower($node['properties']['bfName']);
                if (isset($removeByName[$key])) {
                    $removed[] = $node['properties']['bfName'];
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
                    if (array_key_exists('readonly', $change)) {
                        $node['properties']['readonly'] = $change['readonly'];
                    }
                    if (array_key_exists('default_value', $change)) {
                        $node['properties']['value'] = $change['default_value'];
                    }
                    if (array_key_exists('max_length', $change) || isset($change['type'])) {
                        $oldType = $current[$key]['bfType'];
                        $newType = $change['type'] ?? ($oldType === 'bfTextfield' ? 'text' : 'textarea');
                        $length = $change['max_length'] ?? (int) ($node['properties'][$oldType === 'bfTextfield' ? 'maxLength' : 'maxlength'] ?? 0);
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
                        preg_match('/^(bfQuickModeElement[1-9][0-9]*)_/', (string) $node['attributes']['id'], $matches);
                        $node['properties']['bfName'] = $change['new_name'];
                        $node['attributes']['id'] = $matches[1] . '_' . strtolower($change['new_name']);
                    }
                    if (isset($change['requiredConfig'])) {
                        $this->applyRequiredConfiguration($node['properties'], $change['requiredConfig']);
                    }
                    $targetPage = $change['page'] ?? $currentPages[$key];
                    if ($targetPage !== $pageIndex + 1) {
                        $movedFields[$targetPage - 1][] = $node;
                        $updated[] = $node['properties']['bfName'];
                        continue;
                    }
                    $updated[] = $node['properties']['bfName'];
                }
                $result[] = $node;
            }
            $page['children'] = $result;
            $resultPages[] = $page;
        }

        $highestPageId = 0;
        foreach ($resultPages as $page) {
            preg_match('/^bfQuickModePage([1-9][0-9]*)$/D', (string) $page['attributes']['id'], $matches);
            $highestPageId = max($highestPageId, (int) ($matches[1] ?? 0));
        }
        for ($pageIndex = 0; $pageIndex < $pagesToAdd; $pageIndex++) {
            $resultPages[] = [
                'attributes' => ['id' => 'bfQuickModePage' . ($highestPageId + $pageIndex + 1)],
                'properties' => ['type' => 'page'],
                'children' => [],
            ];
        }

        foreach ($resultPages as $pageIndex => &$page) {
            foreach ($movedFields[$pageIndex] ?? [] as $node) {
                $page['children'][] = $node;
            }
            foreach ($add as $field) {
                if ($field['page'] === $pageIndex + 1) {
                    $page['children'][] = $this->elementNode($field, count($page['children']) + 1);
                }
            }
            foreach ($page['children'] as $index => &$node) {
                $node['properties']['orderNumber'] = $index + 1;
                $allFields[] = $node['properties']['bfName'];
            }
            unset($node);
        }
        unset($page);

        $pagesRemoved = [];
        foreach ($resultPages as $pageIndex => $page) {
            $pageNumber = $pageIndex + 1;
            if (!isset($removePageSet[$pageNumber])) {
                continue;
            }
            if ($page['children'] !== []) {
                throw new ToolException(sprintf('Page %d still has fields; move or remove them before deleting the page.', $pageNumber));
            }
            $pagesRemoved[] = $pageNumber;
            unset($resultPages[$pageIndex]);
        }
        if ($pagesRemoved !== []) {
            $resultPages = array_values($resultPages);
            foreach ($resultPages as $pageIndex => &$page) {
                $page['attributes']['id'] = 'bfQuickModePage' . ($pageIndex + 1);
            }
            unset($page);
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

        $lockedForm = $this->options->getForm($id);
        $lockedTree = json_decode($this->quickmode->getTemplateCode($id), true);
        if ($lockedForm === null || (int) ($lockedForm->version ?? -1) !== (int) ($form->version ?? -2)
            || (string) ($lockedForm->modified ?? '') !== (string) ($form->modified ?? '')
            || $lockedTree !== $originalTree) {
            $this->forms->checkin($id);
            throw new ToolException('The form changed while this request was being prepared; read it again and retry.');
        }

        $historySensitiveChange = $removeByName !== [] || $removePageSet !== [] || $renameTargets !== []
            || array_filter($updateByName, static fn (array $change): bool => array_key_exists('options', $change)
                || array_key_exists('multiple', $change) || array_key_exists('value', $change)) !== [];
        if ($historySensitiveChange && $this->submissionCount($id) > 0) {
            $this->forms->checkin($id);
            throw new ToolException('This change is no longer safe because the form now has submissions.');
        }

        try {
            $this->db->transactionStart();
            $versioning = new FormVersioning($this->db, $this->forms);
            $versioning->keepOrigin($id);
            $this->quickmode->save($id, $tree, (int) $form->published, false);
            try {
                $versioned = $versioning->record($id);
            } catch (\Throwable) {
                $versioned = false;
            }
            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            throw $e;
        } finally {
            $this->forms->checkin($id);
        }

        $contentBuilderSynced = (new ContentBuilderFormSync(\Joomla\CMS\Factory::getApplication(), $this->db))->sync($id);

        return [
            'id' => $id,
            'added' => array_column($add, 'name'),
            'updated' => $updated,
            'removed' => $removed,
            'pages_added' => $pagesToAdd,
            'pages_removed' => $pagesRemoved,
            'field_count' => count($allFields),
            'versioned' => $versioned,
            'contentbuilder_synced' => $contentBuilderSynced,
        ];
    }

    /** @param array<string, mixed> $arguments
     *  @throws ToolException
     */
    public function trashForm(array $arguments, User $user): array
    {
        if (!$user->authorise('core.delete', 'com_breezingformsng')
            || !$user->authorise('core.edit.state', 'com_breezingformsng')) {
            throw new ToolException('You are not allowed to move forms to the trash.');
        }

        $id = (int) $arguments['id'];
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
     *  @throws ToolException
     */
    public function restoreForm(array $arguments, User $user): array
    {
        if (!$user->authorise('core.edit.state', 'com_breezingformsng')) {
            throw new ToolException('You are not allowed to restore forms.');
        }

        $id = (int) $arguments['id'];
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

    /**
     * @return list<array{type: string, name: string, label: string, placeholder: string, default_value: string, max_length: int, readonly: bool, options: list<array{label: string, value: string, selected: bool}>, multiple: bool, checked: bool, value: string, required: bool, required_message: string, page: int}>
     * @throws ToolException
     */
    private function normalizeFields(mixed $fields): array
    {
        if (!is_array($fields) || !array_is_list($fields) || count($fields) > 30) {
            throw new ToolException('Fields must be a list of at most 30 simple fields.');
        }

        $normalized = [];
        $names = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                throw new ToolException('Each field must be an object.');
            }

            $type = (string) ($field['type'] ?? '');
            $name = trim((string) ($field['name'] ?? ''));
            $label = trim((string) ($field['label'] ?? ''));
            $placeholder = trim((string) ($field['placeholder'] ?? ''));
            $hint = $field['hint'] ?? '';
            $defaultValue = $field['default_value'] ?? '';
            $maxLength = $field['max_length'] ?? 0;
            $readOnly = $field['readonly'] ?? false;
            if (!in_array($type, ['text', 'textarea', 'select', 'radio', 'checkbox_group', 'checkbox'], true)) {
                throw new ToolException('Supported field types are text, textarea, select, radio, checkbox_group and checkbox.');
            }
            if (!is_string($defaultValue) || mb_strlen($defaultValue) > 4000
                || (array_key_exists('default_value', $field) && !in_array($type, ['text', 'textarea'], true))) {
                throw new ToolException('Default values are limited to 4000 characters and are only supported on text or textarea fields.');
            }
            $this->assertPlainText($defaultValue);
            if (!is_string($hint) || mb_strlen($hint) > 1000) {
                throw new ToolException('Field hints must be plain text limited to 1000 characters.');
            }
            $hint = trim($hint);
            $this->assertPlainText($hint);
            if (!is_int($maxLength) || $maxLength < 0 || $maxLength > 10000
                || (array_key_exists('max_length', $field) && !in_array($type, ['text', 'textarea'], true))) {
                throw new ToolException('Maximum length must be from 0 to 10000 and can only be set on text or textarea fields.');
            }
            if (!is_bool($readOnly) || (array_key_exists('readonly', $field) && !in_array($type, ['text', 'textarea'], true))) {
                throw new ToolException('Only text and textarea fields support the read-only setting.');
            }
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,99}$/D', $name)) {
                throw new ToolException('Field names must start with a letter and contain only letters, numbers and underscores.');
            }
            if (preg_match('/^bfFakeName(?:[2-4])?$/iD', $name)) {
                throw new ToolException('That field name is reserved for a QuickMode submit control.');
            }
            if ($label === '' || mb_strlen($label) > 255 || mb_strlen($placeholder) > 255) {
                throw new ToolException('Field labels are required and field text is limited to 255 characters.');
            }
            $this->assertPlainText($label);
            $this->assertPlainText($placeholder);
            $multiple = $field['multiple'] ?? false;
            if (!is_bool($multiple) || (array_key_exists('multiple', $field) && $type !== 'select')) {
                throw new ToolException('Only select fields support the multiple setting.');
            }
            $checked = $field['checked'] ?? false;
            if (!is_bool($checked) || (array_key_exists('checked', $field) && $type !== 'checkbox')) {
                throw new ToolException('Only checkbox fields support the checked setting.');
            }
            $checkboxValue = array_key_exists('value', $field) ? $this->normalizeCheckboxValue($field['value']) : '1';
            if (array_key_exists('value', $field) && $type !== 'checkbox') {
                throw new ToolException('Only checkbox fields support a submitted value setting.');
            }
            $required = $field['required'] ?? false;
            if (!is_bool($required) || ((array_key_exists('required', $field) || array_key_exists('required_message', $field))
                && !in_array($type, ['text', 'textarea'], true))) {
                throw new ToolException('Only text and textarea fields can use the required setting.');
            }
            $requiredMessage = $field['required_message'] ?? '';
            if (!is_string($requiredMessage) || mb_strlen($requiredMessage) > 255) {
                throw new ToolException('A required message must be plain text of at most 255 characters.');
            }
            $requiredMessage = trim($requiredMessage);
            if (array_key_exists('required_message', $field) && !$required) {
                throw new ToolException('A required message can only be set when the field is required.');
            }
            $this->assertPlainText($requiredMessage);
            if ($required) {
                $this->requiredValidationScriptId();
            }
            $allowMultipleSelections = $type === 'checkbox_group' || ($type === 'select' && $multiple);
            $choices = $this->normalizeChoices($field['options'] ?? [], $allowMultipleSelections);
            if (in_array($type, ['select', 'radio', 'checkbox_group'], true) && $choices === []) {
                throw new ToolException('Select, radio and checkbox_group fields need at least one choice.');
            }
            if (in_array($type, ['text', 'textarea', 'checkbox'], true) && $choices !== []) {
                throw new ToolException('Text, textarea and checkbox fields cannot have choice options.');
            }
            $page = $field['page'] ?? 1;
            if (!is_int($page) || $page < 1) {
                throw new ToolException('A field page must be a positive page number.');
            }

            $key = strtolower($name);
            if (isset($names[$key])) {
                throw new ToolException('Field names must be unique within the form.');
            }
            $names[$key] = true;
            $normalized[] = [
                'type' => $type,
                'name' => $name,
                'label' => $label,
                'placeholder' => $placeholder,
                'hint' => $hint,
                'default_value' => $defaultValue,
                'max_length' => $maxLength,
                'readonly' => $readOnly,
                'options' => $choices,
                'multiple' => $multiple,
                'checked' => $checked,
                'value' => $checkboxValue,
                'required' => $required,
                'required_message' => $requiredMessage,
                'page' => $page,
            ];
        }

        return $normalized;
    }

    /** @param list<array{type: string, name: string, label: string, placeholder: string, hint: string, default_value: string, max_length: int, readonly: bool, options: list<array{label: string, value: string, selected: bool}>, multiple: bool, checked: bool, value: string, required: bool, required_message: string, page: int}> $fields */
    private function quickmodeTree(string $name, string $title, string $description, array $fields): array
    {
        $children = [];
        foreach ($fields as $index => $field) {
            $children[] = $this->elementNode($field, $index + 1);
        }

        return [
            'attributes' => ['id' => 'bfQuickModeForm'],
            'properties' => [
                'type' => 'root',
                'name' => $name,
                'title' => $title,
                'description' => $description,
                'mailNotification' => false,
                'mailRecipient' => '',
                'submittedScriptCondidtion' => -1,
                'bfngManaged' => true,
            ],
            'children' => [[
                'attributes' => ['id' => 'bfQuickModePage1'],
                'properties' => ['type' => 'page'],
                'children' => $children,
            ]],
        ];
    }

    /** @param array{type: string, name: string, label: string, placeholder: string, hint: string, default_value: string, max_length: int, readonly: bool, options: list<array{label: string, value: string, selected: bool}>, multiple: bool, checked: bool, value: string, required: bool, required_message: string, page: int} $field */
    private function elementNode(array $field, int $order): array
    {
        $type = match ($field['type']) {
            'text' => 'bfTextfield',
            'textarea' => 'bfTextarea',
            'select' => 'bfSelect',
            'radio' => 'bfRadioGroup',
            'checkbox_group' => 'bfCheckboxGroup',
            'checkbox' => 'bfCheckbox',
        };
        $properties = [
            'type' => 'element',
            'bfType' => $type,
            'value' => $type === 'bfCheckbox' ? $field['value'] : $field['default_value'],
            'placeholder' => $field['placeholder'],
            'hint' => $field['hint'],
            'readonly' => $field['readonly'],
            'label' => $field['label'],
            'bfName' => $field['name'],
            'orderNumber' => $order,
            'tabIndex' => 0,
            'logging' => 1,
            'validationId' => 0,
            'validationCode' => '',
            'validationMessage' => '',
            'validationFunctionName' => '',
            'validationCondition' => 0,
            'initId' => 0,
            'initCode' => '',
            'initFunctionName' => '',
            'initCondition' => 0,
            'actionId' => 0,
            'actionCode' => '',
            'actionFunctionName' => '',
            'actionCondition' => 0,
            'dbId' => 0,
        ];
        if ($type === 'bfTextfield') {
            $properties['maxLength'] = $field['max_length'] > 0 ? $field['max_length'] : '';
        } elseif ($type === 'bfTextarea') {
            $properties['maxlength'] = $field['max_length'];
            $properties['showMaxlengthCounter'] = true;
        }
        if ($type === 'bfSelect') {
            $properties['multiple'] = $field['multiple'];
            $properties['list'] = $this->serializeChoices($field['options']);
            $properties['mailback'] = false;
        } elseif (in_array($type, ['bfRadioGroup', 'bfCheckboxGroup'], true)) {
            $properties['group'] = $this->serializeChoices($field['options']);
        } elseif ($type === 'bfCheckbox') {
            $properties['checked'] = $field['checked'];
            $properties['mailbackAccept'] = false;
            $properties['mailbackConnectWith'] = '';
        }
        if ($field['required']) {
            $properties['validationId'] = $this->requiredValidationScriptId();
            $properties['validationFunctionName'] = 'ff_valuenotempty';
            $properties['validationCondition'] = 1;
            $properties['validationMessage'] = $field['required_message'];
        }

        return [
            'attributes' => ['id' => 'bfQuickModeElement' . $order . '_' . strtolower($field['name'])],
            'properties' => $properties,
            'children' => [],
        ];
    }

    /** @throws ToolException */
    private function managedTree(int $formId): array
    {
        $form = $this->options->getForm($formId);
        $template = $this->quickmode->getTemplateCode($formId);
        if ($form === null || (string) ($form->package ?? '') !== 'QuickModeForms'
            || (string) ($form->template_code_processed ?? '') !== 'QuickMode' || strlen($template) > 500000) {
            throw new ToolException('Only QuickMode forms with a supported simple field structure can be edited here.');
        }

        $tree = json_decode($template, true);
        if (!is_array($tree) || !is_array($tree['attributes'] ?? null)
            || ($tree['attributes']['id'] ?? null) !== 'bfQuickModeForm'
            || !is_array($tree['properties'] ?? null) || !is_array($tree['children'] ?? null) || $tree['children'] === []
            || ($tree['properties']['type'] ?? null) !== 'root'
            || !is_array($tree['children'])) {
            throw new ToolException('This QuickMode form has an unsupported root structure.');
        }
        if (($tree['properties']['submittedScriptCondidtion'] ?? -1) != -1
            && !is_string($tree['properties']['submittedScriptCode'] ?? null)) {
            throw new ToolException('This form has an incomplete submitted script configuration.');
        }

        $elementIds = [];
        $quickmodeIds = [];
        $pageIds = [];
        foreach ($tree['children'] as $pageIndex => $page) {
            if (!is_array($page) || !is_array($page['attributes'] ?? null) || !is_array($page['properties'] ?? null)) {
                throw new ToolException('This form has a malformed page that the MCP field editor cannot safely preserve.');
            }
            $pageId = (string) ($page['attributes']['id'] ?? '');
            if ($pageId !== 'bfQuickModePage' . ($pageIndex + 1)
                || !preg_match('/^bfQuickModePage[1-9][0-9]*$/D', $pageId)
                || isset($pageIds[$pageId])
                || ($page['properties']['type'] ?? null) !== 'page' || !is_array($page['children'] ?? null)) {
                throw new ToolException('This form has a page structure that the MCP field editor cannot safely preserve.');
            }
            $pageIds[$pageId] = true;

            foreach ($page['children'] as $node) {
                if (!is_array($node)) {
                    throw new ToolException('This form contains a malformed field that cannot be safely preserved.');
                }
                $properties = $node['properties'] ?? null;
                if (!is_array($node['attributes'] ?? null) || !is_array($properties)) {
                    throw new ToolException('This form contains a malformed field that cannot be safely preserved.');
                }
                $nodeId = $node['attributes']['id'] ?? null;
                $requiredProperties = [
                    'type', 'bfType', 'value', 'label', 'bfName', 'orderNumber', 'tabIndex', 'logging',
                    'validationId', 'validationCode', 'validationMessage', 'validationFunctionName', 'validationCondition',
                    'initId', 'initCode', 'initFunctionName', 'initCondition', 'actionId', 'actionFunctionName', 'dbId',
                ];
                if (!is_string($nodeId) || isset($quickmodeIds[$nodeId])
                    || ($properties['type'] ?? null) !== 'element'
                    || !in_array($properties['bfType'] ?? null, ['bfTextfield', 'bfTextarea', 'bfSelect', 'bfRadioGroup', 'bfCheckboxGroup', 'bfCheckbox'], true)
                    || array_diff($requiredProperties, array_keys($properties)) !== []
                    || !is_array($node['children'] ?? null) || $node['children'] !== []) {
                    throw new ToolException('This form contains an unsupported field or nested structure the MCP field editor cannot safely preserve.');
                }
                $dbId = (int) ($properties['dbId'] ?? 0);
                $fieldName = trim((string) ($properties['bfName'] ?? ''));
                if ($dbId <= 0 || isset($elementIds[$dbId]) || $fieldName === '' || mb_strlen($fieldName) > 255
                    || preg_match('/[\x00-\x1F\x7F]/u', $fieldName)
                    || !is_string($properties['value']) || !is_string($properties['label'])
                    || mb_strlen((string) ($properties['label'] ?? '')) > 255
                    || mb_strlen((string) ($properties['placeholder'] ?? '')) > 255) {
                    throw new ToolException('This form has a field identity or text value that cannot be safely edited through MCP.');
                }
                $choiceProperty = match ($properties['bfType']) {
                    'bfSelect' => 'list',
                    'bfRadioGroup', 'bfCheckboxGroup' => 'group',
                    default => null,
                };
                if ($choiceProperty !== null && (!is_string($properties[$choiceProperty] ?? null)
                    || ($properties['bfType'] === 'bfSelect'
                        && (!is_bool($properties['multiple'] ?? null) || !is_bool($properties['mailback'] ?? null))))) {
                    throw new ToolException('This form has a malformed choice field that cannot be safely edited through MCP.');
                }
                if ($properties['bfType'] === 'bfCheckbox'
                    && (!is_bool($properties['checked'] ?? null) || !is_string($properties['mailbackConnectWith'] ?? null)
                        || !is_bool($properties['mailbackAccept'] ?? null))) {
                    throw new ToolException('This form has a malformed checkbox that cannot be safely edited through MCP.');
                }
                $elementIds[$dbId] = true;
                $quickmodeIds[$nodeId] = true;
            }
        }

        return $tree;
    }

    private function assertPlainText(string $text): void
    {
        if (str_contains($text, '<') || str_contains($text, '>')) {
            throw new ToolException('Field labels and placeholders must be plain text without markup.');
        }
    }

    /** @return list<array{label: string, value: string, selected: bool}> */
    private function normalizeChoices(mixed $choices, bool $allowMultipleSelections = true): array
    {
        if (!is_array($choices) || !array_is_list($choices) || count($choices) > 30) {
            throw new ToolException('Choice options must be a list of at most 30 items.');
        }

        $normalized = [];
        $values = [];
        $selectedCount = 0;
        foreach ($choices as $choice) {
            if (!is_array($choice)) {
                throw new ToolException('Each choice option must be an object.');
            }
            $label = trim((string) ($choice['label'] ?? ''));
            $value = trim((string) ($choice['value'] ?? ''));
            $selected = $choice['selected'] ?? false;
            if (!is_bool($selected)) {
                throw new ToolException('Choice selected flags must be boolean.');
            }
            $selectedCount += $selected ? 1 : 0;
            if (!$allowMultipleSelections && $selectedCount > 1) {
                throw new ToolException('Select fields and radio groups can have at most one initially selected choice.');
            }
            foreach ([$label, $value] as $text) {
                if ($text === '' || mb_strlen($text) > 255 || preg_match('/[;\r\n\x00]/u', $text)) {
                    throw new ToolException('Choice labels and values must be non-empty plain text of at most 255 characters without semicolons or line breaks.');
                }
                $this->assertPlainText($text);
            }
            if (isset($values[$value])) {
                throw new ToolException('Choice values must be unique within a field.');
            }
            $values[$value] = true;
            $normalized[] = ['label' => $label, 'value' => $value, 'selected' => $selected];
        }

        return $normalized;
    }

    /** @param list<array{label: string, value: string, selected: bool}> $choices */
    private function serializeChoices(array $choices): string
    {
        return implode('', array_map(static fn (array $choice): string => ($choice['selected'] ? '1;' : '0;') . $choice['label'] . ';' . $choice['value'] . "\n", $choices));
    }

    private function normalizeCheckboxValue(mixed $value): string
    {
        if (!is_string($value)) {
            throw new ToolException('A checkbox submitted value must be text.');
        }
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > 255 || preg_match('/[\x00-\x1F\x7F]/u', $value)) {
            throw new ToolException('A checkbox submitted value must be non-empty text of at most 255 characters without control characters.');
        }
        $this->assertPlainText($value);

        return $value;
    }

    private function submissionCount(int $formId): int
    {
        return (int) $this->db->setQuery(
            $this->db->createQuery()
                ->select('COUNT(*)')
                ->from($this->db->quoteName('#__facileforms_records'))
                ->where($this->db->quoteName('form') . ' = :form')
                ->bind(':form', $formId, ParameterType::INTEGER)
        )->loadResult();
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

        $validationFunction = (string) ($current['validationFunctionName'] ?? '');
        $isRequired = $validationFunction === 'ff_valuenotempty'
            && (int) ($current['validationCondition'] ?? 0) === 1
            && (string) ($current['validationCode'] ?? '') === '';
        $hasValidation = (int) ($current['validationId'] ?? 0) !== 0
            || (string) ($current['validationCode'] ?? '') !== ''
            || $validationFunction !== ''
            || (int) ($current['validationCondition'] ?? 0) !== 0;

        if (array_key_exists('required', $change) && !is_bool($change['required'])) {
            throw new ToolException('A required setting must be boolean.');
        }
        $required = $change['required'] ?? $isRequired;
        if (array_key_exists('required_message', $change) && !is_string($change['required_message'])) {
            throw new ToolException('A required message must be plain text.');
        }
        $message = array_key_exists('required_message', $change)
            ? trim($change['required_message'])
            : ($isRequired ? (string) ($current['validationMessage'] ?? '') : '');
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

        if (($properties['validationFunctionName'] ?? '') === 'ff_valuenotempty'
            && (int) ($properties['validationCondition'] ?? 0) === 1
            && (string) ($properties['validationCode'] ?? '') === '') {
            $properties['validationId'] = 0;
            $properties['validationCode'] = '';
            $properties['validationFunctionName'] = '';
            $properties['validationCondition'] = 0;
            $properties['validationMessage'] = '';
        }
    }

    private function requiredValidationScriptId(): int
    {
        if ($this->requiredValidationId !== null) {
            return $this->requiredValidationId;
        }

        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__facileforms_scripts'))
            ->where($this->db->quoteName('published') . ' = 1')
            ->where($this->db->quoteName('type') . ' = :type')
            ->where($this->db->quoteName('name') . ' = :name')
            ->order($this->db->quoteName('id') . ' DESC')
            ->bind(':type', 'Element Validation', ParameterType::STRING)
            ->bind(':name', 'ff_valuenotempty', ParameterType::STRING);
        $id = (int) $this->db->setQuery($query, 0, 1)->loadResult();
        if ($id <= 0) {
            throw new ToolException('The standard ff_valuenotempty validation is unavailable or unpublished.');
        }
        $this->requiredValidationId = $id;

        return $id;
    }

    /** @param array<string, mixed> $tree
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
        foreach ([
            'email_custom_template', 'mb_email_custom_template', 'mailchimp_email_field',
            'mailchimp_checkbox_field', 'mailchimp_text_html_mobile_field',
            'mailchimp_unsubscribe_field', 'salesforce_fields',
        ] as $column) {
            if (trim((string) ($form->$column ?? '')) !== '') {
                throw new ToolException('Fields cannot be renamed while a form template or integration may reference field names.');
            }
        }

        $root = $tree['properties'];
        if (trim((string) ($root['submittedScriptCode'] ?? '')) !== ''
            || (int) ($root['submittedScriptCondidtion'] ?? -1) !== -1) {
            throw new ToolException('Fields cannot be renamed while a submitted script is configured.');
        }
        foreach ($tree['children'] as $page) {
            foreach ($page['children'] as $node) {
                $properties = $node['properties'];
                if ((int) ($properties['validationId'] ?? 0) !== 0
                    || (int) ($properties['initId'] ?? 0) !== 0
                    || (int) ($properties['actionId'] ?? 0) !== 0
                    || trim((string) ($properties['validationCode'] ?? '')) !== ''
                    || trim((string) ($properties['initCode'] ?? '')) !== ''
                    || trim((string) ($properties['actionCode'] ?? '')) !== ''
                    || !empty($properties['validationFunctionName'])
                    || !empty($properties['initFunctionName'])
                    || !empty($properties['actionFunctionName'])
                    || !empty($properties['password']) || !empty($properties['mailback'])
                    || !empty($properties['mailbackAsSender']) || !empty($properties['mailbackfile'])
                    || !empty($properties['mailbackAccept']) || !empty($properties['mailbackConnectWith'])) {
                    throw new ToolException('Fields cannot be renamed while any field has scripts, validation, password or mailback settings.');
                }
            }
        }
    }
}
