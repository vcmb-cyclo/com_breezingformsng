<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Vcmb\Component\BreezingformsNG\Administrator\Service\LegacyFormConverter;

/** Creates an unpublished QuickMode copy; the source and its submissions stay untouched. */
class LegacyConversionModel extends BaseDatabaseModel
{
    public function convert(int $id): int
    {
        $db = $this->getDatabase();

        // Upgraded installations can still have non-transactional historical tables.
        // Do not promise rollback unless both tables support it.
        $formsTable = $db->replacePrefix('#__facileforms_forms');
        $elementsTable = $db->replacePrefix('#__facileforms_elements');
        $engineQuery = $db->createQuery()
            ->select($db->quoteName(['TABLE_NAME', 'ENGINE']))
            ->from($db->quoteName('information_schema.TABLES'))
            ->where($db->quoteName('TABLE_SCHEMA') . ' = DATABASE()')
            ->where('(' . $db->quoteName('TABLE_NAME') . ' = :forms OR '
                . $db->quoteName('TABLE_NAME') . ' = :elements)')
            ->bind(':forms', $formsTable, ParameterType::STRING)
            ->bind(':elements', $elementsTable, ParameterType::STRING);
        $engines = $db->setQuery($engineQuery)->loadAssocList();

        $nonTransactional = array_filter(
            $engines,
            static fn (array $row): bool => strcasecmp((string) $row['ENGINE'], 'InnoDB') !== 0
        );

        if (count($engines) !== 2 || $nonTransactional !== []) {
            throw new \RuntimeException('Non-transactional form tables');
        }

        $db->transactionStart();

        try {
            $query = $db->createQuery()->select('*')->from($db->quoteName('#__facileforms_forms'))
                ->where($db->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
            $source = $db->setQuery($query)->loadAssoc();

            if (!$source || trim((string) ($source['template_code_processed'] ?? '')) === 'QuickMode') {
                throw new \DomainException('unsupported_options');
            }

            // QuickMode saves notification mode from the recipient, so unusual
            // legacy combinations would otherwise change on the next edit.
            $notification = (int) ($source['emailntf'] ?? 0);
            $recipient = trim((string) ($source['emailadr'] ?? ''));
            if (
                !in_array($notification, [0, 1, 2], true)
                || ($notification === 1 && $recipient !== '')
                || ($notification === 2 && $recipient === '')
            ) {
                throw new \DomainException('unsupported_options');
            }

            // Form-specific mail and PDF templates are named after the source.
            // The clone has a different name, so silently continuing would lose them.
            $sourceName = (string) ($source['name'] ?? '');
            if ($sourceName === '' || basename($sourceName) !== $sourceName || str_contains($sourceName, '\\')) {
                throw new \DomainException('unsupported_options');
            }
            foreach (['.html.php', '.txt.php', '_mailback.html.php', '_mailback.txt.php'] as $suffix) {
                if (is_file(JPATH_SITE . '/media/breezingforms/mailtpl/' . $sourceName . $suffix)) {
                    throw new \DomainException('unsupported_templates');
                }
            }
            foreach (['_pdf_attachment.php', '_pdf_mailback_attachment.php'] as $suffix) {
                if (is_file(JPATH_SITE . '/media/breezingforms/pdftpl/' . $sourceName . $suffix)) {
                    throw new \DomainException('unsupported_templates');
                }
            }

            foreach (['script1cond', 'script2cond', 'piece1cond', 'piece2cond', 'piece3cond', 'piece4cond'] as $field) {
                if ((int) ($source[$field] ?? 0) !== 0) {
                    throw new \DomainException('unsupported_scripts');
                }
            }

            foreach (['salesforce_enabled', 'dropbox_submission_enabled'] as $field) {
                if ((int) ($source[$field] ?? 0) !== 0) {
                    throw new \DomainException('unsupported_options');
                }
            }

            if (!empty($source['mailchimp_api_key']) || !empty($source['mailchimp_list_id'])) {
                throw new \DomainException('unsupported_options');
            }

            $query = $db->createQuery()->select('COUNT(*)')
                ->from($db->quoteName('#__facileforms_integrator_rules'))
                ->where($db->quoteName('form_id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);

            if ((int) $db->setQuery($query)->loadResult() !== 0) {
                throw new \DomainException('unsupported_options');
            }

            $query = $db->createQuery()->select('*')->from($db->quoteName('#__facileforms_elements'))
                ->where($db->quoteName('form') . ' = :id')
                ->order([$db->quoteName('page'), $db->quoteName('ordering'), $db->quoteName('id')])
                ->bind(':id', $id, ParameterType::INTEGER);
            $elements = $db->setQuery($query)->loadAssocList();
            $tree = (new LegacyFormConverter())->convert($source, $elements);

            $verifyTree = static function (array $node) use (&$verifyTree): void {
                if (isset($node['properties']['dbId']) && (int) $node['properties']['dbId'] !== 0) {
                    throw new \RuntimeException('Unexpected source element ID');
                }
                foreach ($node['children'] ?? [] as $child) {
                    $verifyTree($child);
                }
            };
            $verifyTree($tree);

            // Never pass source element IDs to QuickmodeModel::save(): it updates by ID.
            $copyName = 'quickmode_' . $id . '_' . bin2hex(random_bytes(8));
            $copyTitle = mb_substr((string) $source['title'], 0, 243) . ' (QuickMode)';
            $tree['properties']['name'] = $copyName;
            $tree['properties']['title'] = $copyTitle;
            $tree['properties']['mailNotification'] = (int) $source['emailntf'] !== 0;
            $tree['properties']['mailRecipient'] = (string) $source['emailadr'];
            unset($tree['properties']['themebootstrapvars']);

            $copy = (object) $source;
            unset($copy->id);
            $copy->name = $copyName;
            $copy->title = $copyTitle;
            $copy->package = 'QuickModeForms';
            $copy->published = 0;
            $copy->checked_out = null;
            $copy->checked_out_time = null;
            $copy->template_code = '';
            $copy->template_areas = '';
            $copy->template_code_processed = '';
            $copy->version = 0;
            $copy->created = (new Date())->toSql();
            $copy->modified = $copy->created;
            $copy->created_by = (string) $this->getCurrentUser()->username;
            $copy->modified_by = $copy->created_by;

            if (!$db->insertObject('#__facileforms_forms', $copy, 'id')) {
                throw new \RuntimeException('Could not create form copy');
            }

            $newId = (int) $db->insertid();
            $model = $this->getMVCFactory()->createModel('Quickmode', 'Administrator', ['ignore_request' => true]);

            if (!$model instanceof QuickmodeModel || $newId <= 0 || $model->save($newId, $tree) !== $newId) {
                throw new \RuntimeException('Could not save converted form');
            }

            // The editor normalises notification settings on save. Preserve the
            // exact source values on the newly created copy.
            $emailNotification = (int) $source['emailntf'];
            $emailAddress = (string) ($source['emailadr'] ?? '');
            $query = $db->createQuery()->update($db->quoteName('#__facileforms_forms'))
                ->set($db->quoteName('emailntf') . ' = :notification')
                ->set($db->quoteName('emailadr') . ' = :address')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':notification', $emailNotification, ParameterType::INTEGER)
                ->bind(':address', $emailAddress, ParameterType::STRING)
                ->bind(':id', $newId, ParameterType::INTEGER);
            $db->setQuery($query)->execute();

            // save() can skip an invalid element insert; detect this before committing.
            $query = $db->createQuery()->select($db->quoteName('name'))
                ->from($db->quoteName('#__facileforms_elements'))
                ->where($db->quoteName('form') . ' = :id')->bind(':id', $newId, ParameterType::INTEGER);
            $savedNames = $db->setQuery($query)->loadColumn();

            foreach ($elements as $element) {
                if (!in_array((string) $element['name'], $savedNames, true)) {
                    throw new \RuntimeException('Converted element was not saved');
                }
            }

            $db->transactionCommit();

            return $newId;
        } catch (\Throwable $exception) {
            $db->transactionRollback();
            throw $exception;
        }
    }
}
