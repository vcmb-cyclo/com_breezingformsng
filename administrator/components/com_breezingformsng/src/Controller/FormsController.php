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
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AjaxStateService;

/** @property CMSApplication $app */
class FormsController extends BaseController
{
    public function display($cachable = false, $urlparams = []): static
    {
        $this->app->getInput()->set('view', 'forms');
        return parent::display($cachable, $urlparams);
    }

    public function edit(): void
    {
        $input    = $this->app->getInput();
        $id       = $input->getInt('id', 0);
        $pkg      = $input->getString('pkg', '');
        $advanced = $input->getBool('advanced', false);

        if ($id > 0 && !$advanced) {
            $this->app->redirect(Route::_(
                'index.php?option=com_breezingformsng&task=quickmode.display&form=' . $id . '&pkg=' . rawurlencode($pkg),
                false
            ));
            return;
        }

        $this->app->redirect(Route::_(
            'index.php?option=com_breezingformsng&view=forms&layout=edit&id=' . $id . '&pkg=' . rawurlencode($pkg)
                . ($advanced ? '&advanced=1' : ''),
            false
        ));
    }

    public function save(): void
    {
        $app   = $this->app;
        $input = $app->getInput();

        if (!$this->checkToken()) {
            $app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $app->redirect(Route::_($this->listUrl($input->getString('pkg', '')), false));
            return;
        }

        $data = $input->post->getArray([
            'id'                       => 'INT',
            'package'                  => 'STRING',
            'title'                    => 'STRING',
            'name'                     => 'STRING',
            'description'              => 'STRING',
            'class1'                   => 'STRING',
            'width'                    => 'INT',
            'widthmode'                => 'INT',
            'height'                   => 'INT',
            'heightmode'               => 'INT',
            'pages'                    => 'INT',
            'published'                => 'INT',
            'ordering'                 => 'INT',
            'runmode'                  => 'INT',
            'prevmode'                 => 'INT',
            'prevwidth'                => 'INT',
            'autoheight'               => 'INT',
            'emailntf'                 => 'INT',
            'emaillog'                 => 'INT',
            'emailxml'                 => 'INT',
            'dblog'                    => 'INT',
            'emailadr'                 => 'STRING',
            'custom_mail_subject'      => 'STRING',
            'alt_mailfrom'             => 'STRING',
            'alt_fromname'             => 'STRING',
            'email_type'               => 'INT',
            'email_custom_html'        => 'INT',
            'mb_emailntf'              => 'INT',
            'mb_emaillog'              => 'INT',
            'mb_emailxml'              => 'INT',
            'mb_custom_mail_subject'   => 'STRING',
            'mb_alt_mailfrom'          => 'STRING',
            'mb_alt_fromname'          => 'STRING',
            'mb_email_type'            => 'INT',
            'mb_email_custom_html'     => 'INT',
            'script1cond'              => 'INT',
            'script1id'                => 'INT',
            'script2cond'              => 'INT',
            'script2id'                => 'INT',
            'piece1cond'               => 'INT',
            'piece1id'                 => 'INT',
            'piece2cond'               => 'INT',
            'piece2id'                 => 'INT',
            'piece3cond'               => 'INT',
            'piece3id'                 => 'INT',
            'piece4cond'               => 'INT',
            'piece4id'                 => 'INT',
            'mailchimp_email_field'    => 'STRING',
            'mailchimp_checkbox_field' => 'STRING',
            'mailchimp_api_key'        => 'STRING',
            'mailchimp_list_id'        => 'STRING',
            'mailchimp_double_optin'   => 'INT',
            'mailchimp_mergevars'      => 'STRING',
            'mailchimp_text_html_mobile_field' => 'STRING',
            'mailchimp_send_errors'    => 'INT',
            'mailchimp_default_type'   => 'CMD',
            'mailchimp_delete_member'  => 'INT',
            'mailchimp_unsubscribe_field' => 'STRING',
            'salesforce_token'         => 'STRING',
            'salesforce_username'      => 'STRING',
            'salesforce_password'      => 'STRING',
            'salesforce_type'          => 'STRING',
            'salesforce_enabled'       => 'INT',
            'dropbox_email'            => 'STRING',
            'dropbox_password'         => 'STRING',
            'dropbox_folder'           => 'STRING',
            'dropbox_submission_enabled' => 'INT',
            'dropbox_reset_auth'       => 'INT',
        ]);

        $data['salesforce_fields'] = implode(',', array_filter(
            $input->post->get('salesforce_fields', [], 'array'),
            static fn($value): bool => is_string($value) && $value !== ''
        ));
        $data['dropbox_submission_types'] = implode(',', array_intersect(
            $input->post->get('dropbox_submission_types', [], 'array'),
            ['pdf', 'csv', 'xml']
        ));

        $id = (int) ($data['id'] ?? 0);
        if ($id > 0 && $data['salesforce_password'] === '') {
            $data['salesforce_password'] = (string) ($this->getFormModel()->getForm($id)->salesforce_password ?? '');
        }
        if (!empty($data['dropbox_reset_auth'])) {
            $data['dropbox_email'] = '';
            $data['dropbox_password'] = '';
        }
        unset($data['dropbox_reset_auth']);

        foreach (['piece1code', 'piece2code', 'piece3code', 'piece4code',
                  'script1code', 'script2code',
                  'email_custom_template', 'mb_email_custom_template'] as $field) {
            $data[$field] = $input->post->get($field, '', 'raw');
        }

        try {
            $id  = $this->getFormModel()->saveForm($data);
            $pkg = (string) ($data['package'] ?? '');
            // Set by the QuickMode "Options" tab's own form (bfOptionsForm) so
            // the redirect lands back on that tab instead of the default one.
            $returnTab = $input->post->getCmd('return_tab', '');
            $title = trim((string) ($data['title'] ?? ''));
            $app->enqueueMessage(
                $title !== ''
                    ? Text::sprintf('COM_BREEZINGFORMSNG_FORM_SAVE_SUCCESS', $title)
                    : Text::_('JLIB_APPLICATION_SAVE_SUCCESS'),
                'message'
            );
            $app->redirect(Route::_(
                'index.php?option=com_breezingformsng&task=quickmode.display&form=' . $id
                . ($pkg !== '' ? '&pkg=' . rawurlencode($pkg) : '')
                . ($returnTab === 'options' ? '#fragment-3' : ''),
                false
            ));
        } catch (\Throwable $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            $id  = (int) ($data['id'] ?? 0);
            $pkg = (string) ($data['package'] ?? '');
            $app->redirect(Route::_(
                'index.php?option=com_breezingformsng&view=forms&layout=edit&id=' . $id . '&pkg=' . rawurlencode($pkg),
                false
            ));
        }
    }

