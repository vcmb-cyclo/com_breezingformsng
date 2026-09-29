<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Scripts;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Vcmb\Component\BreezingformsNG\Administrator\Model\ScriptsModel;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    public $option = 'com_breezingformsng';

    public string $package = '';

    public string $search = '';

    public int $total = 0;

    public int $limit = 10;

    public int $limitStart = 0;

    public array $packageList = [];

    public array $rows = [];

    public $pagination = null;

    public string $listOrder = 'a.name';

    public string $listDirn = 'asc';

    public string $filterState = '';

    public function display($tpl = null)
    {
        $model = $this->getModel();

        if (!$model instanceof ScriptsModel) {
            throw new \RuntimeException('Unable to create BreezingForms NG scripts model.');
        }

        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $input = $app->getInput();

        if ($this->package === '') {
            $this->package = $input->getString('pkg', '');
        }

        $list = $model->prepareList($this->package, $input, $app->getSession());

        $this->package = $list['package'];
        $this->packageList = $list['packageList'];
        $this->search = $list['search'];
        $this->total = $list['total'];
        $this->limit = $list['limit'];
        $this->limitStart = $list['limitStart'];
        $this->rows = $list['rows'];
        $this->pagination = $list['pagination'];
        $this->listOrder = $list['listOrder'];
        $this->listDirn = $list['listDirn'];
        $this->filterState = $list['filterState'];

        $app->getDocument()->getWebAssetManager()->registerAndUseScript(
            'com_breezingformsng.admin-sort',
            'media/com_breezingformsng/js/admin/admin-sort.js',
            ['version' => 'auto'],
            ['defer' => true],
            ['core']
        );
        $app->getDocument()->getWebAssetManager()->registerAndUseScript(
            'com_breezingformsng.scripts-list',
            'media/com_breezingformsng/js/admin/scripts-list.js',
            ['version' => 'auto'],
            ['defer' => true],
            ['core', 'com_breezingformsng.admin-sort']
        );
        $app->getDocument()->getWebAssetManager()->useScript('table.columns');

        ToolbarHelper::custom('scripts.add', 'new.png', 'new_f2.png', 'COM_BREEZINGFORMSNG_TOOLBAR_NEW', false);
        ToolbarHelper::custom('scripts.copy', 'copy.png', 'copy_f2.png', 'COM_BREEZINGFORMSNG_TOOLBAR_COPY', false);
        ToolbarHelper::custom('scripts.publish', 'publish.png', 'publish_f2.png', 'COM_BREEZINGFORMSNG_TOOLBAR_PUBLISH', false);
        ToolbarHelper::custom('scripts.unpublish', 'unpublish.png', 'unpublish_f2.png', 'COM_BREEZINGFORMSNG_TOOLBAR_UNPUBLISH', false);
        ToolbarHelper::custom('scripts.remove', 'delete.png', 'delete_f2.png', 'COM_BREEZINGFORMSNG_TOOLBAR_DELETE', false);
        ToolbarHelper::preferences('com_breezingformsng');
        ToolbarHelper::help(
            'COM_BREEZINGFORMSNG_HELP_SCRIPTS_TITLE',
            false,
            Uri::base() . 'index.php?option=com_breezingformsng&view=scripts&layout=help&tmpl=component'
        );

        parent::display($tpl);
    }
}
