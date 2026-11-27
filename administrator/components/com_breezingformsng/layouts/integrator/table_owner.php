<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Icon of the tool a database table belongs to.
 *
 * @var array{owner: string, label?: bool} $displayData
 */

use Joomla\CMS\Language\Text;
use Vcmb\Component\BreezingformsNG\Administrator\Service\TableOwner;

/**
 * @var array{owner: string, label?: bool} $layoutData
 * @psalm-suppress UndefinedGlobalVariable
 */
$layoutData = $displayData;
$icons = [
    TableOwner::JOOMLA => 'icon-joomla',
    TableOwner::BFNG => 'fa-solid fa-rectangle-list',
    TableOwner::CBNG => 'fa-solid fa-cubes',
    TableOwner::EXTENSION => 'fa-solid fa-puzzle-piece',
    TableOwner::FREE => 'fa-solid fa-table',
];
$owner = isset($icons[$layoutData['owner']]) ? $layoutData['owner'] : TableOwner::FREE;
$text = htmlspecialchars(
    Text::_('COM_BREEZINGFORMSNG_INTEGRATOR_TABLE_OWNER_' . strtoupper($owner)),
    ENT_QUOTES,
    'UTF-8'
);
?>
<span class="me-2" title="<?= $text; ?>"><span class="<?= $icons[$owner]; ?> fa-fw" aria-hidden="true"></span>
<?php if (empty($layoutData['label'])) : ?>
    <span class="visually-hidden"><?= $text; ?></span></span>
<?php else : ?>
    <?= $text; ?></span>
<?php endif; ?>
