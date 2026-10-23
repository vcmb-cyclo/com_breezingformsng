<?php
/**
 * BreezingForms NG - A Joomla Forms Application
 * 
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/**
 * Not a Joomla-version compatibility shim: this is the BreezingForms legacy
 * API surface (pre-Joomla-6 BFFactory/BFRequest/... classes) kept so that
 * user-authored PHP stored in the database (Pieces, "Before Form" code) keeps
 * running unmodified. It is intentionally exempt from AGENTS.md's "no
 * fallbacks/polyfills/compat workarounds" rule, which targets Joomla-version
 * shims in first-party source, not this database-code compatibility layer.
 *
 * Reintroduced 2026-07-12 (production incident): a repo-wide grep found no
 * internal caller and this was removed, but production Pieces and a form's
 * "Before Form" code — PHP stored in the database, evaluated at runtime, not
 * part of this source tree — call BFFactory::getDbo()/getDBO() directly:
 * ff_databaseToSelect, ff_query, ff_select, ff_selectValue (admin Pieces) and
 * the hash_password form's piece1code. Do not remove this file again on the
 * strength of a source-tree grep alone; check facileforms_pieces.code and
 * facileforms_forms.piece*code in the live database first.
 */
use Joomla\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;

class BFFactory
{

    private static ?BFDbo $dbo = null;

    public static function getDbo(): BFDbo
    {
        if (self::$dbo === null) {
            self::$dbo = new BFDbo();
        }

        return self::$dbo;
    }

}

class BFFile extends File
{

    /** @return string|false */
    public static function read(string $file)
    {
        return file_get_contents($file);
    }
}

class BFDbo
{

    private int $errNo = 0;
    private string $errMsg = '';
    private DatabaseInterface $dbo;
    private bool $last_query = true;
    private string|QueryInterface $last_failed_query = '';

    function __construct()
    {
        $this->dbo = Factory::getContainer()->get(DatabaseInterface::class);
    }

    /**
     * @param string|QueryInterface $query
     * @param int $offset
     * @param int $limit
     */
    public function setQuery($query, $offset = 0, $limit = 0): void
    {

        try {
            $this->dbo->setQuery($query, $offset, $limit);
        } catch (Exception $e) {
            $this->last_query = false;
            $this->last_failed_query = $query;
            $this->errNo = $e->getCode();
            $this->errMsg = $e->getMessage();
        }

    }

    /** @return array<array-key, object>|null */
    public function loadObjectList()
    {
        if (!$this->last_query)
            return array();

        $this->errNo = 0;
        $this->errMsg = '';

        try {
            return $this->dbo->loadObjectList();
        } catch (Throwable $e) {
            $this->errNo = $e->getCode();
            $this->errMsg = $e->getMessage();
        }

        return array();
    }

    /**
     * @param class-string $class
     * @return object|null
     */
    public function loadObject($class = 'stdClass')
    {
        if (!$this->last_query)
            return null;

        $this->errNo = 0;
        $this->errMsg = '';

        try {
            return $this->dbo->loadObject($class);

        } catch (Throwable $e) {
            $this->errNo = $e->getCode();
            $this->errMsg = $e->getMessage();
        }

        return null;
    }

    /**
     * @param int $offset
     * @return list<mixed>|null
     */
    public function loadColumn($offset = 0)
    {
        if (!$this->last_query)
            return null;

        $this->errNo = 0;
        $this->errMsg = '';

        try {

            return $this->dbo->loadColumn($offset);

        } catch (Throwable $e) {
            $this->errNo = $e->getCode();
            $this->errMsg = $e->getMessage();
        }

        return null;
    }

    /**
     * @param string|null $key
     * @param string|null $column
     * @return array<array-key, mixed>|null
     */
    public function loadAssocList($key = null, $column = null)
    {
        if (!$this->last_query)
            return array();

        $this->errNo = 0;
        $this->errMsg = '';

        try {

            return $this->dbo->loadAssocList($key, $column);

        } catch (Throwable $e) {
            $this->errNo = $e->getCode();
            $this->errMsg = $e->getMessage();
        }

        return array();
    }

