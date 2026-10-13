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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Support;

use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordsModel;

final class RecordsNavigationModelDouble extends RecordsModel
{
    public function __construct(private readonly DatabaseInterface $database)
    {
    }

    public function getDatabase(): DatabaseInterface
    {
        return $this->database;
    }
}
