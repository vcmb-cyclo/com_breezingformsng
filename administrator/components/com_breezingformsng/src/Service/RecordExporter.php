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

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\VendorHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordsExportModel;

/**
 * Builds the PDF, CSV, Excel and XML exports of the records. An export covers
 * an explicit selection of ids or, without one, what the list shows: the
 * chosen form and the chosen state.
 */
final class RecordExporter
{
    private const EXPORT_SUBRECORD_BATCH_SIZE = 200;

    /**
     * Row ceiling for the Excel export. PhpSpreadsheet keeps every cell in
     * memory, so a very large selection has to fall back to the streamed CSV
     * export instead.
     */
    public const EXPORT_XLSX_MAX_ROWS = 25000;

    /**
     * Column ceiling for the Excel export. This is the hard limit of the
     * .xlsx format itself; an unfiltered "all forms" export can gather more
     * distinct field names than that.
     */
    private const EXPORT_XLSX_MAX_COLUMNS = 16384;

    public function __construct(
        private readonly RecordsExportModel $records,
        private readonly RecordModel $model,
        private readonly \DateTimeZone $timezone,
        private readonly int $formSelection,
        private readonly string $stateFilter,
    ) {
    }

    /**
     * Exposed for the PDF export templates, which call $this->getSubrecords().
     */
    public function getSubrecords(int $recordId): array
    {
        return $this->model->getSubrecords($recordId);
    }

    /**
     * @param int[] $ids
     */
    public function exceedsSpreadsheetRows(array $ids): bool
    {
        return $this->records->countRecords($ids, $this->formSelection, $this->stateFilter) > self::EXPORT_XLSX_MAX_ROWS;
    }

    /**
     * @param int[] $ids
     *
     * @return array{name: string, document: PdfDocument, ids: list<int>}
     */
    public function pdf(array $ids): array
    {
        // The export templates, including the ones a site adds, run in this
        // scope: they read $recs and may read the variables set here.
        $app = Factory::getApplication();
        $input = $app->getInput();
        $formSelection = $this->formSelection;
        $model = $this->model;
        $tz = $this->timezone;
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $recs = $this->records->getRecords($ids, $this->formSelection, $this->stateFilter);

        $formName = ($formSelection && $recs) ? ($recs[0]->name ?? '') : '';

        $file = JPATH_SITE . '/media/breezingforms/pdftpl/export_custom_pdf.php';
        if (!file_exists($file)) {
            $file = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/pdftpl/export_pdf.php';
        }
        if ($formName !== '') {
            $custom = JPATH_SITE . '/media/breezingforms/pdftpl/' . $formName . '_export_pdf.php';
            if (file_exists($custom)) {
                $file = $custom;
            }
        }

        $updIds = [];
        foreach ($recs as $i => $rec) {
            $updIds[] = (int) $rec->id;
            $recs[$i]->submitted = $this->localTime((string) $rec->submitted)->format('Y-m-d H:i:s', true);
        }

        $datestamp = $this->localTime('now');

        $pdf = new PdfDocument();
        $pdf->setFormName($formName);
        $pdf->setWhich('export');

        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        require_once $file;
        $content = ob_get_clean();

        $activeFound = false;
        $ttfName = '';
        $fontDir = JPATH_SITE . '/media/breezingforms/pdftpl/fonts/';
        foreach ((new PdfFontDirectoryScanner())->scan($fontDir) as $f) {
            $lower = strtolower($f);
            if (str_ends_with($lower, '.php')) {
                $parts = explode('.', $f);
                array_pop($parts);
                $pdf->AddFont(implode('_', $parts), '', $fontDir . $f);
            }
            if (str_ends_with($lower, '.ttf')) {
                $ttfName = PdfDocument::importTtfFont($fontDir . $f);
            }
            if (str_ends_with($lower, '_active')) {
                $parts = explode('_', $f);
                array_pop($parts);
                $pdf->SetFont($ttfName ?: implode('_', $parts));
                $activeFound = true;
            }
        }

        if (!$activeFound) {
            PdfDocument::importTtfFont(JPATH_SITE . '/media/com_breezingformsng/fonts/verdana.ttf');
            $pdf->SetFont('verdana');
        }

        $pdf->setPrintFooter($pdf->getFooterTemplate() !== '');
        $pdf->setPrintHeader($pdf->getHeaderTemplate() !== '');
        $pdf->AddPage();
        $pdf->writeHTML($content);
        $pdf->lastPage();

        return [
            'name' => ($formName ? $formName . '-' : '') . 'ffexport-pdf-' . $datestamp->format('YmdHis', true) . '.pdf',
            'document' => $pdf,
            'ids' => $updIds,
        ];
    }

