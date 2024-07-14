/**
* BreezingForms - A Joomla Forms Application
* @version 1.4.4
* @package BreezingForms
* @copyright (C) 2004-2005 by Peter Koch
* @license Released under the terms of the GNU General Public License
**/

// simple textarea support

function textAreaResize(name, size)
{
    var area = eval('document.adminForm.'+name);
    area.rows = size;
} // textAreaResize

// linked codearea support

var codeArea = new Array();

function codeAreaFocus(element)
{
    var cnt = codeArea.length;
    for (var a = 0; a < cnt; a++)
        if (codeArea[a].target == element) {
            codeArea[a].target = codeArea[a].source;
            codeArea[a].source = element;
            break;
        } else
            if (codeArea[a].source == element)
                break;
} // codeAreaFocus

function codeAreaChange(element, ev)
{
    if (arguments.length>1)
        if (ev.keyCode!=8 && ev.keyCode!=13 && ev.keyCode!=46)
            return true;
    var cnt = codeArea.length;
    for (var a = 0; a < cnt; a++)
        if (codeArea[a].edit == element) {
            area = codeArea[a];
            var s = area.edit.value;
            var p = -1;
            var n = 0;
            for (;;) {
                p = s.indexOf('\n',p+1);
                if (p < 0) break;
                n++;
            } // for
            if (n != area.linecnt) {
                var t = '';
                for (p = 1; p <= n; p++) t += p+'\n';
                t += p;
                //area.lines.value = t;
                area.linecnt = n;
            } // if
            break;
        } // if
    return true;
} // codeAreaChange

function codeAreaResize(name, size)
{
    var cnt = codeArea.length;
    for (var a = 0; a < cnt; a++)
        if (codeArea[a].name == name) {
            codeArea[a].edit.rows = size;
            codeArea[a].lines.rows = size;
            break;
        } // if
} // codeAreaResize

function codeAreaRefresh()
{
    var cnt = codeArea.length;
    for (var a = 0; a < cnt; a++)
        codeArea[a].target.scrollTop=codeArea[a].source.scrollTop;
    setTimeout('codeAreaRefresh()', 100);
} // codeAreaRefresh

function codeAreaAdd(editname, linesname)
{
    var area   = new Object();
    area.name  = editname;
    area.edit  = eval('document.adminForm.'+editname);
    area.lines = eval('document.adminForm.'+linesname);
    area.source = area.edit;
    area.target = area.lines;
    area.linecnt = -1;
    codeArea[codeArea.length] = area;
    codeAreaChange(area.edit);
    if (codeArea.length == 1) codeAreaRefresh();
} // codeAreaAdd