<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\File;
use Vcmb\Component\BreezingformsNG\Administrator\Exception\InvalidPackageException;
use Vcmb\Component\BreezingformsNG\Administrator\Service\FormVersioning;
use Vcmb\Component\BreezingformsNG\Administrator\Service\SqlDate;

final class PackageTransferModel extends BaseDatabaseModel
{
    private const FORMAT = 'breezingformsng-package';
    private const VERSION = 2;
    private const LIBRARY_COLUMNS = ['published', 'package', 'name', 'title', 'description', 'type', 'code', 'unit_tests'];

    /**
     * Custom template files of a form, by folder of media/breezingforms: the
     * engines look for the form name followed by one of these endings.
     */
    private const TEMPLATE_SUFFIXES = [
        'mailtpl' => ['.html.php', '.txt.php', '_mailback.html.php', '_mailback.txt.php'],
        'pdftpl' => [
            '_pdf_attachment.php',
            '_pdf_mailback_attachment.php',
            '_pdf_attachment_header.php',
            '_pdf_mailback_attachment_header.php',
            '_pdf_attachment_footer.php',
            '_pdf_mailback_attachment_footer.php',
            '_export_pdf.php',
            '_export_header_pdf.php',
            '_export_footer_pdf.php',
        ],
    ];

    /** Integrator tables holding the rows of a rule, with their column naming an element. */
    private const INTEGRATOR_CHILDREN = [
        'items' => ['#__facileforms_integrator_items', 'element_id'],
        'criteria_fixed' => ['#__facileforms_integrator_criteria_fixed', null],
        'criteria_form' => ['#__facileforms_integrator_criteria_form', 'element_id'],
        'criteria_joomla' => ['#__facileforms_integrator_criteria_joomla', null],
    ];

    private const EMPTY_SUMMARY = [
        'templates' => 0, 'rules' => 0, 'failed' => [], 'package' => '', 'unlinked' => 0, 'shared' => [], 'skipped' => [], 'conflicts' => [],
        'items' => ['forms' => [], 'scripts' => [], 'pieces' => [], 'menus' => [], 'rules' => [], 'templates' => []],
    ];

    public const STATUS_NEW = 'new';
    public const STATUS_REPLACE = 'replace';
    public const STATUS_IDENTICAL = 'identical';

    /** @var array<string, array<int, string>> What the running import does with each item, by part and position. */
    private array $statuses = [];

    /** @var array<int, true> Source ids of the forms the site already has as they are. */
    private array $identicalForms = [];

    /** Parts of a package the user can choose from on import, by key of the file. */
    public const SELECTABLE_PARTS = ['forms', 'scripts', 'pieces', 'menus'];

    /**
     * @var array{templates: int, rules: int, failed: list<string>, package: string, unlinked: int, shared: list<string>, skipped: list<string>, conflicts: list<string>, items: array<string, list<array<string, mixed>>>}
     *      skipped: the optional parts left out, as the user may not write them.
     *      items: what the import wrote, by kind, for the report shown after it.
     */
    private array $importSummary = self::EMPTY_SUMMARY;

    /** @var array<string, array<int, true>> Source ids imported only because a chosen item needs them, by part. */
    private array $required = [];

    /** @var array<string, string> Name of the form of each template file to write, by path. */
    private array $templateOwners = [];

    /** Notes of the form versions written by the running import. */
    private string $importedNote = '';

    private string $originNote = '';

    /** @var list<int> Every form written by the running import. */
    private array $importedFormIds = [];

    /** Language key part naming the items of each table found by name on import. */
    private const NAMED_KINDS = [
        '#__facileforms_forms' => 'FORMS',
        '#__facileforms_scripts' => 'SCRIPTS',
        '#__facileforms_pieces' => 'PIECES',
    ];

    /**
     * @return list<string>
     */
    public function getPackages(): array
    {
        $db = $this->getDatabase();
        $packages = $this->getAssignedPackages();
        $query = $db->createQuery()->select($db->quoteName('id'))->from($db->quoteName('#__facileforms_packages'));
        $packages = array_merge($packages, $db->setQuery($query)->loadColumn() ?: []);
        // A package row with an empty id cannot be chosen or exported.
        $packages = array_values(array_unique(array_filter($packages, static fn(mixed $id): bool => is_string($id) && $id !== '')));
        sort($packages, SORT_NATURAL | SORT_FLAG_CASE);

        return $packages;
    }

    /**
     * Packages named by at least one form, script, piece or menu.
     *
     * @return list<string>
     */
    private function getAssignedPackages(): array
    {
        $db = $this->getDatabase();
        $packages = [];

        foreach (['#__facileforms_forms', '#__facileforms_scripts', '#__facileforms_pieces', '#__facileforms_compmenus'] as $table) {
            $query = $db->createQuery()
                ->select('DISTINCT ' . $db->quoteName('package'))
                ->from($db->quoteName($table))
                ->where($db->quoteName('package') . ' != ' . $db->quote(''));
            $packages = array_merge($packages, $db->setQuery($query)->loadColumn() ?: []);
        }

        return array_values(array_unique(array_filter($packages, 'is_string')));
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getPackageProfiles(): array
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()->select('*')->from($db->quoteName('#__facileforms_packages'));
        $profiles = $db->setQuery($query)->loadAssocList('id') ?: [];
        unset($profiles['']);

        foreach ($this->getPackages() as $id) {
            $profiles[$id] ??= ['id' => $id];
        }

        // Packages with assigned items export those items, the others a selection.
        foreach ($this->getAssignedPackages() as $id) {
            $profiles[$id]['assigned'] = true;
        }

        ksort($profiles, SORT_NATURAL | SORT_FLAG_CASE);

        return $profiles;
    }

    /**
     * Stores the package details, so the package can be exported again later.
     *
     * @param array<string, mixed> $metadata
     */
    public function savePackage(string $package, array $metadata): void
    {
        $package = trim($package);

        if ($package === '') {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_SAVED_PACKAGE_REQUIRED'));
        }

        $this->savePackageMetadata($package, $this->metadataForExport($package, $metadata));
    }

    /**
     * Removes a package while keeping its forms, scripts, pieces and menus.
     */
    public function deletePackage(string $package): void
    {
        $package = trim($package);

        if ($package === '' || !in_array($package, $this->getPackages(), true)) {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_NOT_FOUND'));
        }

        $db = $this->getDatabase();
        $db->transactionStart();

        try {
            foreach (['#__facileforms_forms', '#__facileforms_scripts', '#__facileforms_pieces', '#__facileforms_compmenus'] as $table) {
                $query = $db->createQuery()
                    ->update($db->quoteName($table))
                    ->set($db->quoteName('package') . ' = ' . $db->quote(''))
                    ->where($db->quoteName('package') . ' = :package')
                    ->bind(':package', $package);
                $db->setQuery($query)->execute();
            }

            $query = $db->createQuery()
                ->delete($db->quoteName('#__facileforms_packages'))
                ->where($db->quoteName('id') . ' = :package')
                ->bind(':package', $package);
            $db->setQuery($query)->execute();
            $db->transactionCommit();
        } catch (\Throwable $exception) {
            $db->transactionRollback();
            throw $exception;
        }
    }

