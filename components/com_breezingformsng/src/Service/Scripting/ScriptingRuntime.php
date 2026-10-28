<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Scripting;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\JavascriptCompressor;

final class ScriptingRuntime
{
    private readonly Repository $repository;
    private readonly JavascriptCompressor $compressor;
    private readonly StoredPhpExecutor $phpExecutor;

    public function __construct(DatabaseInterface $database)
    {
        $this->repository = new Repository($database);
        $this->compressor = new JavascriptCompressor();
        $this->phpExecutor = new StoredPhpExecutor();
    }

    public function findPieceById(int $id): ?StoredCode
    {
        return $this->repository->findPublishedPieceById($id);
    }

    public function findPieceByName(string $name): ?StoredCode
    {
        return $this->repository->findPublishedPieceByName($name);
    }

    public function findScriptById(int $id): ?StoredCode
    {
        return $this->repository->findPublishedScriptById($id);
    }

    /**
     * @return list<StoredCode>
     */
    public function publishedScripts(): array
    {
        return $this->repository->getPublishedScripts();
    }

    public function compress(string $javascript, int $breakAfter, string $lineEnding): string
    {
        return $this->compressor->compress($javascript, $breakAfter, $lineEnding);
    }

    public function executePiece(
        object $processor,
        string $code,
        mixed $name,
        mixed $type,
        mixed $id,
        mixed $pane
    ): mixed {
        return $this->phpExecutor->executePiece($processor, $code, $name, $type, $id, $pane);
    }

    /**
     * @param \stdClass $elem
     * @param \Vcmb\Component\BreezingformsNG\Site\Table\QueryColumn $coldef
     */
    public function executeQueryValue(
        object $processor,
        string $code,
        object &$elem,
        object &$row,
        object &$coldef,
        mixed $value
    ): mixed {
        return $this->phpExecutor->executeQueryValue($processor, $code, $elem, $row, $coldef, $value);
    }

    /**
     * @param \stdClass $elem
     * @param array<int, mixed> $valrows
     * @param list<\Vcmb\Component\BreezingformsNG\Site\Table\QueryColumn> $coldefs
     *
     * @return array<int, object>
     */
    public function executeQuery(
        object $processor,
        string $code,
        object &$elem,
        array &$valrows,
        array &$coldefs
    ): array {
        return $this->phpExecutor->executeQuery($processor, $code, $elem, $valrows, $coldefs);
    }
}