    /**
     * @param int[] $ids
     *
     * @return array{name: string, content: string, ids: list<int>}
     */
    public function csv(array $ids): array
    {
        $config = $this->model->getExportConfig();

        $delimiter = stripslashes((string) $config->csvdelimiter);
        $quote = stripslashes((string) $config->csvquote);
        $cellNewline = ((int) $config->cellnewline === 0) ? "\n" : "\\n";

        [$formName, $headers, $rows, $updIds] = $this->buildTabularExport($ids);

        $q = fn($val) => $quote
            . str_replace($quote, $quote . $quote, str_replace("\n", $cellNewline, str_replace("\r", '', (string) $val)))
            . $quote;

        $header = implode($delimiter, array_map($q, $headers)) . "\n";
        $body = '';
        foreach ($rows as $row) {
            $body .= implode($delimiter, array_map($q, $row)) . "\n";
        }

        return [
            'name' => ($formName ? $formName . '-' : '') . 'ffexport-' . date('YmdHis') . '.csv',
            'content' => "\xEF\xBB\xBF" . $header . $body,
            'ids' => $updIds,
        ];
    }

    /**
     * @param int[] $ids
     *
     * @return array{name: string, spreadsheet: Spreadsheet, ids: list<int>}
     *
     * @throws \RangeException When the selection has more columns than a sheet can hold.
     */
    public function spreadsheet(array $ids): array
    {
        [$formName, $headers, $rows, $updIds] = $this->buildTabularExport($ids, self::EXPORT_XLSX_MAX_COLUMNS);

        VendorHelper::load();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheetTitle = preg_replace('/[\\\\\\/:*?\[\]]/', '', (string) $formName) ?: Text::_('COM_BREEZINGFORMSNG_RECORDS_RECORDINFO');
        $sheet->setTitle(mb_substr($sheetTitle, 0, 31));

        foreach ($headers as $columnIndex => $header) {
            $sheet->setCellValueExplicit(
                Coordinate::stringFromColumnIndex($columnIndex + 1) . '1',
                (string) $header,
                DataType::TYPE_STRING,
            );
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex($columnIndex + 1) . (string) ($rowIndex + 2),
                    (string) $value,
                    DataType::TYPE_STRING,
                );
            }
        }

        if ($headers) {
            $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
            $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:' . $lastColumn . (string) max(1, count($rows) + 1));
        }

        $filePrefix = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $formName);

        return [
            'name' => ($filePrefix ? $filePrefix . '-' : '') . 'ffexport-' . date('YmdHis') . '.xlsx',
            'spreadsheet' => $spreadsheet,
            'ids' => $updIds,
        ];
    }

    /**
     * @param int[] $ids
     *
     * @return array{name: string, content: string, ids: list<int>}
     */
    public function xml(array $ids): array
    {
        $recs = $this->records->getRecords($ids, $this->formSelection, $this->stateFilter);
        $formName = ($this->formSelection && $recs) ? ($recs[0]->name ?? '') : '';

        $datestamp = $this->localTime('now');

        $ind = fn(int $n) => str_repeat('  ', $n);

        $xml = '<?xml version="1.0" encoding="utf-8" ?>' . "\n"
            . '<FacileFormsExport type="records">' . "\n"
            . $ind(1) . '<exportdate>' . $datestamp->format('Y-m-d H:i:s', true) . '</exportdate>' . "\n";

        $updIds = [];
        foreach (array_chunk($recs, self::EXPORT_SUBRECORD_BATCH_SIZE) as $recordBatch) {
            $subrecordsByRecord = $this->model->getSubrecordsByRecordIds(
                array_map(static fn (object $record): int => (int) $record->id, $recordBatch)
            );

            foreach ($recordBatch as $rec) {
                $updIds[] = (int) $rec->id;
                $date = $this->localTime((string) $rec->submitted);

                $xml .= $ind(1) . '<record id="' . (int) $rec->id . '">' . "\n"
                    . $ind(2) . '<submitted>' . $date->format('Y-m-d H:i:s', true) . '</submitted>' . "\n"
                    . $ind(2) . '<user_id>' . (int) $rec->user_id . '</user_id>' . "\n"
                    . $ind(2) . '<username>' . htmlspecialchars((string) $rec->username) . '</username>' . "\n"
                    . $ind(2) . '<user_full_name>' . htmlspecialchars((string) $rec->user_full_name) . '</user_full_name>' . "\n"
                    . $ind(2) . '<form>' . (int) $rec->form . '</form>' . "\n"
                    . $ind(2) . '<title>' . htmlspecialchars((string) $rec->title) . '</title>' . "\n"
                    . $ind(2) . '<name>' . htmlspecialchars((string) $rec->name) . '</name>' . "\n"
                    . $ind(2) . '<ip>' . htmlspecialchars((string) $rec->ip) . '</ip>' . "\n"
                    . $ind(2) . '<browser>' . htmlspecialchars((string) $rec->browser) . '</browser>' . "\n"
                    . $ind(2) . '<opsys>' . htmlspecialchars((string) $rec->opsys) . '</opsys>' . "\n"
                    . $ind(2) . '<provider>' . htmlspecialchars((string) ($rec->provider ?? '')) . '</provider>' . "\n"
                    . $ind(2) . '<viewed>' . (int) $rec->viewed . '</viewed>' . "\n"
                    . $ind(2) . '<exported>' . (int) $rec->exported . '</exported>' . "\n"
                    . $ind(2) . '<state>' . (int) $rec->state . '</state>' . "\n"
                    . $ind(2) . '<pptxid>' . htmlspecialchars((string) $rec->paypal_tx_id) . '</pptxid>' . "\n"
                    . $ind(2) . '<pppdate>' . htmlspecialchars((string) $rec->paypal_payment_date) . '</pppdate>' . "\n"
                    . $ind(2) . '<pptestacc>' . (int) $rec->paypal_testaccount . '</pptestacc>' . "\n"
                    . $ind(2) . '<ppdltries>' . (int) $rec->paypal_download_tries . '</ppdltries>' . "\n"
                    . $ind(2) . '<opted>' . (int) ($rec->opted ?? 0) . '</opted>' . "\n";

                foreach ($subrecordsByRecord[(int) $rec->id] ?? [] as $sub) {
                    $value = (string) $sub->value;
                    if ($sub->type === 'File Upload' && stripos($value, '{cbsite}') !== false) {
                        $value = str_ireplace('{cbsite}', JPATH_SITE, $value);
                    }
                    $xml .= $ind(2) . '<subrecord id="' . (int) $sub->id . '">' . "\n"
                        . $ind(3) . '<element>' . (int) $sub->element . '</element>' . "\n"
                        . $ind(3) . '<name>' . htmlspecialchars((string) $sub->name) . '</name>' . "\n"
                        . $ind(3) . '<title>' . htmlspecialchars((string) $sub->title) . '</title>' . "\n"
                        . $ind(3) . '<type>' . htmlspecialchars((string) $sub->type) . '</type>' . "\n"
                        . $ind(3) . '<value>' . htmlspecialchars($value) . '</value>' . "\n"
                        . $ind(2) . '</subrecord>' . "\n";
                }
                $xml .= $ind(1) . '</record>' . "\n";
            }
        }
        $xml .= '</FacileFormsExport>' . "\n";

        return [
            'name' => ($formName ? $formName . '-' : '') . 'ffexport-' . $datestamp->format('YmdHis', true) . '.xml',
            'content' => $xml,
            'ids' => $updIds,
        ];
    }

    /**
     * Builds the shared data set used by the CSV and Excel exports.
     *
     * @param int[] $ids
     * @param int   $maxColumns Hard cap on the column count; a \RangeException is
     *                          thrown before any rows are built when it is exceeded.
     *
     * @return array{string, list<string>, list<list<string>>, list<int>}
     */
    private function buildTabularExport(array $ids, int $maxColumns = PHP_INT_MAX): array
    {
        $records = $this->records->getRecords($ids, $this->formSelection, $this->stateFilter);

        if ($ids) {
            $formIds = array_unique(array_map(static fn (object $record): int => (int) $record->form, $records));
        } elseif ($this->formSelection) {
            $formIds = [$this->formSelection];
        } else {
            $formIds = [];
        }

        $fieldKeys = [];
        foreach ($this->records->getElementFields($formIds) as $field) {
            $key = md5(strip_tags((string) $field->name));
            $fieldKeys[$key] ??= strip_tags((string) $field->name);
        }

        $headers = [
            'id', 'submitted', 'user_id', 'username', 'user_full_name', 'bf_form_title', 'ip',
            'browser', 'opsys', 'paypal_tx_id', 'paypal_payment_date', 'paypal_testaccount',
            'paypal_download_tries', 'double_opt_in', 'viewed', 'exported', 'state',
            ...array_values($fieldKeys),
        ];

        if (count($headers) > $maxColumns) {
            throw new \RangeException('Export column count exceeds the requested maximum.');
        }

        $rows = [];
        $updateIds = [];

        foreach (array_chunk($records, self::EXPORT_SUBRECORD_BATCH_SIZE) as $recordBatch) {
            $subrecordsByRecord = $this->model->getSubrecordsByRecordIds(
                array_map(static fn (object $record): int => (int) $record->id, $recordBatch)
            );

            foreach ($recordBatch as $record) {
                $updateIds[] = (int) $record->id;
                $date = $this->localTime((string) $record->submitted);

                $subValues = [];
                foreach ($subrecordsByRecord[(int) $record->id] ?? [] as $subrecord) {
                    $key = md5(strip_tags((string) $subrecord->name));
                    $subValues[$key][] = (string) $subrecord->value;
                }

                $row = [
                    (string) $record->id,
                    $date->format('Y-m-d H:i:s', true),
                    (string) $record->user_id,
                    (string) $record->username,
                    (string) $record->user_full_name,
                    (string) $record->title,
                    (string) $record->ip,
                    (string) $record->browser,
                    (string) $record->opsys,
                    (string) $record->paypal_tx_id,
                    (string) $record->paypal_payment_date,
                    (string) $record->paypal_testaccount,
                    (string) $record->paypal_download_tries,
                    (string) ($record->opted ?? ''),
                    (string) $record->viewed,
                    (string) $record->exported,
                    (string) $record->state,
                ];
                foreach ($fieldKeys as $key => $name) {
                    $row[] = isset($subValues[$key]) ? implode('|', $subValues[$key]) : '';
                }
                $rows[] = $row;
            }
        }

        return [
            ($this->formSelection && $records) ? (string) ($records[0]->name ?? '') : '',
            $headers,
            $rows,
            $updateIds,
        ];
    }

    /**
     * A stored date shifted by the offset of the site time zone, as the
     * exports have always written it.
     */
    private function localTime(string $value): Date
    {
        $date = new Date($value, $this->timezone);
        $offset = $date->getOffsetFromGMT();

        if ($offset > 0) {
            $date->add(new \DateInterval('PT' . $offset . 'S'));
        } elseif ($offset < 0) {
            $date->sub(new \DateInterval('PT' . abs($offset) . 'S'));
        }

        return $date;
    }
}
