<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering;

use HTML_facileFormsProcessor;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Executes the optional before/after form pieces through the processor. */
final class FormPieceExecutionService
{
    public function __construct(
        private readonly HTML_facileFormsProcessor $processor,
        private readonly mixed $database
    ) {
    }

    public function executeBefore(object $form, string $libraryLabel, string $customLabel, int $formId): bool
    {
        switch ($form->piece1cond) {
            case 1:
                $this->executeLibrary(
                    (int) $form->piece1id,
                    'piece1id',
                    $libraryLabel,
                    (int) $form->piece1id,
                    null
                );
                break;
            case 2:
                echo $this->processor->execPiece($form->piece1code, $customLabel, 'f', $formId, 2);
                break;
            default:
                break;
        }

        return $this->processor->bury();
    }

    public function executeAfter(object $form, string $libraryLabel, string $customLabel, int $formId): bool
    {
        switch ($form->piece2cond) {
            case 1:
                $this->executeLibrary(
                    (int) $form->piece2id,
                    'piece2id',
                    $libraryLabel,
                    (int) $form->piece2id,
                    null
                );
                break;
            case 2:
                echo $this->processor->execPiece($form->piece2code, $customLabel, 'f', $formId, 2);
                break;
            default:
                break;
        }

        return $this->processor->bury();
    }

    private function executeLibrary(
        int $pieceId,
        string $parameter,
        string $label,
        int $executionId,
        ?int $pane
    ): void {
        /** @var DatabaseInterface $database */
        $database = $this->database;
        $query = $database->createQuery()
            ->select(['name', 'code'])
            ->from($database->quoteName('#__facileforms_pieces'))
            ->where($database->quoteName('id') . ' = :' . $parameter)
            ->where($database->quoteName('published') . ' = 1')
            ->bind(':' . $parameter, $pieceId, ParameterType::INTEGER);
        $database->setQuery($query);
        $rows = $database->loadObjectList();

        if (count($rows)) {
            echo $this->processor->execPiece($rows[0]->code, $label . ' ' . $rows[0]->name, 'p', $executionId, $pane);

            return;
        }

        // The form keeps the piece by id: a deleted, reimported or unpublished
        // piece is skipped, which the site tells only in debug mode.
        if ($this->processor->app->getConfig()->get('debug', false)) {
            $this->processor->app->enqueueMessage(Text::sprintf('COM_BREEZINGFORMSNG_PROCESS_PIECE_NOT_FOUND', $label, $pieceId), 'error');
        }
    }
}
