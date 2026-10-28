<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Vcmb\Component\BreezingformsNG\Administrator\Service\SqlDate;

/**
 * Imports a FacileForms XML package (script and piece libraries).
 *
 * Packages containing forms or component menus are rejected: those
 * legacy package types are not produced anymore and their import
 * relied on the removed legacy runtime.
 */
class ImportModel extends BaseDatabaseModel
{
    public bool $reinstallOnlyIfChanged = false;

    /** @var int[] Ids of scripts inserted or updated */
    public array $scripts = [];

    /** @var int[] Ids of pieces inserted or updated */
    public array $pieces = [];

    /** @var string[] */
    public array $createdScripts = [];

    /** @var string[] */
    public array $updatedScripts = [];

    /** @var string[] */
    public array $createdPieces = [];

    /** @var string[] */
    public array $updatedPieces = [];

    /** @var string[] */
    public array $warnings = [];

    private DatabaseInterface $db;

    /**
     * Import a package file. As the package import of the Packages screen, an
     * item replaced keeps its state as its version of origin, and the state
     * written is stored as a version.
     *
     * @throws \RuntimeException on any parse or storage error (the transaction is rolled back)
     */
    public function import(string $filename, PackageItemModel $scriptItems, PackageItemModel $pieceItems): void
    {
        $this->scripts = $this->pieces = [];
        $this->createdScripts = $this->updatedScripts = [];
        $this->createdPieces = $this->updatedPieces = [];
        $this->warnings = [];

        $this->db = $this->getDatabase();

        if (!is_file($filename)) {
            throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_IMPORT_FILE_NOT_FOUND', $filename));
        }

        $useInternalErrors = libxml_use_internal_errors(true);
        $xml = simplexml_load_file($filename, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        $xmlErrors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        if ($xml === false) {
            $detail = $xmlErrors !== [] ? trim($xmlErrors[0]->message) . ' (line ' . $xmlErrors[0]->line . ')' : '';

            throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_IMPORT_INVALID_XML', $detail));
        }

        if ($xml->getName() !== 'FacileFormsPackage') {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_IMPORT_INVALID_ROOT'));
        }

        if (isset($xml->form) || isset($xml->compmenu)) {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_IMPORT_FORMS_UNSUPPORTED'));
        }

        $this->db->transactionStart();

        try {
            foreach ($xml->script as $node) {
                $this->importLibraryItem($node, '#__facileforms_scripts', $scriptItems, $this->scripts, $this->createdScripts, $this->updatedScripts);
            }

            foreach ($xml->piece as $node) {
                $this->importLibraryItem($node, '#__facileforms_pieces', $pieceItems, $this->pieces, $this->createdPieces, $this->updatedPieces);
            }

            $this->savePackageMetadata($xml);

            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            $this->db->transactionRollback();

            throw new \RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param int[] $touched
     * @param array<mixed> $created
     * @param array<mixed> $updated
     */
    private function importLibraryItem(
        \SimpleXMLElement $node,
        string $table,
        PackageItemModel $items,
        array &$touched,
        array &$created,
        array &$updated
    ): void
    {
        $unknown = Text::_('COM_BREEZINGFORMSNG_INSTALLER_UNKNOWN');

        $item = [
            'published' => $this->intValue($node->published, 1),
            'package' => $this->textValue($node->package),
            'name' => $this->textValue($node->name, $unknown),
            'title' => $this->textValue($node->title, $unknown),
            'description' => $this->textValue($node->description),
            'type' => $this->textValue($node->type, 'Untyped'),
            'code' => $this->textValue($node->code),
        ];

        if (isset($node->unit_tests)) {
            $item['unit_tests'] = $this->textValue($node->unit_tests);
        }

        $query = $this->db->createQuery()
            ->select('*')
            ->from($this->db->quoteName($table))
            ->where($this->db->quoteName('name') . ' = :name')
            ->where($this->db->quoteName('package') . ' = :package')
            ->order($this->db->quoteName('id'))
            ->bind(':name', $item['name'])
            ->bind(':package', $item['package']);
        $existing = $this->db->setQuery($query, 0, 1)->loadAssoc();

        if ($existing !== null && $this->reinstallOnlyIfChanged && $this->isUnchanged($item, $existing)) {
            return;
        }

        $now = (new \Joomla\CMS\Date\Date())->toSql();
        $userName = (string) $this->getCurrentUser()->username;

        if ($existing !== null) {
            $item['modified'] = $now;
            $item['modified_by'] = $userName;

            $items->keepOrigin((int) $existing['id']);

            $row = (object) ($item + ['id' => (int) $existing['id']]);
            $this->db->updateObject($table, $row, 'id');
            $items->recordVersion((int) $existing['id']);

            $touched[] = (int) $existing['id'];

            if (!\in_array($item['name'], $updated, true)) {
                $updated[] = $item['name'];
            }

            return;
        }

        $item['created'] = $item['modified'] = $now;
        $item['created_by'] = $item['modified_by'] = $userName;

        $incomingId = (int) ($node['id'] ?? 0);

        if ($incomingId > 0 && !$this->idExists($table, $incomingId)) {
            $item['id'] = $incomingId;
        }

        $row = (object) $item;
        $this->db->insertObject($table, $row, 'id');
        $rowId = $row instanceof \stdClass ? (int) $row->id : 0;
        $items->recordVersion($rowId);

        $touched[] = $rowId;

        if (!\in_array($item['name'], $created, true)) {
            $created[] = $item['name'];
        }
    }

    /**
     * @param array<string, int|string> $item
     * @param array<string, mixed> $existing
     */
    private function isUnchanged(array $item, array $existing): bool
    {
        foreach ($item as $key => $value) {
            if ($key === 'published') {
                if ((int) $existing['published'] !== (int) $value) {
                    return false;
                }

                continue;
            }

            if ((string) ($existing[$key] ?? '') !== (string) $value) {
                return false;
            }
        }

        return true;
    }

    private function idExists(string $table, int $id): bool
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName($table))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        return $this->db->setQuery($query)->loadResult() !== null;
    }

    private function savePackageMetadata(\SimpleXMLElement $xml): void
    {
        $id = trim((string) ($xml['id'] ?? ''));

        if ($id === '') {
            return;
        }

        $package = [
            'name' => $this->textValue($xml->name),
            'title' => $this->textValue($xml->title),
            'version' => $this->textValue($xml->version),
            'created' => SqlDate::normalise($this->textValue($xml->creationDate)),
            'author' => $this->textValue($xml->author),
            'email' => $this->textValue($xml->authorEmail),
            'url' => $this->textValue($xml->authorUrl),
            'description' => $this->textValue($xml->description),
            'copyright' => $this->textValue($xml->copyright),
        ];

        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__facileforms_packages'))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id);
        $exists = $this->db->setQuery($query)->loadResult() !== null;

        $now = (new \Joomla\CMS\Date\Date())->toSql();
        $userName = (string) $this->getCurrentUser()->username;
        $package['modified'] = $now;
        $package['modified_by'] = $userName;

        if ($exists) {
            // An existing package keeps its creation date and author.
            unset($package['created']);
            $row = (object) ($package + ['id' => $id]);
            $this->db->updateObject('#__facileforms_packages', $row, 'id');
        } else {
            $package['created'] ??= $now;
            $package['created_by'] = $userName;
            $row = (object) ($package + ['id' => $id]);
            $this->db->insertObject('#__facileforms_packages', $row);
        }
    }

    /**
     * Package values are stored with C-style escapes (\x2C, \n, ...) by the legacy exporter.
     */
    private function textValue(?\SimpleXMLElement $node, string $default = ''): string
    {
        if ($node === null || !isset($node[0])) {
            return $default;
        }

        return stripcslashes(trim((string) $node));
    }

    private function intValue(?\SimpleXMLElement $node, int $default = 0): int
    {
        return (int) $this->textValue($node, (string) $default);
    }
}
