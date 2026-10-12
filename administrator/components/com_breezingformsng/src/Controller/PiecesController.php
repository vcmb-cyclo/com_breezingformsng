<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AjaxStateService;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;
use Vcmb\Component\BreezingformsNG\Administrator\Service\PieceManager;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PieceModel;
use Vcmb\Component\BreezingformsNG\Site\Table\PieceTable;

/** @property CMSApplication $app */
class PiecesController extends BaseController
{
    /** Tasks that only display data; every other task changes or runs code. */
    private const READ_ONLY_TASKS = ['display', 'edit', 'cancel', 'previous', 'next'];

    public function execute($task)
    {
        if (!\in_array(strtolower((string) $task), self::READ_ONLY_TASKS, true)) {
            AreaAccess::assert($this->app->getIdentity(), AreaAccess::CODE);
        }

        return parent::execute($task);
    }

    public function add(): void
    {
        $this->runLegacyTask('edit', []);
    }

    public function edit(): void
    {
        $this->runLegacyTask('edit');
    }

    public function save(): void
    {
        $this->assertPostToken();
        $this->runLegacyTask('save');
    }

    public function cancel(): void
    {
        $this->runLegacyTask('cancel');
    }

    public function copy(): void
    {
        $this->assertPostToken();
        $this->runLegacyTask('copy');
    }

    public function remove(): void
    {
        $this->assertPostToken();
        $this->runLegacyTask('del');
    }

    public function publish(): void
    {
        $this->assertPostToken();
        $this->runLegacyTask('publish', null, 1);
    }

    public function unpublish(): void
    {
        $this->assertPostToken();
        $this->runLegacyTask('publish', null, 0);
    }

    public function setPublished(): void
    {
        $input = $this->app->getInput()->post;
        $id = $input->getInt('id', 0);
        $state = $input->getString('state', '');
        $payload = AjaxStateService::error(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_breezingformsng')) {
            $payload = AjaxStateService::error(Text::_('JERROR_ALERTNOAUTHOR'));
        } elseif (!$this->checkToken('post', false)) {
            $payload = AjaxStateService::error(Text::_('JINVALID_TOKEN'));
        } elseif ($id > 0 && in_array($state, ['0', '1'], true)) {
            try {
                $table = new PieceTable(Factory::getContainer()->get(DatabaseInterface::class));

                if ($table->load($id)) {
                    /** @var PieceModel $model */
                    $model = $this->getModel('Piece');

                    if ($model->publishByIds([$id], $state === '1') === 1) {
                        $payload = AjaxStateService::success((int) $state);
                    }
                }
            } catch (\Throwable) {
                $payload = AjaxStateService::error(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }
        }

        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        echo new JsonResponse($payload);
        $this->app->close();
    }

    public function previous(): void
    {
        $this->runLegacyTask('prev');
    }

    public function next(): void
    {
        $this->runLegacyTask('next');
    }

    public function test(): void
    {
        $this->runLegacyTask('test');
    }

    public function runTest(): void
    {
        $this->assertPostToken();
        $this->runLegacyTask('testrun');
    }

    public function runTestsAjax(): void
    {
        $this->checkToken();
        $this->runLegacyTask('testrunajax');
    }

    private function runLegacyTask(string $method, ?array $ids = null, ?int $state = null): void
    {
        $this->assertAuthorised();
        $this->prepareDocument();

        $arguments = ['com_breezingformsng', $this->getPackage()];

        if ($ids !== null || !in_array($method, ['save', 'cancel'], true)) {
            $arguments[] = $ids ?? $this->getIds();
        }

        if ($state !== null) {
            $arguments[] = $state;
        }

        /** @var PieceModel $model */
        $model = $this->getModel('Piece');
        $manager = new PieceManager(
            $this->app,
            Factory::getContainer()->get(DatabaseInterface::class),
            $model,
        );
        $manager->$method(...$arguments);
    }

    private function assertAuthorised(): void
    {
        if (!$this->app->getIdentity()->authorise('core.manage', 'com_breezingformsng')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }

    private function prepareDocument(): void
    {
        $title = Text::_('COM_BREEZINGFORMSNG') . ' / ' . Text::_('COM_BREEZINGFORMSNG_MANAGEPIECES');

        $document = $this->app->getDocument();
        $document->setTitle($title);
        $document->getWebAssetManager()->getRegistry()->addExtensionRegistryFile('com_breezingformsng');
        $document->getWebAssetManager()->addInlineStyle(
            '.icon-logo_left{'
            . 'background-image:url(' . Uri::root(true) . '/media/com_breezingformsng/images/logo_left.png);'
            . 'background-size:contain;'
            . 'background-repeat:no-repeat;'
            . 'background-position:center;'
            . 'display:inline-block;'
            . 'width:48px;'
            . 'height:48px;'
            . 'vertical-align:middle;'
            . '}'
        );
        ToolbarHelper::title($title, 'logo_left');
    }

    private function assertPostToken(): void
    {
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }
    }

    private function getPackage(): string
    {
        $package = $this->app->getInput()->getString('pkg', '');

        return $package === '- blank -' ? '' : $package;
    }

    private function getIds(): array
    {
        $input = $this->app->getInput();
        $ids = (array) $input->get('ids', [], 'array');

        if ($ids === []) {
            $ids = (array) $input->get('cid', [], 'array');
        }

        return array_values(array_filter(array_map('intval', $ids)));
    }

}
