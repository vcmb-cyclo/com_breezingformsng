<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Scripting;

\defined('_JEXEC') or die;

use Closure;

/**
 * Executes trusted PHP stored by Super Users while preserving the historical
 * processor scope exposed as $this.
 */
final class StoredPhpExecutor
{
    /**
     * Executes a trusted snippet in the scope of the supplied runtime object.
     * A variable passed by reference stays one, so the snippet can change it.
     *
     * @param array<string, mixed> $variables
     */
    public function execute(object $scope, string $code, array $variables = []): mixed
    {
        $executor = function () use ($code, $variables) {
            extract($variables, EXTR_SKIP | EXTR_REFS);

            return eval($code);
        };

        return $executor->call($scope);
    }

    public function executePiece(
        object $processor,
        string $code,
        mixed $name,
        mixed $type,
        mixed $id,
        mixed $pane
    ): mixed {
        $executor = function () use ($code, $name, $type, $id, $pane) {
            $ret = '';

            return eval($code);
        };

        return $executor->call($processor);
    }

    public function executeQueryValue(
        object $processor,
        string $code,
        object &$elem,
        object &$row,
        object &$coldef,
        mixed $value
    ): mixed {
        $executor = function () use ($code, &$elem, &$row, &$coldef, $value) {
            return eval($code);
        };

        return $executor->call($processor);
    }

    /**
     * @param array<int, mixed> $valrows
     * @param array<int, object> $coldefs
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
        $executor = function () use ($code, &$elem, &$valrows, &$coldefs): array {
            $ret = null;
            $rows = [];
            eval($code);

            return $rows;
        };

        return $executor->call($processor);
    }
}