    public function cancel(): void
    {
        $pkg = $this->app->getInput()->getString('pkg', '');
        $this->app->redirect(Route::_($this->listUrl($pkg), false));
    }

    public function remove(): void
    {
        $app   = $this->app;
        $input = $app->getInput();

        if (!$this->checkToken()) {
            $app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
        } else {
            $ids = $this->selectedIds($input);
            if (!empty($ids)) {
                $this->getFormModel()->deleteItems($ids);
                $app->enqueueMessage(Text::_('COM_BREEZINGFORMSNG_DELETE_SUCCESS'), 'message');
            }
        }

        $app->redirect(Route::_($this->listUrl($input->getString('pkg', '')), false));
    }

    public function copy(): void
    {
        $app   = $this->app;
        $input = $app->getInput();

        if (!$this->checkToken()) {
            $app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
        } else {
            $ids = $this->selectedIds($input);
            if (!empty($ids)) {
                $this->getFormModel()->copyItems($ids);
                $app->enqueueMessage(
                    Text::plural('COM_BREEZINGFORMSNG_FORMS_N_COPIED', count($ids)),
                    'message'
                );
            }
        }

        $app->redirect(Route::_($this->listUrl($input->getString('pkg', '')), false));
    }

    public function publish(): void
    {
        $this->togglePublish(1);
    }

    public function unpublish(): void
    {
        $this->togglePublish(0);
    }

    public function orderup(): void
    {
        $this->moveOrder(-1);
    }

    public function orderdown(): void
    {
        $this->moveOrder(1);
    }

    public function run(): void
    {
        $input  = $this->app->getInput();
        $formId = $input->getInt('id', 0);

        if ($formId <= 0) {
            $this->app->redirect(Route::_($this->listUrl($input->getString('pkg', '')), false));
            return;
        }

        $this->app->redirect(Route::_(
            'index.php?option=com_breezingformsng&ff_form=' . $formId,
            false
        ));
    }

    public function setPublished(): void
    {
        $this->setAjaxState('published');
    }

    public function setDebug(): void
    {
        $this->setAjaxState('debug');
    }

    private function togglePublish(int $state): void
    {
        $app   = $this->app;
        $input = $app->getInput();

        if (!$this->checkToken()) {
            $app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
        } else {
            $ids = $this->selectedIds($input);
            if (!empty($ids)) {
                $this->getFormModel()->publish($ids, $state);
            }
        }

        $app->redirect(Route::_($this->listUrl($input->getString('pkg', '')), false));
    }

    private function setAjaxState(string $property): void
    {
        $app = $this->app;

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!$this->checkToken('post')) {
            $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
            echo new JsonResponse(AjaxStateService::error(Text::_('JINVALID_TOKEN')));
            $app->close();
        }

        $input = $app->getInput();
        $id    = $input->post->getInt('id', 0);
        $state = AjaxStateService::normalizeState($input->post->getInt('state', 0));

        if ($id <= 0) {
            $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
            echo new JsonResponse(AjaxStateService::error(Text::_('JERROR_AN_ERROR_HAS_OCCURRED')));
            $app->close();
        }

        if ($property === 'debug') {
            $this->getFormModel()->setDebugMode($id, $state);
        } else {
            $this->getFormModel()->publish([$id], $state);
        }

        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        echo new JsonResponse(AjaxStateService::success($state));
        $app->close();
    }

    private function moveOrder(int $inc): void
    {
        $app   = $this->app;
        $input = $app->getInput();

        if (!$this->checkToken()) {
            $app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
        } else {
            $ids = $this->selectedIds($input);
            $pkg = $input->getString('pkg', '');
            if (!empty($ids)) {
                $this->getFormModel()->moveOrder((int) $ids[0], $inc, $pkg);
            }
        }

        $app->redirect(Route::_($this->listUrl($input->getString('pkg', '')), false));
    }

    private function listUrl(string $pkg): string
    {
        return 'index.php?option=com_breezingformsng&view=forms'
            . ($pkg !== '' ? '&pkg=' . rawurlencode($pkg) : '');
    }

    private function selectedIds(\Joomla\Input\Input $input): array
    {
        $ids = array_values(array_filter(array_map('intval', (array) $input->get('cid', [], 'array'))));

        if ($ids === []) {
            $actionId = $input->getInt('action_id', 0);
            if ($actionId > 0) {
                $ids[] = $actionId;
            }
        }

        return $ids;
    }

    private function getFormModel(): FormModel
    {
        $component = $this->app->bootComponent('com_breezingformsng');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $model = $component->getMVCFactory()
            ->createModel('Form', 'Administrator', ['ignore_request' => true]);

        if (!$model instanceof FormModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }
}
