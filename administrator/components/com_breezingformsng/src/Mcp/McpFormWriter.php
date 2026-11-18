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
     * @return array{id: int, name: string, title: string, published: bool, settings: array{width: int, height: int, autoheight: bool}, versioned: bool}
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

        foreach (['width', 'height', 'autoheight'] as $field) {
            if (array_key_exists($field, $arguments)) {
                $data[$field] = $arguments[$field];
            }
        }

        if (count($data) === 1) {
            throw new ToolException('Give at least one of name, title or description to update.');
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
            'published' => (int) $form->published === 1,
            'settings' => [
                'width' => (int) ($data['width'] ?? $form->width),
                'height' => (int) ($data['height'] ?? $form->height),
                'autoheight' => (bool) ($data['autoheight'] ?? $form->autoheight),
            ],
            'versioned' => $versioned,
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array{id: int, added: list<string>, updated: list<string>, removed: list<string>, field_count: int, versioned: bool, contentbuilder_synced: bool}
     * @throws ToolException
     */
    public function manageFields(array $arguments, User $user): array
    {
        if (!$user->authorise('core.edit', 'com_breezingformsng')) {
            throw new ToolException('You are not allowed to edit forms.');
        }

        $id = (int) $arguments['id'];
        $form = $this->options->getForm($id);
        if ($form === null || (int) $form->published === -2) {
            throw new ToolException('Form not found or cannot be edited.');
        }

        $tree = $this->managedTree($id);
        $originalTree = $tree;
        $current = [];
        foreach ($tree['children'] as $page) {
            foreach ($page['children'] as $node) {
                $properties = $node['properties'];
                $key = strtolower($properties['bfName']);
                if (isset($current[$key])) {
                    throw new ToolException('This form has duplicate field names and cannot be safely edited through MCP.');
                }
                $current[$key] = $properties;
            }
        }

        $add = $this->normalizeFields($arguments['add'] ?? []);
        $updates = $arguments['update'] ?? [];
        $removals = $arguments['remove'] ?? [];
        if (!is_array($updates) || !array_is_list($updates) || count($updates) > 30
            || !is_array($removals) || !array_is_list($removals) || count($removals) > 30) {
            throw new ToolException('Field updates and removals must be lists of at most 30 items.');
        }
        if ($add === [] && $updates === [] && $removals === []) {
            throw new ToolException('Give at least one field to add, update or remove.');
        }

        $updateByName = [];
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
            if ($change === []) {
                throw new ToolException('Give a new type, label or placeholder for each field update.');
            }
            if (isset($change['type']) && !in_array($change['type'], ['text', 'textarea'], true)) {
                throw new ToolException('Field types are limited to text and textarea.');
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
            $updateByName[$key] = $change;
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
            $recordCount = (int) $this->db->setQuery(
                $this->db->createQuery()
                    ->select('COUNT(*)')
                    ->from($this->db->quoteName('#__facileforms_records'))
                    ->where($this->db->quoteName('form') . ' = :form')
                    ->bind(':form', $id, ParameterType::INTEGER)
            )->loadResult();
            if ($recordCount > 0) {
                throw new ToolException('Fields cannot be removed while the form has submissions; existing records are preserved.');
            }
        }

        $resultPages = [];
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
                    $updated[] = $node['properties']['bfName'];
                }
                $result[] = $node;
            }

            if ($pageIndex === 0) {
                foreach ($add as $field) {
                    $result[] = $this->elementNode($field, count($result) + 1);
                }
            }
            foreach ($result as $index => &$node) {
                $node['properties']['orderNumber'] = $index + 1;
                $allFields[] = $node['properties']['bfName'];
            }
            unset($node);
            $page['children'] = $result;
            $resultPages[] = $page;
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

        try {
            $this->db->transactionStart();
            $versioning = new FormVersioning($this->db, $this->forms);
            $versioning->keepOrigin($id);
            $this->quickmode->save($id, $tree, 1, false);
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
     * @return list<array{type: string, name: string, label: string, placeholder: string}>
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
            if (!in_array($type, ['text', 'textarea'], true)) {
                throw new ToolException('Field types are limited to text and textarea.');
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

            $key = strtolower($name);
            if (isset($names[$key])) {
                throw new ToolException('Field names must be unique within the form.');
            }
            $names[$key] = true;
            $normalized[] = ['type' => $type, 'name' => $name, 'label' => $label, 'placeholder' => $placeholder];
        }

        return $normalized;
    }

    /** @param list<array{type: string, name: string, label: string, placeholder: string}> $fields */
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

    /** @param array{type: string, name: string, label: string, placeholder: string} $field */
    private function elementNode(array $field, int $order): array
    {
        return [
            'attributes' => ['id' => 'bfQuickModeElement' . $order . '_' . strtolower($field['name'])],
            'properties' => [
                'type' => 'element',
                'bfType' => $field['type'] === 'text' ? 'bfTextfield' : 'bfTextarea',
                'value' => '',
                'placeholder' => $field['placeholder'],
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
            ],
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
        foreach ($tree['children'] as $page) {
            if (!is_array($page) || !is_array($page['attributes'] ?? null) || !is_array($page['properties'] ?? null)) {
                throw new ToolException('This form has a malformed page that the MCP field editor cannot safely preserve.');
            }
            $pageId = (string) ($page['attributes']['id'] ?? '');
            if (!preg_match('/^bfQuickModePage[1-9][0-9]*$/D', $pageId)
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
                    || !in_array($properties['bfType'] ?? null, ['bfTextfield', 'bfTextarea'], true)
                    || array_diff($requiredProperties, array_keys($properties)) !== []
                    || !is_array($node['children'] ?? null) || $node['children'] !== []) {
                    throw new ToolException('This form contains a non-text field or nested structure the MCP field editor cannot safely preserve.');
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
}
