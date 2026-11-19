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
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormOptionsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ContentBuilderFormSync;
use Vcmb\Component\BreezingformsNG\Administrator\Service\FormVersioning;

class QuickmodeController extends BaseController
{
    /**
     * @param array<string, mixed> $urlparams
     */
    public function display($cachable = false, $urlparams = []): static
    {
        $formId = $this->app->getInput()->getInt('form', 0);
        $user = $this->app->getIdentity();
        $action = $formId > 0 ? 'core.edit' : 'core.create';

        if ($user === null || !$user->authorise($action, 'com_breezingformsng')) {
            $this->setMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_breezingformsng&view=forms', false));

            return $this;
        }

        // GET only renders the editor. The lock is acquired by the token-checked save.
        if ($formId > 0) {
            $form = $this->getOptionsModel()->getForm($formId);

            if ($form === null || (int) $form->published === -2) {
                $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
                $this->setRedirect(Route::_('index.php?option=com_breezingformsng&view=forms', false));

                return $this;
            }
        }

        $this->app->getInput()->set('view', 'quickmode');
        return parent::display($cachable, $urlparams);
    }

    /**
     * Leaves QuickMode: checks the form in and goes back to the forms list.
     */
    public function close(): void
    {
        $this->checkToken('get');

        $formId = $this->app->getInput()->getInt('form', 0);

        if ($formId > 0) {
            $model = $this->getFormModel();

            // Joomla refuses it when someone else holds the lock and the user cannot manage check-in.
            if (!$model->checkin($formId)) {
                $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()), 'error');
            } elseif (($user = $this->app->getIdentity()) !== null && $user->authorise('core.edit.state', 'com_breezingformsng')) {
                $this->getOptionsModel()->setDebugMode($formId, 0);
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&view=forms', false));
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
        $user         = $app->getIdentity();
        $action       = $form > 0 ? 'core.edit' : 'core.create';

        if ($user === null || !$user->authorise($action, 'com_breezingformsng')) {
            $app->setHeader('status', '403', true);
            $app->close();
            return;
        }

        if ($form > 0) {
            $existingForm = $this->getOptionsModel()->getForm($form);

            if ($existingForm === null || (int) $existingForm->published === -2) {
                $app->setHeader('status', '403', true);
                $app->close();

                return;
            }
        }

        $chunkIdx     = $input->getInt('chunkIdx', 0);
        $rndAdd       = $input->getAlnum('rndAdd', '');
        $chunk        = $input->getString('chunk', '');

        if ($chunksLength < 1 || $chunkIdx < 0 || $chunkIdx >= $chunksLength || $rndAdd === '') {
            $app->setHeader('status', '400', true);
            $app->close();
        }

        $cacheDir = Path::clean((string) $app->get('tmp_path') . '/com_breezingformsng-quickmode');

        if (!is_dir($cacheDir) && !Folder::create($cacheDir)) {
            $app->setHeader('status', '500', true);
            $app->close();
        }

        $dest = $cacheDir . '/ajaxsave_' . $chunkIdx . '_' . $rndAdd . '.txt';
        if (!File::write($dest, $chunk)) {
            $app->setHeader('status', '500', true);
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
                    $app->setHeader('status', '400', true);
                    $app->close();
                }

                $contents .= (string) file_get_contents($file);
                File::delete($file);
            }

            $decoded = base64_decode($contents, true);
            $dataObject = $decoded === false ? null : json_decode($decoded, true);

            if (!is_array($dataObject)) {
                $app->setHeader('status', '400', true);
                $app->close();
            }

            /** @var array<string, mixed> $dataObject The application closes above when it is not an array. */

            $db = Factory::getContainer()->get(DatabaseInterface::class);

            // The browser reads the answer as the form id: a PHP notice shown on
            // a site that displays errors would stop the save right after this
            // request, without the options, the version or the reload.
            ob_start();

            $status = 200;
            $formId = 0;
            $revision = '';
            try {
                $db->transactionStart();
                try {
                    if ($form > 0) {
                        // Serialize the checkout and save, including simultaneous requests by the same user.
                        $lockedForm = $this->getOptionsModel()->getForm($form, true);
                        $expectedRevision = $input->post->getString('formRevision', '');
                        if ($lockedForm === null || (int) $lockedForm->published === -2) {
                            $status = 403;
                        } elseif (
                            !$this->getFormModel()->checkout($form)
                            || !hash_equals(FormOptionsModel::quickmodeRevision($lockedForm), $expectedRevision)
                        ) {
                            $status = 409;
                        }
                    }

                    if ($status !== 200) {
                        $db->transactionRollback();
                    } else {
                        $versioning = new FormVersioning($db, $this->getFormModel());
                        if ($form > 0) {
                            // A form without versions keeps the state it had before this save.
                            $versioning->keepOrigin($form);
                        }

                        $formId = $this->getQuickmodeModel()->save($form, $dataObject);

                        if ($input->getInt('recordVersion', 1) === 1) {
                            try {
                                $versioning->record($formId);
                            } catch (\Throwable $e) {
                                // The form save can still be committed if history could not be written.
                                Log::add($e->getMessage(), Log::WARNING, 'com_breezingformsng');
                            }
                        }

                        $savedForm = $this->getOptionsModel()->getForm($formId);
                        $revision = $savedForm === null ? '' : FormOptionsModel::quickmodeRevision($savedForm);
                        $db->transactionCommit();
                    }
                } catch (\Throwable $e) {
                    $db->transactionRollback();
                    throw $e;
                }

                if ($status === 200) {
                    (new ContentBuilderFormSync($app, $db))->sync($formId);
                }
            } catch (\Throwable $e) {
                Log::add($e->getMessage(), Log::WARNING, 'com_breezingformsng');
                $status = 500;
            } finally {
                $notices = trim((string) ob_get_clean());

                if ($notices !== '') {
                    Log::add('QuickMode save of form ' . $form . ': ' . mb_substr(strip_tags($notices), 0, 1000), Log::WARNING, 'com_breezingformsng');
                }
            }

            if ($status !== 200) {
                $app->setHeader('status', (string) $status, true);
                $app->sendHeaders();
                $app->close();

                return;
            }

            $app->setHeader('Content-Type', 'text/plain; charset=UTF-8', true);
            $app->setHeader('X-BFNG-Form-Revision', $revision, true);
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

    private function getFormModel(): FormModel
    {
        $component = $this->app->bootComponent('com_breezingformsng');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $model = $component->getMVCFactory()->createModel('Form', 'Administrator', ['ignore_request' => true]);

        if (!$model instanceof FormModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }

    private function getOptionsModel(): FormOptionsModel
    {
        $model = $this->getModel('FormOptions');

        if (!$model instanceof FormOptionsModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }
}
