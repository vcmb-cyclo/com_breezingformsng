<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\Event;

/**
 * The options of a form, edited from QuickMode or the advanced options page.
 */
class FormOptionsModel extends BaseDatabaseModel
{
    private function db(): DatabaseInterface
    {
        return $this->getDatabase();
    }

    public function getForm(int $id): ?\stdClass
    {
        if ($id <= 0) {
            return null;
        }

        $db = $this->db();
        $q  = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__facileforms_forms'))
            ->where($db->quoteName('id') . ' = ' . (int) $id);

        return $db->setQuery($q)->loadObject() ?: null;
    }

    public function getDefaultForm(string $pkg): \stdClass
    {
        $obj                         = new \stdClass();
        $obj->id                     = 0;
        $obj->package                = $pkg;
        $obj->title                  = '';
        $obj->name                   = '';
        $obj->description            = '';
        $obj->class1                 = 'content_outline';
        $obj->width                  = 400;
        $obj->widthmode              = 0;
        $obj->height                 = 500;
        $obj->heightmode             = 0;
        $obj->pages                  = 1;
        $obj->published              = 1;
        $obj->ordering               = 0;
        $obj->runmode                = 0;
        $obj->prevmode               = 2;
        $obj->prevwidth              = 400;
        $obj->autoheight             = 0;
        $obj->emailntf               = 1;
        $obj->emaillog               = 1;
        $obj->emailxml               = 0;
        $obj->dblog                  = 1;
        $obj->emailadr               = '';
        $obj->custom_mail_subject    = '';
        $obj->alt_mailfrom           = '';
        $obj->alt_fromname           = '';
        $obj->email_type             = 0;
        $obj->email_custom_html      = 0;
        $obj->email_custom_template  = '';
        $obj->mb_emailntf            = 1;
        $obj->mb_emaillog            = 1;
        $obj->mb_emailxml            = 0;
        $obj->mb_custom_mail_subject = '';
        $obj->mb_alt_mailfrom        = '';
        $obj->mb_alt_fromname        = '';
        $obj->mb_email_type          = 0;
        $obj->mb_email_custom_html   = 0;
        $obj->mb_email_custom_template = '';
        $obj->script1cond            = 0;
        $obj->script1id              = 0;
        $obj->script1code            = '';
        $obj->script2cond            = 0;
        $obj->script2id              = 0;
        $obj->script2code            = '';
        $obj->piece1cond             = 0;
        $obj->piece1id               = 0;
        $obj->piece1code             = '';
        $obj->piece2cond             = 0;
        $obj->piece2id               = 0;
        $obj->piece2code             = '';
        $obj->piece3cond             = 0;
        $obj->piece3id               = 0;
        $obj->piece3code             = '';
        $obj->piece4cond             = 0;
        $obj->piece4id               = 0;
        $obj->piece4code             = '';
        foreach (['mailchimp_email_field', 'mailchimp_checkbox_field', 'mailchimp_api_key', 'mailchimp_list_id',
            'mailchimp_mergevars', 'mailchimp_text_html_mobile_field', 'mailchimp_unsubscribe_field',
            'salesforce_token', 'salesforce_username', 'salesforce_password', 'salesforce_type', 'salesforce_fields',
            'dropbox_email', 'dropbox_password', 'dropbox_folder'] as $column) {
            $obj->$column = '';
        }
        $obj->mailchimp_double_optin = 1;
        $obj->mailchimp_send_errors = 0;
        $obj->mailchimp_default_type = 'text';
        $obj->mailchimp_delete_member = 0;
        $obj->salesforce_enabled = 0;
        $obj->dropbox_submission_enabled = 0;
        $obj->dropbox_submission_types = 'pdf';
        $obj->created                = null;
        $obj->created_by             = '';
        $obj->modified               = null;
        $obj->modified_by            = '';

        return $obj;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveForm(array $data): int
    {
        $db  = $this->db();
        $now = (new \Joomla\CMS\Date\Date())->toSql();
        $uid = (string) $this->getCurrentUser()->username;
        $id  = (int) ($data['id'] ?? 0);

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_FORMS_TITLEEMPTY'));
        }

        $cols = [
            'package', 'title', 'name', 'description',
            'class1', 'width', 'widthmode', 'height', 'heightmode', 'pages',
            'published', 'ordering', 'runmode', 'prevmode', 'prevwidth', 'autoheight',
            'emailntf', 'emaillog', 'emailxml', 'dblog', 'emailadr',
            'custom_mail_subject', 'alt_mailfrom', 'alt_fromname',
            'email_type', 'email_custom_html', 'email_custom_template',
            'mb_emailntf', 'mb_emaillog', 'mb_emailxml',
            'mb_custom_mail_subject', 'mb_alt_mailfrom', 'mb_alt_fromname',
            'mb_email_type', 'mb_email_custom_html', 'mb_email_custom_template',
            'script1cond', 'script1id', 'script1code',
            'script2cond', 'script2id', 'script2code',
            'piece1cond', 'piece1id', 'piece1code',
            'piece2cond', 'piece2id', 'piece2code',
            'piece3cond', 'piece3id', 'piece3code',
            'piece4cond', 'piece4id', 'piece4code',
            'mailchimp_email_field', 'mailchimp_checkbox_field', 'mailchimp_api_key', 'mailchimp_list_id',
            'mailchimp_double_optin', 'mailchimp_mergevars', 'mailchimp_text_html_mobile_field',
            'mailchimp_send_errors', 'mailchimp_default_type', 'mailchimp_delete_member',
            'mailchimp_unsubscribe_field', 'salesforce_token', 'salesforce_username', 'salesforce_password',
            'salesforce_type', 'salesforce_fields', 'salesforce_enabled', 'dropbox_email', 'dropbox_password',
            'dropbox_folder', 'dropbox_submission_enabled', 'dropbox_submission_types',
        ];

        $sets = [];
        foreach ($cols as $col) {
            $val = $data[$col] ?? '';
            $sets[$col] = in_array($col, ['width', 'widthmode', 'height', 'heightmode', 'pages',
                'published', 'ordering', 'runmode', 'prevmode', 'prevwidth', 'autoheight',
                'emailntf', 'emaillog', 'emailxml', 'dblog', 'email_custom_html', 'mb_email_custom_html',
                'email_type', 'mb_email_type', 'mb_emailntf', 'mb_emaillog', 'mb_emailxml',
                'script1cond', 'script1id', 'script2cond', 'script2id',
                'piece1cond', 'piece1id', 'piece2cond', 'piece2id',
                'piece3cond', 'piece3id', 'piece4cond', 'piece4id',
                'mailchimp_double_optin', 'mailchimp_send_errors', 'mailchimp_delete_member',
                'salesforce_enabled', 'dropbox_submission_enabled',
            ], true) ? (int) $val : (string) $val;
        }

        if ($id > 0) {
            $sets['modified']    = $now;
            $sets['modified_by'] = $uid;

            $q = $db->createQuery()->update($db->quoteName('#__facileforms_forms'));
            foreach ($sets as $col => $val) {
                $q->set($db->quoteName($col) . ' = ' . $db->quote((string) $val));
            }
            $q->set($db->quoteName('modified') . ' = ' . $db->quote($now));
            $q->set($db->quoteName('modified_by') . ' = ' . $db->quote($uid));
            $q->where($db->quoteName('id') . ' = ' . (int) $id);
            $db->setQuery($q)->execute();
        } else {
            $sets['created']    = $now;
            $sets['created_by'] = $uid;
            $sets['modified']   = $now;
            $sets['modified_by']= $uid;

            $q = $db->createQuery()
                ->insert($db->quoteName('#__facileforms_forms'))
                ->columns(array_map(fn($c) => $db->quoteName($c), array_keys($sets)))
                ->values(implode(',', array_map(fn($v) => $db->quote((string) $v), array_values($sets))));
            $db->setQuery($q)->execute();
            $id = (int) $db->insertid();
        }

        $this->reorder((string) ($sets['package'] ?? ''));

        PluginHelper::importPlugin('breezingforms_addons');
        $this->getDispatcher()
            ->dispatch('onPropertiesSave', new Event('onPropertiesSave', [$id]));

        return $id;
    }

    public function setDebugMode(int $id, int $state): void
    {
        $db = $this->db();
        $db->setQuery(
            $db->createQuery()
                ->update($db->quoteName('#__facileforms_forms'))
                ->set($db->quoteName('debug_mode') . ' = ' . (int) $state)
                ->where($db->quoteName('id') . ' = ' . (int) $id)
        )->execute();
    }

    private function reorder(string $pkg): void
    {
        $db = $this->db();
        $q  = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__facileforms_forms'))
            ->order($db->quoteName('ordering') . ' ASC, ' . $db->quoteName('id') . ' ASC');

        if ($pkg !== '') {
            $q->where($db->quoteName('package') . ' = ' . $db->quote($pkg));
        }

        $ids = $db->setQuery($q)->loadColumn() ?: [];
        foreach (array_values($ids) as $pos => $rowId) {
            $db->setQuery(
                $db->createQuery()
                    ->update($db->quoteName('#__facileforms_forms'))
                    ->set($db->quoteName('ordering') . ' = ' . ($pos + 1))
                    ->where($db->quoteName('id') . ' = ' . $db->quote($rowId))
            )->execute();
        }
    }
}
