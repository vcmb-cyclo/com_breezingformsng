<?php
/**
 * BreezingForms NG - A Joomla Forms Application
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2012 by Markus Bopp
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * */
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

if (!isset($pdfPageNumber, $pdfPageCount)) {
    throw new \LogicException('PDF footer requires page numbers.');
}

echo '<div style="font-size: 8pt; text-align: right;">' . (int) $pdfPageNumber . ' / ' . (int) $pdfPageCount . '</div>';
