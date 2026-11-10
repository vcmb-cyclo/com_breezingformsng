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

    /** @var array{templates: int, rules: int, failed: list<string>, package: string, unlinked: int} */
    private array $importSummary = ['templates' => 0, 'rules' => 0, 'failed' => [], 'package' => '', 'unlinked' => 0];

    /** Notes of the form versions written by the running import. */
    private string $importedNote = '';

    private string $originNote = '';

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

        $db = $this->getDatabase();
        $forms = $this->loadPackageRows('#__facileforms_forms', $package);
        $scripts = $this->loadPackageRows('#__facileforms_scripts', $package);
        $pieces = $this->loadPackageRows('#__facileforms_pieces', $package);
        $menus = $this->loadPackageRows('#__facileforms_compmenus', $package);

        if ($forms === [] && $scripts === [] && $pieces === [] && $menus === []) {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_INSTALLER_SELECTAPKG'));
        }

        $this->assertImportableNames('#__facileforms_forms', $forms);
        $this->assertImportableNames('#__facileforms_scripts', $scripts);
        $this->assertImportableNames('#__facileforms_pieces', $pieces);

        $formIds = array_map(static fn(array $form): int => (int) $form['id'], $forms);
        $elements = $formIds === [] ? [] : $this->loadElements($formIds);
        $elementsByForm = [];

        foreach ($elements as $element) {
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
            'menus' => array_map(fn(array $row): array => $this->menuExportRow($row), $this->includeMenuAncestors($menus)),
            'forms' => $payloadForms,
            'metadata' => $this->metadataForExport($package, $metadata),
        ];
        $payload += $this->formExtras($forms, $withTemplates, $withIntegrator);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return $json . "\n";
    }

    /**
     * @return list<array{id: int, name: string, packagePublished: int, previousPublished: ?int}>
     *         The forms saved as "Backend only" that the import left unpublished.
     */
    public function import(string $filename, bool $allowTemplates = false, string $sourceName = ''): array
    {
        $this->importSummary = ['templates' => 0, 'rules' => 0, 'failed' => [], 'package' => '', 'unlinked' => 0];
        // The uploaded file is a temporary one: its name of origin goes in the version notes.
        $sourceName = $sourceName !== '' ? $sourceName : basename($filename);

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

        $db = $this->getDatabase();
        $package = $payload['package'];
        $this->importedNote = Text::sprintf('COM_BREEZINGFORMSNG_FORM_VERSION_NOTE_IMPORTED', $package, $sourceName);
        $this->originNote = Text::sprintf('COM_BREEZINGFORMSNG_FORM_VERSION_NOTE_BEFORE_IMPORT', $package);
        $db->transactionStart();

        try {
            $scriptIds = $this->importLibraries('#__facileforms_scripts', (array) ($payload['scripts'] ?? []), $package, $this->packageItemModel('Script'));
            $pieceIds = $this->importLibraries('#__facileforms_pieces', (array) ($payload['pieces'] ?? []), $package, $this->packageItemModel('Piece'));
            $imported = [];
            $retiredForms = $this->importForms((array) ($payload['forms'] ?? []), $package, $scriptIds, $pieceIds, $imported);
            $this->importSummary['rules'] = $this->importIntegrator($payload['integrator'] ?? [], $imported);
            $this->importSummary['unlinked'] = $this->countUnlinkedRules(array_values(array_column($imported, 'id')));
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

        $forms = $this->loadRowsByIds("#__facileforms_forms", $formIds);
        $scripts = $this->loadRowsByIds("#__facileforms_scripts", $scriptIds);
        $pieces = $this->loadRowsByIds("#__facileforms_pieces", $pieceIds);
        $menus = $this->includeMenuAncestors($this->loadRowsByIds('#__facileforms_compmenus', $menuIds));

        if ($forms === [] && $scripts === [] && $pieces === [] && $menus === []) {
            throw new \RuntimeException(Text::_("COM_BREEZINGFORMSNG_INSTALLER_SELECTAPKG"));
        }

        $this->assertImportableNames("#__facileforms_forms", $forms);
        $this->assertImportableNames("#__facileforms_scripts", $scripts);
        $this->assertImportableNames("#__facileforms_pieces", $pieces);

        $formIds = array_map(static fn(array $form): int => (int) $form["id"], $forms);
        $elementsByForm = [];

        foreach ($formIds === [] ? [] : $this->loadElements($formIds) as $element) {
            $elementsByForm[(int) $element["form"]][] = ["source_id" => (int) $element["id"]] + $this->without($element, ["id", "form"]);
        }

        $payloadForms = [];
        foreach ($forms as $form) {
            $sourceId = (int) $form["id"];
            $payloadForms[] = [
                "source_id" => $sourceId,
                "data" => $this->without($form, ["id", "created", "created_by", "modified", "modified_by"]),
                "elements" => $elementsByForm[$sourceId] ?? [],
            ];
        }

        $payload = [
            "format" => self::FORMAT,
            "version" => self::VERSION,
            "package" => $package,
            "exported_at" => (new \Joomla\CMS\Date\Date())->toSql(),
            "scripts" => array_map(fn(array $row): array => $this->libraryExportRow($row), $scripts),
            "pieces" => array_map(fn(array $row): array => $this->libraryExportRow($row), $pieces),
            "menus" => array_map(fn(array $row): array => $this->menuExportRow($row), $menus),
            "forms" => $payloadForms,
            "metadata" => $this->metadataForExport($package, $metadata),
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
                throw new InvalidPackageException();
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
                if (isset($seen[$parent])) {
                    throw new InvalidPackageException();
                }
                $seen[$parent] = true;
                if (!isset($byId[$parent])) {
                    $ancestor = $this->loadRowsByIds('#__facileforms_compmenus', [$parent]);
                    if ($ancestor === []) {
                        throw new InvalidPackageException();
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

        foreach ($rows as $row) {
            if (!is_array($row) || !is_int($row['source_id'] ?? null)
                || $row['source_id'] <= 0 || !is_array($row['data'] ?? null)
                || isset($pending[$row['source_id']])) {
                throw new InvalidPackageException();
            }
            $pending[$row['source_id']] = $row['data'];
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
                if ($id > 0) {
                    $data['id'] = $id;
                    $object = (object) $data;
                    $db->updateObject('#__facileforms_compmenus', $object, 'id');
                } else {
                    $query = $db->createQuery()
                        ->select('COALESCE(MAX(' . $db->quoteName('id') . '), 0) + 1')
                        ->from($db->quoteName('#__facileforms_compmenus'));
                    $data['id'] = (int) $db->setQuery($query)->loadResult();
                    $object = (object) $data;
                    $db->insertObject('#__facileforms_compmenus', $object);
                    $id = $data['id'];
                }

                $importedIds[$sourceId] = $id;
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

        foreach ($rows as $row) {
            $data = $this->only($row['data'], self::LIBRARY_COLUMNS);
            $data['package'] = $package;
            $name = trim((string) ($data['name'] ?? ''));
            $id = $this->findId($table, $package, $name);
            $this->assertNotWrittenTwice($table, $written, $id, $name);
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
     * @return list<array{id: int, name: string, packagePublished: int, previousPublished: ?int}>
     */
    private function importForms(array $forms, string $package, array $scriptIds, array $pieceIds, array &$imported = []): array
    {
        $retiredForms = [];
        $db = $this->getDatabase();
        $formColumns = $this->tableColumns('#__facileforms_forms');
        $elementColumns = $this->tableColumns('#__facileforms_elements');
        $formModel = $this->getMVCFactory()->createModel('Form', 'Administrator', ['ignore_request' => true]);

        if (!$formModel instanceof FormModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $versioning = new FormVersioning($db, $formModel);
        $written = [];

        foreach ($forms as $form) {
            if (!is_array($form) || !is_array($form['data'] ?? null) || !is_array($form['elements'] ?? null)) {
                throw new InvalidPackageException();
            }
        }
        $this->assertImportableNames('#__facileforms_forms', $this->fileNames($forms));

        foreach ($forms as $form) {
            // The version number belongs to the form of this site, not to the package.
            $data = $this->only($form['data'], $formColumns, ['id', 'created', 'created_by', 'modified', 'modified_by', 'version']);
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
            $name = trim((string) ($data['name'] ?? ''));
            $id = $this->findId('#__facileforms_forms', $package, $name);
            $this->assertNotWrittenTwice('#__facileforms_forms', $written, $id, $name);
            $previousPublished = $id > 0 && $backendOnly ? $this->publishedState($id) : null;
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
            $imported[(int) ($form['source_id'] ?? 0)] = ['id' => $id, 'name' => $name, 'elements' => $elementIds];
            if ($replacedElements !== null) {
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
     * @return array{templates: int, rules: int, failed: list<string>, package: string, unlinked: int}
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
                if (!$this->isFileSafeName($name)) {
                    continue;
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
        $elements = $this->elementNames($formId);

        foreach ($db->setQuery($query)->loadAssocList() ?: [] as $rule) {
            $ruleId = (int) $rule['id'];
            $entry = ['data' => $this->without($rule, ['id', 'form_id'])];
            foreach (self::INTEGRATOR_CHILDREN as $key => [$table, $elementColumn]) {
                $query = $db->createQuery()->select('*')->from($db->quoteName($table))
                    ->where($db->quoteName('rule_id') . ' = :ruleId')->order($db->quoteName('id'))
                    ->bind(':ruleId', $ruleId, ParameterType::INTEGER);
                $entry[$key] = [];
                foreach ($db->setQuery($query)->loadAssocList() ?: [] as $row) {
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

        foreach ($entries as $entry) {
            if (
                !is_array($entry) || !is_int($entry['form_source_id'] ?? null)
                || !isset($imported[$entry['form_source_id']]) || !is_array($entry['rules'] ?? null)
            ) {
                throw new InvalidPackageException();
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

        // The first element of a name, as the lists of the form show it.
        $newIds = array_flip(array_reverse($this->elementNames($formId), true));

        foreach ($replaced as $oldId => $name) {
            if (!isset($newIds[$name])) {
                continue;
            }
            $newId = (int) $newIds[$name];
            foreach (['#__facileforms_integrator_items', '#__facileforms_integrator_criteria_form'] as $table) {
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

        foreach (self::INTEGRATOR_CHILDREN as [$table]) {
            $db->setQuery($db->createQuery()->delete($db->quoteName($table))->whereIn($db->quoteName('rule_id'), $ruleIds))->execute();
        }
        $db->setQuery($db->createQuery()->delete($db->quoteName('#__facileforms_integrator_rules'))->whereIn($db->quoteName('id'), $ruleIds))->execute();
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
        if ($entries !== [] && !$allowTemplates) {
            throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_TEMPLATES_NOT_ALLOWED', Text::_('JACTION_ADMIN')));
        }

        $files = [];

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
            // The file takes the name of the imported form, which the file may have renamed.
            $files[JPATH_SITE . '/media/breezingforms/' . $folder . '/' . $name . $suffix] = $content;
        }

        return $files;
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
    private function assertImportableNames(string $table, array $rows): void
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

            // The database compares names without case or trailing spaces.
            $byName[mb_strtolower($name)][] = ['name' => $name, 'id' => $id];
        }

        if ($unnamed !== []) {
            throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UNNAMED_' . $kind, implode(', ', $unnamed)));
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
            static fn (array $row): array => ['id' => (int) $row['source_id'], 'name' => is_scalar($row['data']['name'] ?? null) ? (string) $row['data']['name'] : ''],
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
