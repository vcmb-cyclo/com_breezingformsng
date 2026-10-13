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

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

class PieceModel extends PackageItemModel
{
    /**
     * Piece types with the language key of their name.
     */
    public const TYPES = [
        'Untyped' => 'COM_BREEZINGFORMSNG_PIECES_UNTYPED',
        'Before Form' => 'COM_BREEZINGFORMSNG_PIECES_BEFOREFORM',
        'After Form' => 'COM_BREEZINGFORMSNG_PIECES_AFTERFORM',
        'Begin Submit' => 'COM_BREEZINGFORMSNG_PIECES_BEGINSUBMIT',
        'End Submit' => 'COM_BREEZINGFORMSNG_PIECES_ENDSUBMIT',
    ];
}
