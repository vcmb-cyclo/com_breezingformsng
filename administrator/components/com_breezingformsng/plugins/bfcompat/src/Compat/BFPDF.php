<?php
/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Vcmb\Component\BreezingformsNG\Administrator\Service\PdfDocument;

/**
 * Not a Joomla-version compatibility shim: kept as the BreezingForms legacy
 * API surface. PHP stored in the database (facileforms_pieces.code,
 * forms.piece*code) and external callers may reference this class by name
 * to build a custom PDF (e.g. a custom export or mailback attachment). The
 * implementation now lives in Administrator\Service\PdfDocument (the same
 * class the component's own export/notification code uses), so this facade
 * never drifts out of sync with it - see BFQuickModeBootstrap/Mobile/OnePage
 * for the same pattern applied to the Phase 9c renderers.
 *
 * Only the class name is preserved: PdfDocument extends the native
 * tc-lib-pdf API, so stored code calling the former TCPDF methods
 * (AddPage(), SetFont(), writeHTML(), Output()...) must be adapted. The
 * installer reports stored code that still references this class.
 */
class BFPDF extends PdfDocument
{
}
