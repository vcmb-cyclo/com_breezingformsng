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

/** Assembles the complete client-side Query List page callback. */
final class QueryListPageScriptBuilder
{
    /**
     * @param array{start: string, previous: string, next: string, end: string} $labels
     */
    public function build(
        array $labels,
        bool $hasCheckboxes,
        int $heightMode,
        int $height,
        bool $inFrame,
        string $newline
    ): string {
        return 'function ff_dispQueryPage(id,page)' . $newline .
            '{' . $newline .
            '    var forced = false;' . $newline .
            '    if (arguments.length>2) forced = arguments[2];' . $newline .
            self::rowsRefresh($newline) . $newline .
            '    if (pagenav > 0 && pagesize > 0) {' . $newline .
            '        var navi = \'\';' . $newline .
            self::navigation($labels, $newline) . $newline .
            '        rows[header+pagesize].cells[0].innerHTML = navi;' . $newline .
            '    } // if' . $newline .
            '    ff_queryCurrPage[id] = page;' . $newline .
            self::paginationTail($hasCheckboxes, $heightMode, $height, $inFrame, $newline) .
            '} // ff_dispQueryPage';
    }

    private static function rowsRefresh(string $newline): string
    {
        return '    var qrows = ff_queryRows[id];' . $newline .
            '    var cnt = qrows.length;' . $newline .
            '    var currpage = ff_queryCurrPage[id];' . $newline .
            '    var pagesize = ff_queryPageSize[id];' . $newline .
            '    var pagenav = ff_queryPagenav[id];' . $newline .
            '    var lastpage = 1;' . $newline .
            '    if (pagesize > 0) {' . $newline .
            '        lastpage = parseInt((cnt+pagesize-1)/pagesize);' . $newline .
            '        if (lastpage == 1) pagesize = cnt;' . $newline .
            '    } // if' . $newline .
            '    if (page < 1) page = 1;' . $newline .
            '    if (page > lastpage) page = lastpage;' . $newline .
            '    if (!forced && page == currpage) return;' . $newline .
            '    var p, c;' . $newline .
            '    for (p = 1; p < page; p++) cnt -= pagesize;' . $newline .
            '    if (cnt > pagesize) cnt = pagesize;' . $newline .
            '    var start = (page-1) * pagesize;' . $newline .
            '    var rows = document.getElementById(\'ff_elem\'+id).rows;' . $newline .
            '    var cols = ff_queryCols[id];' . $newline .
            '    var checkbox = ff_queryCheckbox[id];' . $newline .
            '    var header = ff_queryHeader[id];' . $newline .
            '    for (p = 0; p < cnt; p++) {' . $newline .
            '        var qrow = qrows[start+p];' . $newline .
            '        var row = rows[header+p];' . $newline .
            '        var cc = 0;' . $newline .
            '        for (c = 0; c < cols.length; c++)' . $newline .
            '            if (cols[c]) {' . $newline .
            '                if (c==0 && checkbox>0) {' . $newline .
            '                    document.getElementById(\'ff_cb\'+id+\'_\'+p).value = qrow[c];' . $newline .
            '                    cc++;' . $newline .
            '                } else' . $newline .
            '                    row.cells[cc++].textContent = qrow[c];' . $newline .
            '            } // if' . $newline .
            '        row.style.display = \'\';' . $newline .
            '    } // for' . $newline .
            '    for (p = cnt; p < pagesize; p++) {' . $newline .
            '        var row = rows[p+header];' . $newline .
            '        row.style.display = \'none\';' . $newline .
            '    } // for';
    }

    /** @param array{start: string, previous: string, next: string, end: string} $labels */
    private static function navigation(array $labels, string $newline): string
    {
        return '        if (pagenav<=4) {' . $newline .
            '            if (page>1) navi += \'<a href="javascript:ff_dispQueryPage(\'+id+\',1);">\';' . $newline .
            '            navi += \'&lt;&lt;\';' . $newline .
            '            if (pagenav<=2) navi += \' ' . $labels['start'] . '\';' . $newline .
            '            if (page>1) navi += \'<\\/a>\';' . $newline .
            '            navi += \' \';' . $newline .
            '            if (page>1) navi += \'<a href="javascript:ff_dispQueryPage(\''
            . '+id+\',\'+(page-1)+\');">\';' . $newline .
            '            navi += \'&lt;\';' . $newline .
            '            if (pagenav<=2) navi += \' ' . $labels['previous'] . '\';' . $newline .
            '            if (page>1) navi += \'<\\/a>\';' . $newline .
            '            navi += \' \';' . $newline .
            '        } // if' . $newline .
            '        if (pagenav % 2) {' . $newline .
            '            for (p = 1; p <= lastpage; p++)' . $newline .
            '                if (p == page) ' . $newline .
            '                    navi += p+\' \';' . $newline .
            '                else' . $newline .
            '                    navi += \'<a href="javascript:ff_dispQueryPage(\''
            . '+id+\',\'+p+\');">\'+p+\'<\\/a> \';' . $newline .
            '        } // if' . $newline .
            '        if (pagenav<=4) {' . $newline .
            '            if (page<lastpage) navi += \'<a href="javascript:ff_dispQueryPage(\''
            . '+id+\',\'+(page+1)+\');">\';' . $newline .
            '            if (pagenav<=2) navi += \'' . $labels['next'] . ' \';' . $newline .
            '            navi += \'&gt;\';' . $newline .
            '            if (page<lastpage) navi += \'<\\/a>\';' . $newline .
            '            navi += \' \';' . $newline .
            '            if (page<lastpage) navi += \'<a href="javascript:ff_dispQueryPage(\''
            . '+id+\',\'+lastpage+\');">\';' . $newline .
            '            if (pagenav<=2) navi += \'' . $labels['end'] . ' \';' . $newline .
            '            navi += \'&gt;&gt;\';' . $newline .
            '            if (page<lastpage) navi += \'<\\/a>\';' . $newline .
            '        } // if';
    }

    private static function paginationTail(
        bool $hasCheckboxes,
        int $heightMode,
        int $height,
        bool $inFrame,
        string $newline
    ): string {
        $code = '';

        if ($hasCheckboxes) {
            $code .= '    if (checkbox) ff_selectAllQueryRows(id, false);' . $newline;
        }
        if ($heightMode > 0) {
            $code .= '    ff_resizepage(' . $heightMode . ', ' . $height . ');' . $newline;
        }
        if ($inFrame) {
            $code .= '    parent.window.scrollTo(0,0);' . $newline;
        }

        return $code . '    window.scrollTo(0,0);' . $newline;
    }
}
