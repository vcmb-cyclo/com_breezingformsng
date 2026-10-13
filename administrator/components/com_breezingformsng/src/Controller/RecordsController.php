<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Utilities\ArrayHelper;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordsExportModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AjaxStateService;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ActionLogger;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordExporter;

/** @property CMSApplication $app */
class RecordsController extends AdminController
{
    protected $text_prefix = 'COM_BREEZINGFORMSNG_RECORDS';

    private ?RecordsModel $listModel = null;

    private const LIST_URL = 'index.php?option=com_breezingformsng&view=records';

    public function __construct($config = [], ?MVCFactoryInterface $factory = null, $app = null, $input = null)
    {
        parent::__construct($config, $factory, $app, $input);

        // A record is published, archived or trashed, never unpublished.
        $this->unregisterTask('unpublish');
        $this->unregisterTask('report');
    }

    public function getModel($name = 'Record', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function display($cachable = false, $urlparams = [])
    {
        $this->app->getInput()->set('view', 'records');
        return parent::display($cachable, $urlparams);
    }

    /**
     * Joomla calls this hook after a failed deletion too: the model leaves in
     * the ids only those actually deleted.
     */
    protected function postDeleteHook(BaseDatabaseModel $model, $id = null)
    {
        $deleted = array_values(array_map('intval', (array) $id));

        if ($deleted !== []) {
            (new ActionLogger($this->app))->log(ActionLogger::DELETED, ActionLogger::RECORD, $deleted);
        }
    }

    public function viewed(): void     { $this->batchFlag('viewed', 1); }
    public function unviewed(): void   { $this->batchFlag('viewed', 0); }
    public function exported(): void   { $this->batchFlag('exported', 1); }
    public function unexported(): void { $this->batchFlag('exported', 0); }

    public function setFlag(): void
    {
        $this->checkToken();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $input = $this->app->getInput();
        $recordId = $input->getInt('record_id', 0);
        $column = AjaxStateService::normalizeRecordColumn($input->getString('column', ''));
        $flag = AjaxStateService::normalizeState($input->getInt('flag', 0));
        if ($recordId > 0 && $column !== null) {
            $this->getRecordModel()->setFlagSingle($recordId, $column, $flag);
        }
        $payload = $recordId > 0 && $column !== null
            ? AjaxStateService::success($flag)
            : AjaxStateService::error(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        echo new JsonResponse($payload);
        $this->app->close();
    }

    public function exportPdf(): void
    {
        $this->checkToken();

        $export = $this->getExporter()->pdf($this->selectedIds());
        $this->getRecordModel()->markExported($export['ids']);

        $export['document']->Output($export['name'], 'D');
        $this->app->close();
    }

    public function exportCsv(): void
    {
        $this->checkToken();

        $export = $this->getExporter()->csv($this->selectedIds());
        $this->getRecordModel()->markExported($export['ids']);

        $app = $this->app;

        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $app->setHeader('Pragma', 'public', true);
        $app->setHeader('Expires', '0', true);
        $app->setHeader('Cache-Control', 'private', true);
        $app->setHeader('Content-Type', 'text/csv; charset=UTF-8', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $export['name'] . '"', true);
        $app->sendHeaders();
        echo $export['content'];
        $app->close();
    }

    public function exportXlsx(): void
    {
        $this->checkToken();

        $app = $this->app;
        $ids = $this->selectedIds();
        $exporter = $this->getExporter();

        if ($exporter->exceedsSpreadsheetRows($ids)) {
            $app->enqueueMessage(
                Text::sprintf('COM_BREEZINGFORMSNG_RECORDS_XLSX_TOO_MANY_ROWS', RecordExporter::EXPORT_XLSX_MAX_ROWS),
                'warning'
            );
            $app->redirect(self::LIST_URL);

            return;
        }

        try {
            $export = $exporter->spreadsheet($ids);
        } catch (\RangeException) {
            $app->enqueueMessage(Text::_('COM_BREEZINGFORMSNG_RECORDS_XLSX_TOO_MANY_COLUMNS'), 'warning');
            $app->redirect(self::LIST_URL);

            return;
        }

        $this->getRecordModel()->markExported($export['ids']);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $app->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $export['name'] . '"', true);
        $app->setHeader('Cache-Control', 'max-age=0', true);
        $app->sendHeaders();
        $writer = new Xlsx($export['spreadsheet']);
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
        $export['spreadsheet']->disconnectWorksheets();
        $app->close();
    }

    public function exportXml(): void
    {
        $this->checkToken();

        $export = $this->getExporter()->xml($this->selectedIds());
        $this->getRecordModel()->markExported($export['ids']);

        $app = $this->app;

        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $app->setHeader('Pragma', 'public', true);
        $app->setHeader('Expires', '0', true);
        $app->setHeader('Cache-Control', 'private', true);
        $app->setHeader('Content-Type', 'application/octet-stream', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $export['name'] . '"', true);
        $app->sendHeaders();
        echo $export['content'];
        $app->close();
    }

    private function batchFlag(string $column, int $value = 1): void
    {
        $this->checkToken();

        $app = $this->app;
        $input = $app->getInput();
        $ids = $input->post->get('cid', [], 'array');
        ArrayHelper::toInteger($ids);
        $this->getRecordModel()->setFlagsBatch($ids, $column, $value);

        $keyBases = [
            'viewed'   => $value ? 'COM_BREEZINGFORMSNG_RECORDS_N_MARKED_VIEWED' : 'COM_BREEZINGFORMSNG_RECORDS_N_UNMARKED_VIEWED',
            'exported' => $value ? 'COM_BREEZINGFORMSNG_RECORDS_N_MARKED_EXPORTED' : 'COM_BREEZINGFORMSNG_RECORDS_N_UNMARKED_EXPORTED',
        ];
        if (isset($keyBases[$column])) {
            $app->enqueueMessage(Text::plural($keyBases[$column], count($ids)), 'message');
        }

        $app->redirect(self::LIST_URL);
    }

    /**
     * The list model, holding the filters of the records list.
     */
    private function getListModel(): RecordsModel
    {
        if ($this->listModel === null) {
            $model = $this->factory->createModel('Records', 'Administrator');

            if (!$model instanceof RecordsModel) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            $this->listModel = $model;
        }

        return $this->listModel;
    }

    private function listFormId(): int
    {
        return (int) $this->getListModel()->getState('filter.form');
    }

    private function getRecordModel(): RecordModel
    {
        $component = $this->app->bootComponent('com_breezingformsng');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $model = $component->getMVCFactory()->createModel('Record', 'Administrator');

        if (!$model instanceof RecordModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }

    /**
     * @return int[] The ids selected in the list, if any.
     */
    private function selectedIds(): array
    {
        return ArrayHelper::toInteger((array) $this->app->getInput()->post->get('cid', [], 'array'));
    }

    /**
     * The exporter of what the list shows: its form and state filters.
     */
    private function getExporter(): RecordExporter
    {
        $records = $this->factory->createModel('RecordsExport', 'Administrator');

        if (!$records instanceof RecordsExportModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return new RecordExporter(
            $records,
            $this->getRecordModel(),
            new \DateTimeZone((string) $this->app->get('offset', 'UTC')),
            $this->listFormId(),
            (string) $this->getListModel()->getState('filter.state')
        );
    }
}