    /** @return array<string, mixed>|null */
    public function loadAssoc()
    {
        if (!$this->last_query)
            return null;

        $this->errNo = 0;
        $this->errMsg = '';

        try {

            return $this->dbo->loadAssoc();

        } catch (Throwable $e) {
            $this->errNo = $e->getCode();
            $this->errMsg = $e->getMessage();
        }

        return null;
    }

    public function query(): bool
    {

        return $this->execute();
    }

    public function execute(): bool
    {
        $this->errNo = 0;
        $this->errMsg = '';

        try {

            return $this->dbo->execute();

        } catch (Throwable $e) {
            $this->errNo = $e->getCode();
            $this->errMsg = $e->getMessage();
        }

        return false;
    }

    /**
     * @param string $table
     * @param object $object
     * @param string $key
     * @param bool $nulls
     */
    public function updateObject($table, &$object, $key, $nulls = false): bool
    {
        $this->errNo = 0;
        $this->errMsg = '';

        try {

            return $this->dbo->updateObject($table, $object, $key, $nulls);

        } catch (Throwable $e) {
            $this->errNo = $e->getCode();
            $this->errMsg = $e->getMessage();
        }

        return false;
    }

    /**
     * @param string $table
     * @param object $object
     * @param string|null $key
     */
    public function insertObject($table, &$object, $key = null): bool
    {
        $this->errNo = 0;
        $this->errMsg = '';

        try {

            return $this->dbo->insertObject($table, $object, $key);

        } catch (Throwable $e) {
            $this->errNo = $e->getCode();
            $this->errMsg = $e->getMessage();
        }

        return false;
    }

    /**
     * @param string|array<string> $query
     * @param bool $esc
     * @return string|array<array-key, string>
     */
    public function quote($query, $esc = true)
    {
        return $this->dbo->quote($query, $esc);
    }

    /**
     * @param bool $new
     * @return QueryInterface|string
     */
    public function getQuery($new = false)
    {
        if ($new) {
            return $this->createQuery();
        }

        if (!$this->last_query)
            return $this->last_failed_query;

        return $this->dbo->getQuery();
    }

    public function createQuery(): QueryInterface
    {
        return $this->dbo->createQuery();
    }

    public function getPrefix(): string
    {
        return $this->dbo->getPrefix();
    }

    public function getNullDate(): string
    {
        return $this->dbo->getNullDate();
    }

    public function getNumRows(): int
    {
        if (!$this->last_query)
            return 0;
        return $this->dbo->getNumRows();
    }

    public function getCount(): int
    {
        if (!$this->last_query)
            return 0;
        return $this->dbo->getCount();
    }

    /** @return mixed */
    public function getConnection()
    {
        return $this->dbo->getConnection();
    }

    /** @return int|array{} */
    public function getAffectedRows()
    {
        if (!$this->last_query)
            return array();
        return $this->dbo->getAffectedRows();
    }

    /**
     * @param string $table
     * @param bool $typeOnly
     * @return array<string, mixed>
     */
    public function getTableColumns($table, $typeOnly = true)
    {
        $this->errNo = 0;
        $this->errMsg = '';

        try {
            return $this->dbo->getTableColumns($table, $typeOnly);
        } catch (Throwable $e) {
            $this->errNo = $e->getCode();
            $this->errMsg = $e->getMessage();
        }

        return array();
    }

    /** @return list<string> */
    public function getTableList()
    {
        $this->errNo = 0;
        $this->errMsg = '';

        try {
            return $this->dbo->getTableList();
        } catch (Throwable $e) {
            $this->errNo = $e->getCode();
            $this->errMsg = $e->getMessage();
        }

        return array();
    }

    /** @return mixed */
    public function loadResult()
    {
        if (!$this->last_query)
            return null;

        $this->errNo = 0;
        $this->errMsg = '';

        try {
            return $this->dbo->loadResult();
        } catch (Throwable $e) {
            $this->errNo = $e->getCode();
            $this->errMsg = $e->getMessage();
        }

        return null;
    }

    public function getErrorNum(): int
    {
        return $this->errNo;
    }

    public function getErrorMsg(): string
    {
        return $this->errMsg;
    }

    public function stderr(): string
    {

        return $this->errMsg;
    }

    /** @return mixed */
    public function insertid()
    {

        if (!$this->last_query)
            return 0;
        return $this->dbo->insertid();
    }
}

