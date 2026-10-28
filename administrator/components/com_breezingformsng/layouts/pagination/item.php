<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * Joomla pagination over the list of the edited item, one item per page, at
 * the end of the toolbar. The admin layout of Joomla submits adminForm (here,
 * the item being edited), so the pages are rendered as links, with the
 * markup of joomla.pagination.link.
 *
 * @var array{pagination: \Joomla\CMS\Pagination\Pagination, position: string} $displayData
 */

$pagination = $displayData['pagination'];
$pages = $pagination->getPaginationPages();
// The toolbar has room for fewer page numbers than the 10 of Joomla: the
// current item and the one on each side.
$first = max(1, min($pagination->pagesCurrent - 1, $pagination->pagesTotal - 2));
$pages['pages'] = array_filter(
    $pages['pages'],
    static fn (int $number): bool => $number >= $first && $number < $first + 3,
    ARRAY_FILTER_USE_KEY
);
$pageLink = static function (array $page, string $icon = '', string $aria = ''): string {
    $item = $page['data'];
    $display = $icon !== '' ? '<span class="' . $icon . '" aria-hidden="true"></span>' : $item->text;
    $aria = $aria !== '' ? Text::_($aria) : Text::sprintf('JLIB_HTML_GOTO_PAGE', strtolower($item->text));

    if ($page['active']) {
        return sprintf('<li class="page-item"><a aria-label="%s" href="%s" class="page-link">%s</a></li>', $aria, $item->link, $display);
    }

    if ($item->active) {
        return sprintf(
            '<li class="active page-item"><a aria-current="true" aria-label="%s" href="#" class="page-link">%s</a></li>',
            Text::sprintf('JLIB_HTML_PAGE_CURRENT', strtolower($item->text)),
            $display
        );
    }

    return '<li class="disabled page-item"><span class="page-link" aria-hidden="true">' . $display . '</span></li>';
};
?>
<nav class="bf-item-pagination d-flex flex-wrap align-items-center gap-3 ms-auto" aria-label="<?= Text::_('JLIB_HTML_PAGINATION'); ?>">
  <div><?= $displayData['position']; ?></div>
  <ul class="pagination m-0">
    <?= $pageLink($pages['start'], 'icon-angle-double-left', 'JLIB_HTML_GOTO_POSITION_START'); ?>
    <?= $pageLink($pages['previous'], 'icon-angle-left', 'JLIB_HTML_GOTO_POSITION_PREVIOUS'); ?>
    <?php foreach ($pages['pages'] as $page): ?>
      <?= $pageLink($page); ?>
    <?php endforeach; ?>
    <?= $pageLink($pages['next'], 'icon-angle-right', 'JLIB_HTML_GOTO_POSITION_NEXT'); ?>
    <?= $pageLink($pages['end'], 'icon-angle-double-right', 'JLIB_HTML_GOTO_POSITION_END'); ?>
  </ul>
</nav>
