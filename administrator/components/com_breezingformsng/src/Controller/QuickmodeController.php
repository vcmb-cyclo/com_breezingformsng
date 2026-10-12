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
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\ContentBuilderInstallation;

class QuickmodeController extends BaseController
{
    public function display($cachable = false, $urlparams = []): static
    {
        $this->app->getInput()->set('view', 'quickmode');
        return parent::display($cachable, $urlparams);
    }

    /**
     * Chunked AJAX save endpoint — called repeatedly by quickmode-app.js.
     * Writes each chunk to a temp file, then on the last chunk reassembles,
     * saves to DB, and echoes the form ID as plain text.
     */
    public function doAjaxSave(): void
    {
        $this->checkToken('post');

        /** @var CMSApplication $app */
        $app   = $this->app;
        $input = $app->getInput();

        $chunksLength = $input->getInt('chunksLength', 0);
        $form         = $input->getInt('form', 0);
        $chunkIdx     = $input->getInt('chunkIdx', 0);
        $rndAdd       = $input->getAlnum('rndAdd', '');
        $chunk        = $input->getString('chunk', '');

        if ($chunksLength < 1 || $chunkIdx < 0 || $chunkIdx >= $chunksLength || $rndAdd === '') {
            $app->setHeader('status', 400, true);
            $app->close();
        }

        $cacheDir = Path::clean((string) $app->get('tmp_path') . '/com_breezingformsng-quickmode');

        if (!is_dir($cacheDir) && !Folder::create($cacheDir)) {
            $app->setHeader('status', 500, true);
            $app->close();
        }

        $dest = $cacheDir . '/ajaxsave_' . $chunkIdx . '_' . $rndAdd . '.txt';
        if (!File::write($dest, $chunk)) {
            $app->setHeader('status', 500, true);
            $app->close();
        }

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        if ($chunkIdx === $chunksLength - 1) {
            $contents = '';
            for ($i = 0; $i < $chunksLength; $i++) {
                $file = $cacheDir . '/ajaxsave_' . $i . '_' . $rndAdd . '.txt';
                if (!is_file($file)) {
                    $app->setHeader('status', 400, true);
                    $app->close();
                }

                $contents .= (string) file_get_contents($file);
                File::delete($file);
            }

            $decoded = base64_decode($contents, true);
            $dataObject = $decoded === false ? null : json_decode($decoded, true);

            if (!is_array($dataObject)) {
                $app->setHeader('status', 400, true);
                $app->close();
            }

            $formId = $this->getQuickmodeModel()->save($form, $dataObject);

            // Optional ContentBuilderNG integration
            $cbngBasePath = JPATH_SITE . '/administrator/components/com_contentbuilderng';
            if (ContentBuilderInstallation::isInstalled($cbngBasePath)) {
                require_once $cbngBasePath . '/src/Helper/FormSourceFactory.php';
                require_once $cbngBasePath . '/src/Service/PathService.php';
                require_once $cbngBasePath . '/src/Service/TemplateSampleService.php';
                require_once $cbngBasePath . '/src/Service/FormSupportService.php';

                $cbForm = \CB\Component\Contentbuilderng\Administrator\Helper\FormSourceFactory::getForm('com_breezingformsng', $formId);
                $db = Factory::getContainer()->get(DatabaseInterface::class);
                $sourceType = 'com_breezingformsng';
                $query = $db->getQuery(true)
                    ->select($db->quoteName('id'))
                    ->from($db->quoteName('#__contentbuilderng_forms'))
                    ->where($db->quoteName('type') . ' = :sourceType')
                    ->where($db->quoteName('reference_id') . ' = :referenceId')
                    ->bind(':sourceType', $sourceType)
                    ->bind(':referenceId', $formId, ParameterType::INTEGER);
                $db->setQuery($query);
                $cbForms = $db->loadColumn();

                if (is_object($cbForm) && count($cbForms)) {
                    $formSupportService = new \CB\Component\Contentbuilderng\Administrator\Service\FormSupportService(
                        new \CB\Component\Contentbuilderng\Administrator\Service\PathService(),
                        $db,
                        new \CB\Component\Contentbuilderng\Administrator\Service\TemplateSampleService($app, $db)
                    );
                    foreach ($cbForms as $dataId) {
                        $formSupportService->synchElements($dataId, $cbForm);
                    }
                }
            }

            $app->setHeader('Content-Type', 'text/plain; charset=UTF-8', true);
            $app->sendHeaders();
            echo $formId;
            $app->close();
        }

        $app->close();
    }

    /**
     * Inline editor for rich-text / CodeMirror element properties (opens in a modal/iframe).
     */
    public function editor(): void
    {
        $input = $this->app->getInput();
        $input->set('view', 'quickmode');
        $input->set('layout', 'editor');
        parent::display();
    }

    private function getQuickmodeModel(): QuickmodeModel
    {
        $component = $this->app->bootComponent('com_breezingformsng');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $model = $component->getMVCFactory()->createModel('Quickmode', 'Administrator', ['ignore_request' => true]);

        if (!$model instanceof QuickmodeModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }
}