    /**
     * What is attached to a package, by kind.
     *
     * @return array{forms: list<array<string, mixed>>, scripts: list<array<string, mixed>>, pieces: list<array<string, mixed>>, menus: list<array<string, mixed>>}
     */
    public function getPackageContents(string $package): array
    {
        return [
            'forms' => $this->loadPackageSummary('#__facileforms_forms', $package),
            'scripts' => $this->loadPackageSummary('#__facileforms_scripts', $package),
            'pieces' => $this->loadPackageSummary('#__facileforms_pieces', $package),
            'menus' => $this->loadPackageSummary('#__facileforms_compmenus', $package),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadPackageSummary(string $table, string $package): array
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'name', 'title', 'published']))
            ->from($db->quoteName($table))
            ->where($db->quoteName('package') . ' = :package')
            ->order([$db->quoteName('name'), $db->quoteName('id')])
            ->bind(':package', $package);

        return $db->setQuery($query)->loadAssocList() ?: [];
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function export(string $package, array $metadata = [], bool $withTemplates = false, bool $withIntegrator = false): string
    {
        if ($package === '') {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_PACKAGE_REQUIRED'));
        }

        return $this->buildPayload(
            $package,
            $this->loadPackageRows('#__facileforms_forms', $package),
            $this->loadPackageRows('#__facileforms_scripts', $package),
            $this->loadPackageRows('#__facileforms_pieces', $package),
            $this->includeMenuAncestors($this->loadPackageRows('#__facileforms_compmenus', $package)),
            $metadata,
            $withTemplates,
            $withIntegrator
        );
    }

    /**
     * @param array<string, list<int>>|null $selection Positions in the file of the items to import,
     *        by part (forms, scripts, pieces, menus); null imports the whole file.
     * @return list<array{id: int, name: string, packagePublished: int, previousPublished: ?int}>
     *         The forms saved as "Backend only" that the import left unpublished.
     */
    public function import(
        string $filename,
        bool $allowTemplates = false,
        string $sourceName = '',
        bool $allowIntegrator = false,
        ?array $selection = null
    ): array {
        $this->importSummary = self::EMPTY_SUMMARY;
        $this->importedFormIds = [];
        $this->required = [];
        $this->templateOwners = [];
        $this->identicalForms = [];
        // The uploaded file is a temporary one: its name of origin goes in the version notes.
        $sourceName = $sourceName !== '' ? $sourceName : basename($filename);
        $payload = $this->readPayload($filename);

        if ($selection !== null) {
            $payload = $this->selectFromPayload($payload, $selection);
        }

        // Templates and Integrator rules are PHP code: without the permission to
        // write them, they are left out and the rest of the package is imported.
        // Even an empty rule list would replace the existing rules of a form.
        if (!$allowIntegrator && !empty($payload['integrator'])) {
            unset($payload['integrator']);
            $this->importSummary['skipped'][] = 'integrator';
        }
        if (!$allowTemplates && !empty($payload['templates'])) {
            unset($payload['templates']);
            $this->importSummary['skipped'][] = 'templates';
        }

        $db = $this->getDatabase();
        $package = $payload['package'];
        // What is already on the site as it is in the file is left as it is:
        // no new version, and the elements of a form keep their ids.
        $this->statuses = $this->compareItems($payload);
        $this->importedNote = Text::sprintf('COM_BREEZINGFORMSNG_FORM_VERSION_NOTE_IMPORTED', $package, $sourceName);
        $this->originNote = Text::sprintf('COM_BREEZINGFORMSNG_FORM_VERSION_NOTE_BEFORE_IMPORT', $package);
        $db->transactionStart();

        try {
            $scriptIds = $this->importLibraries('#__facileforms_scripts', (array) ($payload['scripts'] ?? []), $package, $this->packageItemModel('Script'));
            $pieceIds = $this->importLibraries('#__facileforms_pieces', (array) ($payload['pieces'] ?? []), $package, $this->packageItemModel('Piece'));
            $imported = [];
            $rulesInFile = [];
            foreach (is_array($payload['integrator'] ?? null) ? $payload['integrator'] : [] as $entry) {
                if (is_array($entry) && is_int($entry['form_source_id'] ?? null)) {
                    $rulesInFile[$entry['form_source_id']] = true;
                }
            }
            $retiredForms = $this->importForms((array) ($payload['forms'] ?? []), $package, $scriptIds, $pieceIds, $imported, $rulesInFile);
            $this->importSummary['rules'] = $this->importIntegrator($payload['integrator'] ?? [], $imported);
            $this->importSummary['unlinked'] = $this->countUnlinkedRules($this->importedFormIds);
            $templates = $this->templatesToWrite($payload['templates'] ?? [], $imported, $allowTemplates);

            if (($payload['version'] ?? null) === self::VERSION) {
                $this->importMenus((array) ($payload['menus'] ?? []), $package);
                if (isset($payload['metadata'])) {
                    if (!is_array($payload['metadata'])) {
                        throw new InvalidPackageException();
                    }
                    $this->savePackageMetadata($package, $payload['metadata']);
                }
            }

            $db->transactionCommit();
            $this->importSummary['package'] = $package;
        } catch (\Throwable $exception) {
            $db->transactionRollback();
            throw $exception;
        }

        // Files cannot be rolled back: they are written once the database import is saved.
        $this->writeTemplates($templates);

        foreach ($retiredForms as $form) {
            $previous = $form['previousPublished'] ?? 'none';
            Log::add(
                'Package import: form ' . $form['id'] . ' ('
                . preg_replace('/[\x00-\x1F\x7F]+/', ' ', $form['name'] . ', package ' . $package)
                . ') was saved as "Backend only" and is now unpublished'
                . " (published in the package: {$form['packagePublished']}, before the import: {$previous}).",
                Log::WARNING,
                'com_breezingformsng'
            );
        }

        return $retiredForms;
    }

    /**
     * Reads and checks a package file.
     *
     * @return array<string, mixed>
     */
    private function readPayload(string $filename): array
    {
        $contents = file_get_contents($filename);
        if ($contents === false) {
            throw new InvalidPackageException(null, InvalidPackageException::READ);
        }

        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidPackageException($exception, InvalidPackageException::JSON);
        }

        if (!is_array($payload) || ($payload['format'] ?? '') !== self::FORMAT || !is_string($payload['package'] ?? null) || $payload['package'] === '') {
            throw new InvalidPackageException();
        }

        if (!in_array($payload['version'] ?? null, [1, self::VERSION], true)) {
            throw new InvalidPackageException(null, InvalidPackageException::VERSION);
        }
        foreach (self::SELECTABLE_PARTS as $part) {
            if (isset($payload[$part]) && (!is_array($payload[$part]) || !array_is_list($payload[$part]))) {
                throw new InvalidPackageException();
            }
        }

        return $payload;
    }

    /**
     * What importing each item of a package file would do: create it, replace
     * the item of the same name, or nothing, as the site has it as it is.
     *
     * @return array<string, list<string>> Status of each item, by part, in the order of the file.
     */
    public function compareFile(string $filename, bool $allowTemplates, bool $allowIntegrator): array
    {
        $payload = $this->readPayload($filename);
        // The parts the user may not write are left out of the import, and of the comparison.
        if (!$allowIntegrator) {
            unset($payload['integrator']);
        }
        if (!$allowTemplates) {
            unset($payload['templates']);
        }

        return array_map('array_values', $this->compareItems($payload));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, array<int, string>> Status of each item, by part and position in the file.
     */
    private function compareItems(array $payload): array
    {
        $package = (string) $payload['package'];
        $statuses = [];
        $libraryIds = [];

        foreach (['scripts' => '#__facileforms_scripts', 'pieces' => '#__facileforms_pieces'] as $part => $table) {
            $statuses[$part] = [];
            $libraryIds[$part] = [];
            foreach ((array) ($payload[$part] ?? []) as $position => $row) {
                if (!is_array($row) || !is_array($row['data'] ?? null)) {
                    $statuses[$part][$position] = self::STATUS_NEW;
                    continue;
                }
                $data = $this->only($row['data'], self::LIBRARY_COLUMNS);
                $data['package'] = $package;
                $id = $this->findId($table, $package, trim((string) ($data['name'] ?? '')));
                if (is_int($row['source_id'] ?? null)) {
                    // A new item gets an id of its own, which no form of the site uses yet.
                    $libraryIds[$part][$row['source_id']] = $id > 0 ? $id : -1;
                }
                $statuses[$part][$position] = $id === 0 ? self::STATUS_NEW
                    : ($this->rowMatches($table, $id, $data) ? self::STATUS_IDENTICAL : self::STATUS_REPLACE);
            }
        }

        $rulesBySource = [];
        foreach (is_array($payload['integrator'] ?? null) ? $payload['integrator'] : [] as $entry) {
            if (is_array($entry) && is_int($entry['form_source_id'] ?? null)) {
                $rulesBySource[$entry['form_source_id']] = $entry['rules'] ?? null;
            }
        }
        $templatesBySource = [];
        foreach (is_array($payload['templates'] ?? null) ? $payload['templates'] : [] as $entry) {
            if (is_array($entry) && is_int($entry['form_source_id'] ?? null)) {
                $templatesBySource[$entry['form_source_id']][] = $entry;
            }
        }

        $statuses['forms'] = [];
        foreach ((array) ($payload['forms'] ?? []) as $position => $form) {
            if (!is_array($form) || !is_array($form['data'] ?? null) || !is_array($form['elements'] ?? null)) {
                $statuses['forms'][$position] = self::STATUS_NEW;
                continue;
            }
            [$data] = $this->prepareFormData($form['data'], $package, $libraryIds['scripts'], $libraryIds['pieces']);
            $id = $this->findId('#__facileforms_forms', $package, $data['name']);
            $sourceId = is_int($form['source_id'] ?? null) ? $form['source_id'] : null;
            $statuses['forms'][$position] = $id === 0 ? self::STATUS_NEW : (
                $this->formMatches(
                    $id,
                    $data,
                    $form['elements'],
                    $libraryIds,
                    $sourceId !== null && array_key_exists($sourceId, $rulesBySource) ? [$rulesBySource[$sourceId]] : [],
                    $sourceId !== null ? $templatesBySource[$sourceId] ?? [] : []
                ) ? self::STATUS_IDENTICAL : self::STATUS_REPLACE
            );
        }

        $statuses['menus'] = $this->compareMenus((array) ($payload['menus'] ?? []), $package);

        return $statuses;
    }

    /**
     * @param array<mixed> $rows
     * @return array<int, string>
     */
    private function compareMenus(array $rows, string $package): array
    {
        $columns = $this->tableColumns('#__facileforms_compmenus');
        $statuses = array_fill_keys(array_keys($rows), self::STATUS_NEW);
        $menuIds = [];
        $pending = $rows;

        // A menu is found under its parent: the parents are compared first.
        do {
            $progress = false;
            foreach ($pending as $position => $row) {
                if (!is_array($row) || !is_int($row['source_id'] ?? null) || !is_array($row['data'] ?? null)) {
                    unset($pending[$position]);
                    continue;
                }
                $sourceParent = (int) ($row['data']['parent'] ?? 0);
                if ($sourceParent > 0 && !isset($menuIds[$sourceParent])) {
                    continue;
                }
                $data = $this->only($row['data'], $columns, ['id', 'checked_out', 'checked_out_time']);
                $data['package'] = $package;
                $data['parent'] = $sourceParent > 0 ? $menuIds[$sourceParent] : 0;
                $id = $data['parent'] < 0 ? 0 : $this->findMenuId(
                    $package,
                    trim((string) ($data['name'] ?? '')),
                    trim((string) ($data['title'] ?? '')),
                    (int) $data['parent']
                );
                $menuIds[$row['source_id']] = $id > 0 ? $id : -1;
                $statuses[$position] = $id === 0 ? self::STATUS_NEW
                    : ($this->rowMatches('#__facileforms_compmenus', $id, $data) ? self::STATUS_IDENTICAL : self::STATUS_REPLACE);
                unset($pending[$position]);
                $progress = true;
            }
        } while ($progress && $pending !== []);

        return $statuses;
    }

    /**
     * Whether the form of the site holds what the file brings: its options,
     * its elements in the same order, and the template files and Integrator
     * rules the file gives for it.
     *
     * @param array<string, mixed>               $data     The options as the import would write them.
     * @param array<mixed>                       $elements The elements of the file.
     * @param array<string, array<int, int>>     $libraryIds Ids of the site for the scripts and pieces of the file.
     * @param array<int, mixed>                  $rules    The rules of the file for the form, in a list of one, or none.
     * @param list<array<string, mixed>>         $templates
     */
    private function formMatches(int $id, array $data, array $elements, array $libraryIds, array $rules, array $templates): bool
    {
        if (!$this->rowMatches('#__facileforms_forms', $id, $this->without($data, ['checked_out', 'checked_out_time']))) {
            return false;
        }

        $db = $this->getDatabase();
        $siteElements = $db->setQuery(
            $db->createQuery()->select('*')->from($db->quoteName('#__facileforms_elements'))
                ->where($db->quoteName('form') . ' = :form')->order($db->quoteName('id'))
                ->bind(':form', $id, ParameterType::INTEGER)
        )->loadAssocList() ?: [];
        if (count($siteElements) !== count($elements)) {
            return false;
        }

        $elementColumns = $this->tableColumns('#__facileforms_elements');
        $fileOrder = [];
        $siteOrder = [];
        foreach (array_values($elements) as $position => $element) {
            if (!is_array($element)) {
                return false;
            }
            $elementData = $this->only($element, $elementColumns, ['id', 'form', 'checked_out', 'checked_out_time']);
            $this->remapReferences($elementData, $libraryIds['scripts'], $libraryIds['pieces']);
            if (!$this->valuesMatch($elementData, $siteElements[$position])) {
                return false;
            }
            if (is_int($element['source_id'] ?? null)) {
                $fileOrder[$element['source_id']] = $position;
            }
            $siteOrder[(int) $siteElements[$position]['id']] = $position;
        }

        $name = (string) $data['name'];
        foreach ($templates as $entry) {
            $content = is_string($entry['content'] ?? null) ? base64_decode($entry['content'], true) : false;
            $folder = $entry['folder'] ?? null;
            $suffix = $entry['suffix'] ?? null;
            if (
                $content === false || !is_string($folder) || !isset(self::TEMPLATE_SUFFIXES[$folder])
                || !in_array($suffix, self::TEMPLATE_SUFFIXES[$folder], true) || !$this->isFileSafeName($name)
            ) {
                return false;
            }
            $path = JPATH_SITE . '/media/breezingforms/' . $folder . '/' . $name . $suffix;
            if (!is_file($path) || file_get_contents($path) !== $content) {
                return false;
            }
        }

        foreach ($rules as $fileRules) {
            $fileRules = is_array($fileRules) ? $this->canonicalRules($fileRules, $fileOrder) : null;
            if ($fileRules === null || $fileRules !== $this->canonicalRules($this->integratorRules($id), $siteOrder)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Integrator rules as plain values, their elements by position in the form.
     *
     * @param array<mixed>    $rules
     * @param array<int, int> $elementPositions Position of each element, by id.
     * @return list<array<string, mixed>>|null Null for rules the import would reject.
     */
    private function canonicalRules(array $rules, array $elementPositions): ?array
    {
        $ruleColumns = $this->tableColumns('#__facileforms_integrator_rules');
        $canonical = [];
        foreach ($rules as $rule) {
            if (!is_array($rule) || !is_array($rule['data'] ?? null)) {
                return null;
            }
            $entry = ['data' => $this->plainValues($this->only($rule['data'], $ruleColumns, ['id', 'form_id']))];
            foreach (self::INTEGRATOR_CHILDREN as $key => [$table, $elementColumn]) {
                $entry[$key] = [];
                $columns = $this->tableColumns($table);
                foreach ((array) ($rule[$key] ?? []) as $child) {
                    if (!is_array($child) || !is_array($child['data'] ?? null)) {
                        return null;
                    }
                    $row = ['data' => $this->plainValues($this->only($child['data'], $columns, array_values(array_filter(['id', 'rule_id', $elementColumn]))))];
                    if ($elementColumn !== null) {
                        $row['element'] = $elementPositions[(int) ($child['element_source_id'] ?? 0)] ?? -1;
                    }
                    $entry[$key][] = $row;
                }
            }
            $canonical[] = $entry;
        }

        return $canonical;
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, string|null>
     */
    private function plainValues(array $values): array
    {
        $plain = [];
        foreach ($values as $column => $value) {
            $plain[$column] = $value === null ? null : (is_scalar($value) ? (string) $value : (string) json_encode($value));
        }
        ksort($plain);

        return $plain;
    }

    /**
     * Whether the row of the site holds each value given, as the database returns it.
     *
     * @param array<string, mixed> $values
     */
    private function rowMatches(string $table, int $id, array $values): bool
    {
        $db = $this->getDatabase();
        $row = $db->setQuery(
            $db->createQuery()->select('*')->from($db->quoteName($table))
                ->where($db->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER)
        )->loadAssoc();

        return is_array($row) && $this->valuesMatch($values, $row);
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, mixed> $row
     */
    private function valuesMatch(array $values, array $row): bool
    {
        foreach ($this->plainValues($values) as $column => $value) {
            if (!array_key_exists($column, $row) || $value !== ($row[$column] === null ? null : (string) $row[$column])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Keeps the items of the file the user chose, with what they need: the
     * scripts and pieces a chosen form uses, which it would otherwise point to
     * other items of the site, the parents of a chosen menu, and the template
     * files and Integrator rules of the chosen forms only.
     *
     * @param array<string, mixed>     $payload
     * @param array<string, list<int>> $selection Positions in the file, by part.
     * @return array<string, mixed>
     */
    private function selectFromPayload(array $payload, array $selection): array
    {
        $chosen = [];
        foreach (self::SELECTABLE_PARTS as $part) {
            $rows = $payload[$part] ?? [];
            if (!is_array($rows) || !array_is_list($rows)) {
                throw new InvalidPackageException();
            }
            $payload[$part] = $rows;
            $chosen[$part] = array_fill_keys($selection[$part] ?? [], true);
        }

        $needed = ['scripts' => [], 'pieces' => []];
        $formSources = [];
        foreach ($payload['forms'] as $position => $form) {
            if (!isset($chosen['forms'][$position]) || !is_array($form)) {
                continue;
            }
            if (is_int($form['source_id'] ?? null)) {
                $formSources[$form['source_id']] = true;
            }
            foreach (array_merge([$form['data'] ?? null], is_array($form['elements'] ?? null) ? array_values($form['elements']) : []) as $row) {
                foreach (is_array($row) ? $row : [] as $column => $value) {
                    if (preg_match('/^script[1-3]id$/', (string) $column) === 1 && (int) $value > 0) {
                        $needed['scripts'][(int) $value] = true;
                    }
                    if (preg_match('/^piece[1-4]id$/', (string) $column) === 1 && (int) $value > 0) {
                        $needed['pieces'][(int) $value] = true;
                    }
                }
            }
        }

        foreach ($needed as $part => $sourceIds) {
            foreach ($payload[$part] as $position => $row) {
                $sourceId = is_array($row) ? ($row['source_id'] ?? null) : null;
                if (!isset($chosen[$part][$position]) && is_int($sourceId) && isset($sourceIds[$sourceId])) {
                    $chosen[$part][$position] = true;
                    $this->required[$part][$sourceId] = true;
                }
            }
        }

        // A menu is written under its parent, which comes with it.
        $menuPositions = [];
        foreach ($payload['menus'] as $position => $row) {
            if (is_array($row) && is_int($row['source_id'] ?? null)) {
                $menuPositions[$row['source_id']] = $position;
            }
        }
        $queue = array_keys($chosen['menus']);
        while ($queue !== []) {
            $row = $payload['menus'][array_pop($queue)] ?? null;
            $parent = is_array($row) && is_array($row['data'] ?? null) ? (int) ($row['data']['parent'] ?? 0) : 0;
            if ($parent > 0 && isset($menuPositions[$parent]) && !isset($chosen['menus'][$menuPositions[$parent]])) {
                $chosen['menus'][$menuPositions[$parent]] = true;
                $this->required['menus'][$parent] = true;
                $queue[] = $menuPositions[$parent];
            }
        }

        $kept = 0;
        foreach (self::SELECTABLE_PARTS as $part) {
            $payload[$part] = array_values(array_intersect_key($payload[$part], $chosen[$part]));
            $kept += count($payload[$part]);
        }
        if ($kept === 0) {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_NOTHING_SELECTED'));
        }

        // An entry that names no form is left for the import to reject.
        foreach (['integrator', 'templates'] as $part) {
            if (is_array($payload[$part] ?? null)) {
                $payload[$part] = array_values(array_filter(
                    $payload[$part],
                    static fn (mixed $entry): bool => !is_array($entry) || !is_int($entry['form_source_id'] ?? null)
                        || isset($formSources[$entry['form_source_id']])
                ));
            }
        }

        return $payload;
    }

    /**
     * @return array{forms: list<array<string, mixed>>, scripts: list<array<string, mixed>>, pieces: list<array<string, mixed>>, menus: list<array<string, mixed>>}
     */
    public function getExportChoices(): array
    {
        return [
            "forms" => $this->loadChoices("#__facileforms_forms"),
            "scripts" => $this->loadChoices("#__facileforms_scripts"),
            "pieces" => $this->loadChoices("#__facileforms_pieces"),
            "menus" => $this->loadChoices("#__facileforms_compmenus"),
        ];
    }

    /**
     * @param array<mixed>         $formIds
     * @param array<mixed>         $scriptIds
     * @param array<mixed>         $pieceIds
     * @param array<mixed>         $menuIds
     * @param array<string, mixed> $metadata
     */
    public function exportSelection(string $package, array $formIds, array $scriptIds, array $pieceIds, array $menuIds = [], array $metadata = [], bool $withTemplates = false, bool $withIntegrator = false): string
    {
        $package = trim($package);

        if ($package === "") {
            throw new \RuntimeException(Text::_("COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_PACKAGE_REQUIRED"));
        }

        return $this->buildPayload(
            $package,
            $this->loadRowsByIds('#__facileforms_forms', $formIds),
            $this->loadRowsByIds('#__facileforms_scripts', $scriptIds),
            $this->loadRowsByIds('#__facileforms_pieces', $pieceIds),
            $this->includeMenuAncestors($this->loadRowsByIds('#__facileforms_compmenus', $menuIds)),
            $metadata,
            $withTemplates,
            $withIntegrator
        );
    }

    /**
     * The package file of these items, menus with their ancestors: the whole
     * package and a selection export the same way.
     *
     * @param array<array<string, mixed>> $forms
     * @param array<array<string, mixed>> $scripts
     * @param array<array<string, mixed>> $pieces
     * @param array<array<string, mixed>> $menus
     * @param array<string, mixed>        $metadata
     */
    private function buildPayload(
        string $package,
        array $forms,
        array $scripts,
        array $pieces,
        array $menus,
        array $metadata,
        bool $withTemplates,
        bool $withIntegrator
    ): string {
        if ($forms === [] && $scripts === [] && $pieces === [] && $menus === []) {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_INSTALLER_SELECTAPKG'));
        }

        $this->assertImportableNames('#__facileforms_forms', $forms, true);
        $this->assertImportableNames('#__facileforms_scripts', $scripts, true);
        $this->assertImportableNames('#__facileforms_pieces', $pieces, true);

        $formIds = array_values(array_map(static fn(array $form): int => (int) $form['id'], $forms));
        $elementsByForm = [];

        foreach ($formIds === [] ? [] : $this->loadElements($formIds) as $element) {
            // The source id lets the Integrator rules find their elements again.
            $elementsByForm[(int) $element['form']][] = ['source_id' => (int) $element['id']] + $this->without($element, ['id', 'form']);
        }

        $payloadForms = [];
        foreach ($forms as $form) {
            $sourceId = (int) $form['id'];
            $payloadForms[] = [
                'source_id' => $sourceId,
                'data' => $this->without($form, ['id', 'created', 'created_by', 'modified', 'modified_by']),
                'elements' => $elementsByForm[$sourceId] ?? [],
            ];
        }

        $payload = [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'package' => $package,
            'exported_at' => (new \Joomla\CMS\Date\Date())->toSql(),
            'scripts' => array_map(fn(array $row): array => $this->libraryExportRow($row), $scripts),
            'pieces' => array_map(fn(array $row): array => $this->libraryExportRow($row), $pieces),
            'menus' => array_map(fn(array $row): array => $this->menuExportRow($row), $menus),
            'forms' => $payloadForms,
            'metadata' => $this->metadataForExport($package, $metadata),
        ];
        $payload += $this->formExtras($forms, $withTemplates, $withIntegrator);

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    }

    /**
     * @param array<string, mixed> $submitted
     * @return array<string, string>
     */
    private function metadataForExport(string $package, array $submitted): array
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__facileforms_packages'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $package);
        $stored = $db->setQuery($query)->loadAssoc() ?: [];
        $fields = ['name', 'version', 'title', 'author', 'email', 'url', 'description', 'copyright'];
        $metadata = ['created' => (string) ($stored['created'] ?? (new \Joomla\CMS\Date\Date())->toSql())];

        foreach ($fields as $field) {
            $value = $submitted[$field] ?? $stored[$field] ?? '';
            if (!is_string($value)) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_METADATA_INVALID'));
            }
            $metadata[$field] = trim($value);
        }

        $metadata['name'] = $metadata['name'] ?: $package;

        return $metadata;
    }

    /**
     * @param array<mixed> $metadata
     */
    private function savePackageMetadata(string $package, array $metadata): void
    {
        $db = $this->getDatabase();
        $columns = $this->tableColumns('#__facileforms_packages');
        // The audit columns are stamped here, never taken from a form or a file.
        $data = $this->only($metadata, $columns, ['id', 'created_by', 'modified', 'modified_by']);
        $data['id'] = $package;

        foreach ($data as $value) {
            if (!is_string($value)) {
                throw new InvalidPackageException();
            }
        }

        $now = (new Date())->toSql();
        $username = (string) $this->getCurrentUser()->username;
        $data['modified'] = $now;
        $data['modified_by'] = $username;

        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__facileforms_packages'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $package);

        if ($db->setQuery($query)->loadResult() !== null) {
            unset($data['created']);
            $object = (object) $data;
            $db->updateObject('#__facileforms_packages', $object, 'id');
        } else {
            // An imported package keeps the creation date of its file.
            $data['created'] = SqlDate::normalise($data['created'] ?? '') ?? $now;
            $data['created_by'] = $username;
            $object = (object) $data;
            $db->insertObject('#__facileforms_packages', $object);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array{source_id: int, data: array<string, mixed>}
     */
    private function menuExportRow(array $row): array
    {
        return ['source_id' => (int) $row['id'], 'data' => $this->without($row, ['id'])];
    }

    /**
     * @param array<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function includeMenuAncestors(array $rows): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        foreach ($rows as $row) {
            $seen = [];
            $parent = (int) $row['parent'];
            while ($parent > 0) {
                // An export error: no file is involved yet.
                if (isset($seen[$parent])) {
                    throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_MENU_PARENT_INVALID', htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8')));
                }
                $seen[$parent] = true;
                if (!isset($byId[$parent])) {
                    $ancestor = $this->loadRowsByIds('#__facileforms_compmenus', [$parent]);
                    if ($ancestor === []) {
                        throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_MENU_PARENT_INVALID', htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8')));
                    }
                    $byId[$parent] = $ancestor[0];
                }
                $parent = (int) $byId[$parent]['parent'];
            }
        }

        ksort($byId);

        return array_values($byId);
    }

    /**
     * @param array<mixed> $rows
     */
    private function importMenus(array $rows, string $package): void
    {
        $db = $this->getDatabase();
        $columns = $this->tableColumns('#__facileforms_compmenus');
        $pending = [];
        $importedIds = [];

        $identical = [];
        foreach ($rows as $position => $row) {
            if (!is_array($row) || !is_int($row['source_id'] ?? null)
                || $row['source_id'] <= 0 || !is_array($row['data'] ?? null)
                || isset($pending[$row['source_id']])) {
                throw new InvalidPackageException();
            }
            $pending[$row['source_id']] = $row['data'];
            if (($this->statuses['menus'][$position] ?? null) === self::STATUS_IDENTICAL) {
                $identical[$row['source_id']] = true;
            }
        }

        while ($pending !== []) {
            $progress = false;
            foreach ($pending as $sourceId => $source) {
                $sourceParent = (int) ($source['parent'] ?? 0);
                if ($sourceParent > 0 && !isset($importedIds[$sourceParent])) {
                    continue;
                }

                $data = $this->only($source, $columns, ['id']);
                $data['package'] = $package;
                $data['parent'] = $sourceParent > 0 ? $importedIds[$sourceParent] : 0;
                $name = trim((string) ($data['name'] ?? ''));
                $title = trim((string) ($data['title'] ?? ''));
                if ($title === '') {
                    throw new InvalidPackageException();
                }

                $id = $this->findMenuId($package, $name, $title, (int) $data['parent']);
                $this->importSummary['items']['menus'][] = [
                    'id' => 0, 'name' => $name, 'title' => $title,
                    'action' => $id > 0 ? (isset($identical[$sourceId]) ? self::STATUS_IDENTICAL : 'replaced') : 'created',
                    'required' => isset($this->required['menus'][$sourceId]),
                ];
                // A menu the site has as it is in the file is left as it is.
                if ($id > 0 && !isset($identical[$sourceId])) {
                    $data['id'] = $id;
                    $object = (object) $data;
                    $db->updateObject('#__facileforms_compmenus', $object, 'id');
                } elseif ($id === 0) {
                    $query = $db->createQuery()
                        ->select('COALESCE(MAX(' . $db->quoteName('id') . '), 0) + 1')
                        ->from($db->quoteName('#__facileforms_compmenus'));
                    $data['id'] = (int) $db->setQuery($query)->loadResult();
                    $object = (object) $data;
                    $db->insertObject('#__facileforms_compmenus', $object);
                    $id = $data['id'];
                }

                $importedIds[$sourceId] = $id;
                $this->importSummary['items']['menus'][array_key_last($this->importSummary['items']['menus'])]['id'] = $id;
                unset($pending[$sourceId]);
                $progress = true;
            }

            if (!$progress) {
                throw new InvalidPackageException();
            }
        }
    }

    private function findMenuId(string $package, string $name, string $title, int $parent): int
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__facileforms_compmenus'))
            ->where($db->quoteName('package') . ' = :package')
            ->where($db->quoteName('name') . ' = :name')
            ->where($db->quoteName('title') . ' = :title')
            ->where($db->quoteName('parent') . ' = :parent')
            ->bind(':package', $package)
            ->bind(':name', $name)
            ->bind(':title', $title)
            ->bind(':parent', $parent);

        return (int) $db->setQuery($query, 0, 1)->loadResult();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadChoices(string $table): array
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName(["id", "package", "name", "title"]))
            ->from($db->quoteName($table))
            ->order([$db->quoteName("package"), $db->quoteName("name"), $db->quoteName("id")]);

        return $db->setQuery($query)->loadAssocList() ?: [];
    }

    /**
     * @param array<mixed> $ids
     * @return list<array<string, mixed>>
     */
    private function loadRowsByIds(string $table, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map("intval", $ids), static fn(int $id): bool => $id > 0)));

        if ($ids === []) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select("*")
            ->from($db->quoteName($table))
            ->whereIn($db->quoteName("id"), $ids)
            ->order($db->quoteName("id"));

        return $db->setQuery($query)->loadAssocList() ?: [];
    }

    /**
     * @param array<mixed> $rows
     * @return array<int, int>
     */
    private function importLibraries(string $table, array $rows, string $package, PackageItemModel $items): array
    {
        $db = $this->getDatabase();
        $sourceIds = [];
        $written = [];

        foreach ($rows as $row) {
            if (!is_array($row) || !is_array($row['data'] ?? null) || !is_int($row['source_id'] ?? null)) {
                throw new InvalidPackageException();
            }
        }
        $this->assertImportableNames($table, $this->fileNames($rows));

        $part = str_replace('#__facileforms_', '', $table);
        foreach ($rows as $position => $row) {
            $data = $this->only($row['data'], self::LIBRARY_COLUMNS);
            $data['package'] = $package;
            $name = trim((string) ($data['name'] ?? ''));
            $id = $this->findId($table, $package, $name);
            $this->assertNotWrittenTwice($table, $written, $id, $name);
            if ($id > 0 && ($this->statuses[$part][$position] ?? null) === self::STATUS_IDENTICAL) {
                $sourceIds[$row['source_id']] = $id;
                $written[$id] = true;
                $this->importSummary['items'][$part][] = [
                    'id' => $id, 'name' => $name, 'title' => (string) ($data['title'] ?? ''), 'action' => self::STATUS_IDENTICAL,
                    'required' => isset($this->required[$part][$row['source_id']]),
                ];
                continue;
            }
            $action = $id > 0 ? 'replaced' : 'created';
            if ($id > 0) {
                // The item replaced keeps its state as its version of origin.
                $items->keepOrigin($id);
                $data['id'] = $id;
                $object = (object) $data;
                $db->updateObject($table, $object, 'id');
            } else {
                $object = (object) $data;
                $db->insertObject($table, $object, 'id');
                $id = (int) $db->insertid();
            }
            $items->recordVersion($id);
            $this->importSummary['items'][$part][] = [
                'id' => $id, 'name' => $name, 'title' => (string) ($data['title'] ?? ''), 'action' => $action,
                'required' => isset($this->required[$part][$row['source_id']]),
            ];
            $sourceIds[$row['source_id']] = $id;
            $written[$id] = true;
        }

        return $sourceIds;
    }

    /**
     * @param array<mixed>    $forms
     * @param array<int, int> $scriptIds
     * @param array<int, int> $pieceIds
     * @param array<int, array{id: int, name: string, elements: array<int, int>}> $imported Filled with each imported form, by source id.
     * @param array<int, true> $rulesInFile Source ids of the forms whose Integrator rules the file replaces.
     * @return list<array{id: int, name: string, packagePublished: int, previousPublished: ?int}>
     */
    private function importForms(array $forms, string $package, array $scriptIds, array $pieceIds, array &$imported = [], array $rulesInFile = []): array
    {
        $retiredForms = [];
        $db = $this->getDatabase();
        $elementColumns = $this->tableColumns('#__facileforms_elements');
        $formModel = $this->getMVCFactory()->createModel('Form', 'Administrator', ['ignore_request' => true]);

        if (!$formModel instanceof FormModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $versioning = new FormVersioning($db, $formModel);
        $written = [];

        $sourceIds = [];
        foreach ($forms as $form) {
            if (!is_array($form) || !is_array($form['data'] ?? null) || !is_array($form['elements'] ?? null)) {
                throw new InvalidPackageException();
            }
            // The templates and Integrator rules of the file find their form by
            // its source id: one missing or shared would send them to another form.
            if (array_key_exists('source_id', $form) && (!is_int($form['source_id']) || isset($sourceIds[$form['source_id']]))) {
                throw new InvalidPackageException();
            }
            if (is_int($form['source_id'] ?? null)) {
                $sourceIds[$form['source_id']] = true;
            }
        }
        $this->assertImportableNames('#__facileforms_forms', $this->fileNames($forms));

        foreach ($forms as $position => $form) {
            [$data, $backendOnly, $packagePublished] = $this->prepareFormData($form['data'], $package, $scriptIds, $pieceIds);
            $name = $data['name'];
            $id = $this->findId('#__facileforms_forms', $package, $name);
            $this->assertNotWrittenTwice('#__facileforms_forms', $written, $id, $name);
            if ($id > 0 && ($this->statuses['forms'][$position] ?? null) === self::STATUS_IDENTICAL) {
                // Left as it is: its elements keep their ids, which the file's ones point to.
                $written[$id] = true;
                $siteElements = array_keys($this->elementNames($id));
                $elementIds = [];
                foreach (array_values($form['elements']) as $index => $element) {
                    if (is_array($element) && is_int($element['source_id'] ?? null) && isset($siteElements[$index])) {
                        $elementIds[$element['source_id']] = (int) $siteElements[$index];
                    }
                }
                $this->importedFormIds[] = $id;
                $this->importSummary['items']['forms'][] = [
                    'id' => $id, 'name' => $name, 'title' => (string) ($data['title'] ?? ''), 'action' => self::STATUS_IDENTICAL,
                    'elements' => count($form['elements']),
                ];
                if (is_int($form['source_id'] ?? null)) {
                    $imported[$form['source_id']] = ['id' => $id, 'name' => $name, 'elements' => $elementIds];
                    $this->identicalForms[$form['source_id']] = true;
                }
                continue;
            }
            $previousPublished = $id > 0 && $backendOnly ? $this->publishedState($id) : null;
            $action = $id > 0 ? 'replaced' : 'created';
            $replacedElements = null;
            if ($id > 0) {
                // The form replaced keeps its state as its version of origin.
                $versioning->keepOrigin($id, $this->originNote);
                $replacedElements = $this->elementNames($id);
                $data['id'] = $id;
                $object = (object) $data;
                $db->updateObject('#__facileforms_forms', $object, 'id');
                $query = $db->createQuery()->delete($db->quoteName('#__facileforms_elements'))->where($db->quoteName('form') . ' = ' . $id);
                $db->setQuery($query)->execute();
            } else {
                $object = (object) $data;
                $db->insertObject('#__facileforms_forms', $object, 'id');
                $id = (int) $db->insertid();
            }
            $written[$id] = true;

            if ($backendOnly) {
                $retiredForms[] = [
                    'id' => $id,
                    'name' => $name,
                    'packagePublished' => $packagePublished,
                    'previousPublished' => $previousPublished,
                ];
            }

            $elementIds = [];
            foreach ($form['elements'] as $element) {
                if (!is_array($element)) {
                    throw new InvalidPackageException();
                }
                $elementData = $this->only($element, $elementColumns, ['id', 'form']);
                $elementData['form'] = $id;
                $this->remapReferences($elementData, $scriptIds, $pieceIds);
                $elementObject = (object) $elementData;
                $db->insertObject('#__facileforms_elements', $elementObject, 'id');
                if (is_int($element['source_id'] ?? null)) {
                    $elementIds[$element['source_id']] = (int) $db->insertid();
                }
            }
            $this->importedFormIds[] = $id;
            $this->importSummary['items']['forms'][] = [
                'id' => $id, 'name' => $name, 'title' => (string) ($data['title'] ?? ''), 'action' => $action,
                'elements' => count($form['elements']),
            ];
            if (is_int($form['source_id'] ?? null)) {
                $imported[$form['source_id']] = ['id' => $id, 'name' => $name, 'elements' => $elementIds];
            }
            // Rules the file replaces right after need no relinking.
            if ($replacedElements !== null && !(is_int($form['source_id'] ?? null) && isset($rulesInFile[$form['source_id']]))) {
                $this->relinkIntegratorRules($id, $replacedElements);
            }

            $versioning->record($id, $this->importedNote);
        }

        return $retiredForms;
    }

    private function publishedState(int $formId): int
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName('published'))
            ->from($db->quoteName('#__facileforms_forms'))
            ->where($db->quoteName('id') . ' = ' . $formId);

        return (int) $db->setQuery($query)->loadResult();
    }

    private function packageItemModel(string $name): PackageItemModel
    {
        $model = $this->getMVCFactory()->createModel($name, 'Administrator', ['ignore_request' => true]);

        if (!$model instanceof PackageItemModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }

    /**
     * The options of a form of the file as the import writes them.
     *
     * @param array<mixed>    $source
     * @param array<int, int> $scriptIds
     * @param array<int, int> $pieceIds
     * @return array{0: array<string, mixed>&array{name: string}, 1: bool, 2: int} The options, whether the form is
     *         saved as "Backend only", and its publication state in the file.
     */
    private function prepareFormData(array $source, string $package, array $scriptIds, array $pieceIds): array
    {
        // The version number belongs to the form of this site, not to the package.
        $data = $this->only($source, $this->tableColumns('#__facileforms_forms'), ['id', 'created', 'created_by', 'modified', 'modified_by', 'version']);
        $data['package'] = $package;
        // A package can hold a form saved as "Backend only": it stays off the
        // site, unpublished, as the update does for the forms of this site.
        $backendOnly = (int) ($data['runmode'] ?? 0) === 1;
        $packagePublished = (int) ($data['published'] ?? 0);
        if ($backendOnly) {
            $data['published'] = 0;
            $data['runmode'] = 0;
        }
        $this->remapReferences($data, $scriptIds, $pieceIds);
        // Stored as it is found: the template file names are built from the stored name.
        $data['name'] = trim((string) ($data['name'] ?? ''));

        return [$data, $backendOnly, $packagePublished];
    }

    /**
     * @param array<mixed>    $data
     * @param array<int, int> $scriptIds
     * @param array<int, int> $pieceIds
     */
    private function remapReferences(array &$data, array $scriptIds, array $pieceIds): void
    {
        foreach ($data as $column => $value) {
            if (preg_match('/^script[1-3]id$/', $column) === 1 && isset($scriptIds[(int) $value])) {
                $data[$column] = $scriptIds[(int) $value];
            }
            if (preg_match('/^piece[1-4]id$/', $column) === 1 && isset($pieceIds[(int) $value])) {
                $data[$column] = $pieceIds[(int) $value];
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadPackageRows(string $table, string $package): array
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()->select('*')->from($db->quoteName($table))->where($db->quoteName('package') . ' = :package')->order($db->quoteName('id'));
        $query->bind(':package', $package);
        return $db->setQuery($query)->loadAssocList() ?: [];
    }

    /**
     * @param list<int> $formIds
     * @return list<array<string, mixed>>
     */
    private function loadElements(array $formIds): array
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()->select('*')->from($db->quoteName('#__facileforms_elements'))->whereIn($db->quoteName('form'), $formIds)->order([$db->quoteName('form'), $db->quoteName('ordering'), $db->quoteName('id')]);
        return $db->setQuery($query)->loadAssocList() ?: [];
    }

    /**
     * What the last import wrote besides forms, scripts, pieces and menus.
     *
     * @return array{templates: int, rules: int, failed: list<string>, package: string, unlinked: int, shared: list<string>, skipped: list<string>, conflicts: list<string>, items: array<string, list<array<string, mixed>>>}
     */
    public function getImportSummary(): array
    {
        return $this->importSummary;
    }

    /**
     * The optional parts of a package: template files and Integrator rules of its forms.
     *
     * @param array<array<string, mixed>> $forms
     * @return array<string, list<array<string, mixed>>>
     */
    private function formExtras(array $forms, bool $withTemplates, bool $withIntegrator): array
    {
        $extras = [];

        if ($withTemplates) {
            $extras['templates'] = [];
            foreach ($forms as $form) {
                $name = (string) $form['name'];
                // The import could not write them either: say so instead of leaving them out.
                if (!$this->isFileSafeName($name)) {
                    throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_TEMPLATE_UNSAFE_NAME_EXPORT', htmlspecialchars($name, ENT_QUOTES, 'UTF-8')));
                }
                foreach (self::TEMPLATE_SUFFIXES as $folder => $suffixes) {
                    foreach ($suffixes as $suffix) {
                        $path = JPATH_SITE . '/media/breezingforms/' . $folder . '/' . $name . $suffix;
                        if (!is_file($path)) {
                            continue;
                        }
                        $content = file_get_contents($path);
                        if ($content === false) {
                            throw new \RuntimeException(Text::sprintf(
                                'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_TEMPLATE_READ_FAILED',
                                htmlspecialchars($folder . '/' . $name . $suffix, ENT_QUOTES, 'UTF-8')
                            ));
                        }
                        $extras['templates'][] = [
                            'form_source_id' => (int) $form['id'],
                            'folder' => $folder,
                            'suffix' => $suffix,
                            // Templates are PHP files of any encoding: base64 keeps them byte for byte.
                            'content' => base64_encode($content),
                        ];
                    }
                }
            }
        }

        if ($withIntegrator) {
            $extras['integrator'] = [];
            foreach ($forms as $form) {
                $extras['integrator'][] = ['form_source_id' => (int) $form['id'], 'rules' => $this->integratorRules((int) $form['id'])];
            }
        }

        return $extras;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function integratorRules(int $formId): array
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()->select('*')->from($db->quoteName('#__facileforms_integrator_rules'))
            ->where($db->quoteName('form_id') . ' = :formId')->order($db->quoteName('id'))
            ->bind(':formId', $formId, ParameterType::INTEGER);
        $rules = [];
        $ruleRows = $db->setQuery($query)->loadAssocList() ?: [];

        if ($ruleRows === []) {
            return [];
        }

        $elements = $this->elementNames($formId);
        $ruleIds = array_map(static fn (array $rule): int => (int) $rule['id'], $ruleRows);
        // One query per kind of row for all the rules of the form.
        $children = [];
        foreach (self::INTEGRATOR_CHILDREN as $key => [$table]) {
            $query = $db->createQuery()->select('*')->from($db->quoteName($table))
                ->whereIn($db->quoteName('rule_id'), $ruleIds)->order($db->quoteName('id'));
            foreach ($db->setQuery($query)->loadAssocList() ?: [] as $row) {
                $children[$key][(int) $row['rule_id']][] = $row;
            }
        }

        foreach ($ruleRows as $rule) {
            $ruleId = (int) $rule['id'];
            $entry = ['data' => $this->without($rule, ['id', 'form_id'])];
            foreach (self::INTEGRATOR_CHILDREN as $key => [, $elementColumn]) {
                $entry[$key] = [];
                foreach ($children[$key][$ruleId] ?? [] as $row) {
                    // A row naming an element the form no longer has is ignored by
                    // the Integrator, which joins the elements: it is not exported.
                    if ($elementColumn !== null && !isset($elements[(int) $row[$elementColumn]])) {
                        continue;
                    }
                    $child = ['data' => $this->without($row, array_values(array_filter(['id', 'rule_id', $elementColumn])))];
                    if ($elementColumn !== null) {
                        $child['element_source_id'] = (int) $row[$elementColumn];
                    }
                    $entry[$key][] = $child;
                }
            }
            $rules[] = $entry;
        }

        return $rules;
    }

    /**
     * Replaces the Integrator rules of each form the file lists, pointing
     * them to the elements created by this import.
     *
     * @param array<int, array{id: int, name: string, elements: array<int, int>}> $imported
     */
    private function importIntegrator(mixed $entries, array $imported): int
    {
        if (!is_array($entries)) {
            throw new InvalidPackageException();
        }

        $db = $this->getDatabase();
        $ruleColumns = $this->tableColumns('#__facileforms_integrator_rules');
        $childColumns = array_map(fn (array $child): array => $this->tableColumns($child[0]), self::INTEGRATOR_CHILDREN);
        $count = 0;

        $seen = [];
        foreach ($entries as $entry) {
            // A second entry of a form would delete the rules the first one wrote.
            if (
                !is_array($entry) || !is_int($entry['form_source_id'] ?? null)
                || !isset($imported[$entry['form_source_id']]) || !is_array($entry['rules'] ?? null)
                || isset($seen[$entry['form_source_id']])
            ) {
                throw new InvalidPackageException();
            }
            $seen[$entry['form_source_id']] = true;
            // The form and its rules are on the site as they are in the file.
            if (isset($this->identicalForms[$entry['form_source_id']])) {
                continue;
            }
            $form = $imported[$entry['form_source_id']];
            $this->deleteIntegratorRules($form['id']);

            foreach ($entry['rules'] as $rule) {
                if (!is_array($rule) || !is_array($rule['data'] ?? null)) {
                    throw new InvalidPackageException();
                }
                $data = $this->only($rule['data'], $ruleColumns, ['id', 'form_id']);
                $data['form_id'] = $form['id'];
                $object = (object) $data;
                $db->insertObject('#__facileforms_integrator_rules', $object, 'id');
                $ruleId = (int) $db->insertid();
                $this->importSummary['items']['rules'][] = ['id' => $ruleId, 'name' => (string) ($data['name'] ?? ''), 'form' => $form['name']];

                foreach (self::INTEGRATOR_CHILDREN as $key => [$table, $elementColumn]) {
                    foreach ((array) ($rule[$key] ?? []) as $child) {
                        if (!is_array($child) || !is_array($child['data'] ?? null)) {
                            throw new InvalidPackageException();
                        }
                        $childData = $this->only($child['data'], $childColumns[$key], array_values(array_filter(['id', 'rule_id', $elementColumn])));
                        $childData['rule_id'] = $ruleId;
                        if ($elementColumn !== null) {
                            $elementId = $form['elements'][(int) ($child['element_source_id'] ?? 0)] ?? null;
                            if ($elementId === null) {
                                throw new \RuntimeException(Text::sprintf(
                                    'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INTEGRATOR_ELEMENT_MISSING',
                                    htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8')
                                ));
                            }
                            $childData[$elementColumn] = $elementId;
                        }
                        $childObject = (object) $childData;
                        $db->insertObject($table, $childObject, 'id');
                    }
                }
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array<int, string> Name of each element of the form, by id.
     */
    private function elementNames(int $formId): array
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()->select($db->quoteName(['id', 'name']))->from($db->quoteName('#__facileforms_elements'))
            ->where($db->quoteName('form') . ' = :formId')->order($db->quoteName('id'))
            ->bind(':formId', $formId, ParameterType::INTEGER);

        return array_map('strval', $db->setQuery($query)->loadAssocList('id', 'name') ?: []);
    }

    /**
     * The import recreates the elements of a form it replaces: the Integrator
     * rules the form keeps follow each element to the new one of the same name.
     *
     * @param array<int, string> $replaced Name of each element before the import, by id.
     */
    private function relinkIntegratorRules(int $formId, array $replaced): void
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()->select($db->quoteName('id'))->from($db->quoteName('#__facileforms_integrator_rules'))
            ->where($db->quoteName('form_id') . ' = :formId')->bind(':formId', $formId, ParameterType::INTEGER);
        $ruleIds = array_map('intval', $db->setQuery($query)->loadColumn() ?: []);

        if ($ruleIds === [] || $replaced === []) {
            return;
        }

        // Elements of the same name are matched in order: the n-th one before the
        // import is followed to the n-th one after it, both in the order of their ids.
        $newByName = [];
        foreach ($this->elementNames($formId) as $id => $name) {
            $newByName[$name][] = (int) $id;
        }
        $oldToNew = [];
        $seen = [];
        ksort($replaced);
        foreach ($replaced as $oldId => $name) {
            $rank = $seen[$name] = ($seen[$name] ?? -1) + 1;
            if (isset($newByName[$name][$rank])) {
                $oldToNew[(int) $oldId] = $newByName[$name][$rank];
            }
        }

        foreach (['#__facileforms_integrator_items', '#__facileforms_integrator_criteria_form'] as $table) {
            // Only the elements the rules name, not every element of the form.
            $query = $db->createQuery()->select('DISTINCT ' . $db->quoteName('element_id'))->from($db->quoteName($table))
                ->whereIn($db->quoteName('rule_id'), $ruleIds);
            foreach (array_map('intval', $db->setQuery($query)->loadColumn() ?: []) as $oldId) {
                if (!isset($oldToNew[$oldId])) {
                    continue;
                }
                $newId = $oldToNew[$oldId];
                $query = $db->createQuery()->update($db->quoteName($table))
                    ->set($db->quoteName('element_id') . ' = :newId')
                    ->where($db->quoteName('element_id') . ' = :oldId')
                    ->whereIn($db->quoteName('rule_id'), $ruleIds)
                    ->bind(':newId', $newId, ParameterType::INTEGER)
                    ->bind(':oldId', $oldId, ParameterType::INTEGER);
                $db->setQuery($query)->execute();
            }
        }
    }

    /**
     * Integrator rows of the imported forms still naming an element the form
     * no longer has, once the rules of the file are in place.
     *
     * @param list<int> $formIds
     */
    private function countUnlinkedRules(array $formIds): int
    {
        if ($formIds === []) {
            return 0;
        }

        $db = $this->getDatabase();
        $count = 0;

        foreach (['#__facileforms_integrator_items', '#__facileforms_integrator_criteria_form'] as $table) {
            $query = $db->createQuery()->select('COUNT(*)')->from($db->quoteName($table, 'r'))
                ->join('INNER', $db->quoteName('#__facileforms_integrator_rules', 'rule'), 'rule.id = r.rule_id')
                ->join('LEFT', $db->quoteName('#__facileforms_elements', 'e'), 'e.id = r.element_id AND e.form = rule.form_id')
                ->whereIn($db->quoteName('rule.form_id'), $formIds)
                ->where($db->quoteName('e.id') . ' IS NULL');
            $count += (int) $db->setQuery($query)->loadResult();
        }

        return $count;
    }

    private function deleteIntegratorRules(int $formId): void
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()->select($db->quoteName('id'))->from($db->quoteName('#__facileforms_integrator_rules'))
            ->where($db->quoteName('form_id') . ' = :formId')->bind(':formId', $formId, ParameterType::INTEGER);
        $ruleIds = array_map('intval', $db->setQuery($query)->loadColumn() ?: []);

        if ($ruleIds === []) {
            return;
        }

        // As the Integrator screen deletes rules, with all their rows.
        $integrator = $this->getMVCFactory()->createModel('Integrator', 'Administrator', ['ignore_request' => true]);
        if (!$integrator instanceof IntegratorModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }
        $integrator->deleteRules($ruleIds);
    }

    /**
     * Checks the template files of the package and where they go, before anything is written.
     *
     * @param array<int, array{id: int, name: string, elements: array<int, int>}> $imported
     * @return array<string, string> File contents, by path.
     */
    private function templatesToWrite(mixed $entries, array $imported, bool $allowTemplates): array
    {
        if (!is_array($entries)) {
            throw new InvalidPackageException();
        }
        // import() has already left out the templates a user may not write.
        if ($entries !== [] && !$allowTemplates) {
            throw new \LogicException('Templates reached the import without the permission to write them.');
        }

        $files = [];
        $owners = [];
        $shared = [];
        $sharedNames = [];

        foreach ($entries as $entry) {
            $folder = is_array($entry) ? ($entry['folder'] ?? null) : null;
            $suffix = is_array($entry) ? ($entry['suffix'] ?? null) : null;
            $content = is_array($entry) && is_string($entry['content'] ?? null) ? base64_decode($entry['content'], true) : false;

            if (
                !is_string($folder) || !isset(self::TEMPLATE_SUFFIXES[$folder]) || !in_array($suffix, self::TEMPLATE_SUFFIXES[$folder], true)
                || $content === false || !is_int($entry['form_source_id'] ?? null) || !isset($imported[$entry['form_source_id']])
            ) {
                throw new InvalidPackageException();
            }

            $name = $imported[$entry['form_source_id']]['name'];
            if (!$this->isFileSafeName($name)) {
                throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_TEMPLATE_UNSAFE_NAME', htmlspecialchars($name, ENT_QUOTES, 'UTF-8')));
            }
            // The form and its files are on the site as they are in the file.
            if (isset($this->identicalForms[$entry['form_source_id']])) {
                continue;
            }
            // The template files of a form are found by its name alone: another form
            // of the same name, in another package, uses them too and keeps them.
            $formId = $imported[$entry['form_source_id']]['id'];
            $shared[$formId] ??= $this->nameUsedElsewhere($formId, $name);
            if ($shared[$formId]) {
                $sharedNames[$name] = $name;
                continue;
            }
            // The file takes the name of the imported form, which the file may have renamed.
            $path = JPATH_SITE . '/media/breezingforms/' . $folder . '/' . $name . $suffix;
            $files[$path][] = $content;
            $owners[$path][$formId] = $name;
        }
        $this->importSummary['shared'] = array_values($sharedNames);

        // The suffixes overlap: the mailback of form foo and the template of form
        // foo_mailback are the same file. A file another form also uses, on the
        // site or in the package, or that the package gives twice, is not written.
        $writable = [];
        foreach ($files as $path => $contents) {
            $formId = (int) array_key_first($owners[$path]);
            $name = $owners[$path][$formId];
            $folder = basename(dirname($path));
            if (count($contents) > 1 || count($owners[$path]) > 1 || $this->templateUsedByOtherForm($formId, $name, $folder, basename($path))) {
                $this->importSummary['conflicts'][] = substr($path, strlen(JPATH_SITE) + 1);
                continue;
            }
            $writable[$path] = $contents[0];
            $this->templateOwners[$path] = $name;
        }

        return $writable;
    }

    /**
     * Names of the forms a template file can belong to: each name that, with
     * one of the suffixes of the folder, gives the file name.
     *
     * @return list<string>
     */
    public static function templateOwnerNames(string $folder, string $fileName): array
    {
        $names = [];
        foreach (self::TEMPLATE_SUFFIXES[$folder] ?? [] as $suffix) {
            if (strlen($fileName) > strlen($suffix) && str_ends_with($fileName, $suffix)) {
                $names[] = substr($fileName, 0, -strlen($suffix));
            }
        }

        return array_values(array_unique($names));
    }

    /** Whether a form other than the one imported uses this template file, by its own name and a suffix. */
    private function templateUsedByOtherForm(int $formId, string $name, string $folder, string $fileName): bool
    {
        $candidates = array_values(array_diff(self::templateOwnerNames($folder, $fileName), [$name]));
        if ($candidates === []) {
            return false;
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()->select($db->quoteName('name'))->from($db->quoteName('#__facileforms_forms'))
            ->whereIn($db->quoteName('name'), $candidates, ParameterType::STRING)
            ->where($db->quoteName('id') . ' <> :id')->bind(':id', $formId, ParameterType::INTEGER);

        // The column compares without case, the file system does not.
        return array_intersect($candidates, $db->setQuery($query)->loadColumn() ?: []) !== [];
    }

    /** Whether another form of the site has exactly this name, as the template file names use it. */
    private function nameUsedElsewhere(int $formId, string $name): bool
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()->select($db->quoteName('name'))->from($db->quoteName('#__facileforms_forms'))
            ->where($db->quoteName('name') . ' = :name')->where($db->quoteName('id') . ' <> :id')
            ->bind(':name', $name)->bind(':id', $formId, ParameterType::INTEGER);

        // The column compares without case, the file system does not.
        return in_array($name, $db->setQuery($query)->loadColumn() ?: [], true);
    }

    /**
     * @param array<string, string> $files
     */
    private function writeTemplates(array $files): void
    {
        foreach ($files as $path => $content) {
            try {
                $written = File::write($path, $content);
            } catch (\Throwable) {
                $written = false;
            }
            if ($written) {
                $this->importSummary['templates']++;
                $this->importSummary['items']['templates'][] = [
                    'path' => substr($path, strlen(JPATH_SITE) + 1),
                    'form' => $this->templateOwners[$path] ?? '',
                ];
            } else {
                $this->importSummary['failed'][] = substr($path, strlen(JPATH_SITE) + 1);
            }
        }
    }

    /** A form name the template file names can use without leaving their folder. */
    private function isFileSafeName(string $name): bool
    {
        return $name !== '' && $name !== '.' && $name !== '..' && preg_match('#[/\\\\\x00-\x1F]#', $name) !== 1;
    }

    /**
     * Refuses items a package cannot hold: the import finds an item by its
     * package and name, so an item without a name cannot be imported, and of
     * items sharing a name only the last one would be kept.
     *
     * @param array<array<string, mixed>> $rows Rows with their id and name.
     */
    private function assertImportableNames(string $table, array $rows, bool $databaseRows = false): void
    {
        $kind = self::NAMED_KINDS[$table];
        $unnamed = [];
        $byName = [];

        foreach ($rows as $row) {
            $id = '#' . (int) ($row['id'] ?? 0);
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                $unnamed[] = $id;
                continue;
            }

            $byName[mb_strtolower($name)][] = ['name' => $name, 'id' => $id];
        }

        if ($unnamed !== []) {
            throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UNNAMED_' . $kind, implode(', ', $unnamed)));
        }

        if ($databaseRows && count($rows) > 1) {
            // Import trims names in PHP, then compares them using the column's
            // collation. Carry that collation onto the exact PHP-trimmed values.
            $db = $this->getDatabase();
            $query = $db->createQuery();
            $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
            $names = array_map(static fn (array $row): string => trim((string) $row['name']), $rows);
            $idParams = $query->bindArray($ids, ParameterType::INTEGER);
            $nameParams = $query->bindArray($names, ParameterType::STRING);
            $cases = [];

            foreach ($ids as $index => $id) {
                $cases[] = 'WHEN ' . $idParams[$index] . ' THEN ' . $nameParams[$index];
            }

            $nameExpression = 'CONCAT(LEFT(' . $db->quoteName('name') . ', 0), CASE '
                . $db->quoteName('id') . ' ' . implode(' ', $cases) . ' END)';
            $query
                ->select([
                    $db->quoteName('id'),
                    $db->quoteName('name'),
                    'DENSE_RANK() OVER (ORDER BY ' . $nameExpression . ') AS ' . $db->quoteName('name_group'),
                ])
                ->from($db->quoteName($table))
                ->whereIn($db->quoteName('id'), $ids);
            $byName = [];

            foreach ($db->setQuery($query)->loadAssocList() as $row) {
                $byName[(int) $row['name_group']][] = ['name' => trim((string) $row['name']), 'id' => '#' . (int) $row['id']];
            }
        }

        $duplicates = [];

        foreach ($byName as $items) {
            if (count($items) > 1) {
                $duplicates[] = htmlspecialchars($items[0]['name'], ENT_QUOTES, 'UTF-8') . ' (' . implode(', ', array_column($items, 'id')) . ')';
            }
        }

        if ($duplicates !== []) {
            throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DUPLICATE_' . $kind, implode(', ', $duplicates)));
        }
    }

    /**
     * Id and name of each item of a package file, as assertImportableNames() reads them.
     *
     * @param array<array<string, mixed>> $rows
     * @return list<array{id: int, name: string}>
     */
    private function fileNames(array $rows): array
    {
        return array_map(
            static fn (array $row): array => [
                'id' => is_int($row['source_id'] ?? null) ? $row['source_id'] : 0,
                'name' => is_scalar($row['data']['name'] ?? null) ? (string) $row['data']['name'] : '',
            ],
            array_values($rows)
        );
    }

    /**
     * Names the database holds as equal, such as names differing only by an
     * accent, find the item already written by this import: refuse to overwrite it.
     *
     * @param array<int, true> $written
     */
    private function assertNotWrittenTwice(string $table, array $written, int $id, string $name): void
    {
        if ($id > 0 && isset($written[$id])) {
            throw new \RuntimeException(Text::sprintf(
                'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DUPLICATE_' . self::NAMED_KINDS[$table],
                htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
            ));
        }
    }

    private function findId(string $table, string $package, string $name): int
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()->select($db->quoteName('id'))->from($db->quoteName($table))->where($db->quoteName('package') . ' = :package')->where($db->quoteName('name') . ' = :name')->order($db->quoteName('id'));
        $query->bind(':package', $package)->bind(':name', $name);
        return (int) $db->setQuery($query, 0, 1)->loadResult();
    }

    /**
     * @param array<string, mixed> $row
     * @return array{source_id: int, data: array<mixed>}
     */
    private function libraryExportRow(array $row): array
    {
        return ['source_id' => (int) $row['id'], 'data' => $this->only($row, self::LIBRARY_COLUMNS)];
    }

    /**
     * @return list<string>
     */
    private function tableColumns(string $table): array
    {
        return array_keys($this->getDatabase()->getTableColumns($table));
    }

    /**
     * @param array<mixed> $data
     * @param list<string> $allowed
     * @param list<string> $excluded
     * @return array<mixed>
     */
    private function only(array $data, array $allowed, array $excluded = []): array
    {
        return array_diff_key(array_intersect_key($data, array_flip($allowed)), array_flip($excluded));
    }

    /**
     * @param array<mixed> $data
     * @param list<string> $excluded
     * @return array<mixed>
     */
    private function without(array $data, array $excluded): array
    {
        return array_diff_key($data, array_flip($excluded));
    }
}
