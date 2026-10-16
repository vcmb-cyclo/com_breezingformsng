<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

/**
 * List actions on forms: publish, delete, copy and reorder. The form itself
 * is edited in QuickMode, its options through FormOptionsModel.
 */
class FormModel extends AdminModel
{
    private const INTEGER_COLUMNS = [
        'autoheight', 'ordering', 'published', 'debug_mode', 'runmode',
        'width', 'widthmode', 'height', 'heightmode', 'pages',
        'emailntf', 'mb_emailntf', 'emaillog', 'mb_emaillog',
        'emailxml', 'mb_emailxml', 'email_type', 'mb_email_type',
        'email_custom_html', 'mb_email_custom_html', 'dblog',
        'script1cond', 'script2cond', 'piece1cond', 'piece2cond',
        'piece3cond', 'piece4cond', 'prevmode', 'double_opt',
        'mailchimp_double_optin', 'mailchimp_send_errors',
        'mailchimp_delete_member', 'salesforce_enabled',
        'dropbox_submission_enabled',
    ];

    private const NULLABLE_INTEGER_COLUMNS = [
        'script1id', 'script2id', 'piece1id', 'piece2id', 'piece3id', 'piece4id',
        'prevwidth',
    ];

    /**
     * @param  string                $name
     * @param  string                $prefix
     * @param  array<string, mixed>  $options
     */
    public function getTable($name = 'Form', $prefix = 'Site', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * The list actions need no edit form: an empty one fulfils the contract.
     *
     * @param  array<string, mixed>  $data
     * @param  bool                  $loadData
     */
    public function getForm($data = [], $loadData = true)
    {
        return $this->getFormFactory()->createForm('com_breezingformsng.form', ['control' => 'jform']);
    }

    /**
     * Forms are ordered among the forms of their package.
     */
    protected function getReorderConditions($table)
    {
        $db = $this->getDatabase();

        return [$db->quoteName('package') . ' = ' . $db->quote((string) $table->package)];
    }

    /**
     * Deletes the forms with their elements, all of them or none.
     *
     * @param  array<int|string, mixed>  $pks
     */
    public function delete(&$pks)
    {
        $ids = array_values(array_unique(ArrayHelper::toInteger((array) $pks)));
        $db = $this->getDatabase();

        $db->transactionStart();

        try {
            if (!parent::delete($ids)) {
                $db->transactionRollback();
                $pks = [];

                return false;
            }

            if ($ids !== []) {
                // Joomla can return true without visiting later IDs when one no longer exists.
                $remaining = (int) $db->setQuery(
                    $db->createQuery()
                        ->select('COUNT(*)')
                        ->from($db->quoteName('#__facileforms_forms'))
                        ->whereIn($db->quoteName('id'), $ids)
                )->loadResult();

                if ($remaining > 0) {
                    $pks = [];

                    throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_RECORD'));
                }

                $db->setQuery(
                    $db->createQuery()
                        ->delete($db->quoteName('#__facileforms_elements'))
                        ->whereIn($db->quoteName('form'), $ids)
                )->execute();
            }

            $db->transactionCommit();
        } catch (\Throwable $e) {
            $db->transactionRollback();

            throw $e;
        }

        $pks = $ids;

        return true;
    }

    /**
     * Copies the forms with their elements.
     *
     * @param int[] $ids
     */
    public function copy(array $ids): int
    {
        $db = $this->getDatabase();
        $now = (new Date())->toSql();
        $uid = (string) $this->getCurrentUser()->username;
        $copied = 0;

        foreach ($ids as $id) {
            $query = $db->createQuery()
                ->select('*')
                ->from($db->quoteName('#__facileforms_forms'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
            $source = $db->setQuery($query)->loadObject();

            if ($source === null) {
                continue;
            }

            $data = (array) $source;
            unset($data['id']);
            $data['title'] = 'Copy of ' . $source->title;
            $data['name'] = 'copy_' . $source->name;
            $data['ordering'] = 999999;
            $data['created'] = $now;
            $data['created_by'] = $uid;
            $data['modified'] = $now;
            $data['modified_by'] = $uid;
            $data = $this->normaliseCloneData($data);

            $db->setQuery(
                $db->createQuery()
                    ->insert($db->quoteName('#__facileforms_forms'))
                    ->columns($db->quoteName(array_keys($data)))
                    ->values(implode(',', array_map(
                        static fn($value) => $value === null ? 'NULL' : $db->quote($value),
                        array_values($data)
                    )))
            )->execute();
            $newId = (int) $db->insertid();

            $query = $db->createQuery()
                ->select('*')
                ->from($db->quoteName('#__facileforms_elements'))
                ->where($db->quoteName('form') . ' = :form')
                ->bind(':form', $id, ParameterType::INTEGER);

            foreach ($db->setQuery($query)->loadAssocList() as $element) {
                unset($element['id']);
                $element['form'] = $newId;

                $db->setQuery(
                    $db->createQuery()
                        ->insert($db->quoteName('#__facileforms_elements'))
                        ->columns($db->quoteName(array_keys($element)))
                        ->values(implode(',', array_map([$db, 'quote'], array_values($element))))
                )->execute();
            }

            $table = $this->getTable();
            $table->reorder($this->getReorderConditions($source));
            $copied++;
        }

        return $copied;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normaliseCloneData(array $data): array
    {
        foreach (self::INTEGER_COLUMNS as $column) {
            if (array_key_exists($column, $data)) {
                $data[$column] = (int) $data[$column];
            }
        }

        foreach (self::NULLABLE_INTEGER_COLUMNS as $column) {
            if (array_key_exists($column, $data)) {
                $data[$column] = $data[$column] === '' || $data[$column] === null
                    ? null
                    : (int) $data[$column];
            }
        }

        return $data;
    }
}
