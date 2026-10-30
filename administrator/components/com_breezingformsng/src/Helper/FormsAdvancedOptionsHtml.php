<?php

declare(strict_types=1);

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;
use Vcmb\Component\BreezingformsNG\Site\Service\Notification\MailTemplateResolver;

/**
 * The "advanced options" settings block (General/Email/Scripts/Pieces/
 * MailChimp/Salesforce/Dropbox) shown on the classic `forms.edit&advanced=1`
 * screen. Extracted so the same markup and logic can also be rendered as the
 * "Options" tab inside the QuickMode editor (`task=quickmode.display`),
 * without duplicating it.
 */
final class FormsAdvancedOptionsHtml
{
    /**
     * @return array<string, int>
     */
    public static function countEntries(\stdClass $f): array
    {
        $countConfigured = static function (array $values): int {
            return count(array_filter($values, static fn ($value): bool => $value !== null && $value !== '' && $value !== 0 && $value !== '0'));
        };

        return [
            'general' => $countConfigured([$f->title ?? '', $f->name ?? '', $f->package ?? '', $f->description ?? '', $f->class1 ?? '']),
            'email' => $countConfigured([
                $f->emailntf ?? 0, $f->emailadr ?? '', $f->custom_mail_subject ?? '', $f->alt_mailfrom ?? '',
                $f->alt_fromname ?? '', $f->mb_emailntf ?? 0, $f->mb_custom_mail_subject ?? '',
                $f->mb_alt_mailfrom ?? '', $f->mb_alt_fromname ?? '', $f->mb_emailxml ?? 0, $f->mb_email_type ?? 0,
            ]),
            'scripts' => (int) ((int) ($f->script1cond ?? 0) > 0) + (int) ((int) ($f->script2cond ?? 0) > 0),
            'form-pieces' => (int) ((int) ($f->piece1cond ?? 0) > 0) + (int) ((int) ($f->piece2cond ?? 0) > 0),
            'submit-pieces' => (int) ((int) ($f->piece3cond ?? 0) > 0) + (int) ((int) ($f->piece4cond ?? 0) > 0),
            'mailchimp' => $countConfigured([
                $f->mailchimp_api_key ?? '', $f->mailchimp_list_id ?? '', $f->mailchimp_email_field ?? '',
                $f->mailchimp_checkbox_field ?? '', $f->mailchimp_unsubscribe_field ?? '',
                $f->mailchimp_text_html_mobile_field ?? '', $f->mailchimp_mergevars ?? '',
            ]),
            'salesforce' => $countConfigured([
                $f->salesforce_enabled ?? 0, $f->salesforce_token ?? '', $f->salesforce_username ?? '',
                $f->salesforce_type ?? '', $f->salesforce_fields ?? '',
            ]),
            'dropbox' => $countConfigured([
                $f->dropbox_email ?? '', $f->dropbox_password ?? '', $f->dropbox_folder ?? '',
                $f->dropbox_submission_enabled ?? 0,
            ]),
        ];
    }

    /**
     * @param array<int, \stdClass> $list
     */
    public static function bfSel(array $list, string $name, int $current, string $extra = ''): string
    {
        $out = '<select class="form-select" name="' . htmlspecialchars($name) . '" ' . $extra . '>';
        $out .= '<option value="0">' . htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_FORMS_NONE')) . '</option>';
        foreach ($list as $item) {
            $sel  = (int) $item->id === (int) $current ? ' selected' : '';
            $out .= '<option value="' . (int) $item->id . '"' . $sel . '>' . htmlspecialchars($item->text) . '</option>';
        }
        $out .= '</select>';

        return $out;
    }

    /**
     * Library pieces the form keeps by id and that are no longer in their
     * list (deleted, reimported, unpublished or of another type): they are
     * not run.
     *
     * @param array<int, array<int, \stdClass>> $piecesByIndex pieces offered for piece1 to piece4
     * @return array<int, int> missing piece id (0 when none is chosen), by piece index
     */
    public static function missingPieces(\stdClass $f, array $piecesByIndex): array
    {
        $missing = [];

        foreach ($piecesByIndex as $index => $pieces) {
            $pieceId = (int) ($f->{'piece' . $index . 'id'} ?? 0);
            $listedIds = array_map(static fn (object $piece): int => (int) $piece->id, $pieces);

            if ((int) ($f->{'piece' . $index . 'cond'} ?? 0) === 1 && !in_array($pieceId, $listedIds, true)) {
                $missing[$index] = $pieceId;
            }
        }

        return $missing;
    }

    /** Keep an existing template's format when the default email is selected. */
    public static function mailbackHtmlSelection(\stdClass $form): int
    {
        if ((int) ($form->id ?? 0) === 0 && (string) ($form->mb_email_custom_template ?? '') === '') {
            return 1;
        }

        return (int) ($form->mb_email_custom_html ?? 0);
    }

    /**
     * Elements of the form whose label and value an email template can use.
     *
     * @return array<int, string>
     */
    public static function mailFieldNames(int $formId, int $emailLog): array
    {
        if ($emailLog !== 1 && $emailLog !== 2) {
            return [];
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->select($db->quoteName('name'))
            ->from($db->quoteName('#__facileforms_elements'))
            ->where($db->quoteName('form') . ' = :formId')
            ->where($db->quoteName('logging') . ' = 1')
            ->whereIn(
                $db->quoteName('type'),
                [
                    'File Upload', 'Text', 'Textarea', 'Checkbox', 'Radio Button',
                    'Select List', 'Query List', 'Radio Group', 'Checkbox Group',
                    'Number Input', 'Calendar', 'Hidden Input', 'Signature',
                ],
                ParameterType::STRING
            )
            ->whereNotIn(
                $db->quoteName('name'),
                ['bfFakeName', 'bfFakeName2', 'bfFakeName3', 'bfFakeName4', 'bfFakeName5', 'bfFakeName6'],
                ParameterType::STRING
            )
            ->order($db->quoteName('ordering'))
            ->bind(':formId', $formId, ParameterType::INTEGER);

        return $db->setQuery($query)->loadColumn();
    }

    /**
     * What the default confirmation email holds, as NotificationEngine builds
     * it: the source of a template file of the site, else the submission
     * details and fields, which is also what the template file shipped gives.
     *
     * @return array{file: string, content: string}
     */
    public static function mailbackDefaultBody(\stdClass $f): array
    {
        $template = (new MailTemplateResolver())->resolve(
            JPATH_SITE . '/media/breezingforms/mailtpl',
            (string) ($f->name ?? ''),
            '_mailback',
            'mailbacktpl'
        );

        if ($template['file'] !== '') {
            // Line endings and the file header differ between released versions.
            $normalize = static fn (string $path): string => trim((string) preg_replace(
                '/^.*?\?>/s',
                '',
                str_replace("\r\n", "\n", (string) @file_get_contents($path)),
                1
            ));
            $source = $normalize($template['file']);

            if ($source !== $normalize(JPATH_ADMINISTRATOR . '/components/com_breezingformsng/mailtpl/mailbacktpl.txt.php')) {
                return ['file' => basename($template['file']), 'content' => (string) file_get_contents($template['file'])];
            }
        }

        $lines = [Text::_('COM_BREEZINGFORMSNG_PROCESS_RECORDSAVEDID') . ' …', ''];

        foreach (['FORMID', 'FORMTITLE', 'FORMNAME', 'SUBMITTEDAT', 'SUBMITTERIP', 'SUBMITTERID', 'SUBMITTERUSERNAME', 'SUBMITTERFULLNAME', 'PROVIDER', 'BROWSER', 'OPSYS'] as $key) {
            $lines[] = Text::_('COM_BREEZINGFORMSNG_PROCESS_' . $key) . ': …';
        }

        // The engine fills the fields of both emails from the same data, kept
        // according to the emaillog setting of the administrator notification.
        if ((int) ($f->emaillog ?? 1) > 0) {
            $lines[] = '';
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $formId = (int) ($f->id ?? 0);
            $query = $db->createQuery()
                ->select($db->quoteName('title'))
                ->from($db->quoteName('#__facileforms_elements'))
                ->where($db->quoteName('form') . ' = :formId')
                ->where($db->quoteName('logging') . ' = 1')
                ->order($db->quoteName('ordering'))
                ->bind(':formId', $formId, ParameterType::INTEGER);

            foreach ($db->setQuery($query)->loadColumn() as $title) {
                $lines[] = strip_tags((string) $title) . ': …';
            }
        }

        return ['file' => '', 'content' => implode("\n", $lines)];
    }

    /**
     * Packages a form can be assigned to, with the one it already has.
     *
     * @return list<string>
     */
    public static function packageNames(string $current): array
    {
        $component = Factory::getApplication()->bootComponent('com_breezingformsng');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $model = $component->getMVCFactory()->createModel('PackageTransfer', 'Administrator', ['ignore_request' => true]);

        if (!$model instanceof PackageTransferModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $packages = $model->getPackages();

        if ($current !== '' && !in_array($current, $packages, true)) {
            $packages[] = $current;
        }

        return $packages;
    }

    /**
     * @param array<string, mixed> $vars
     */
    public static function render(array $vars): void
    {
        extract($vars, EXTR_SKIP);

        require JPATH_ADMINISTRATOR . '/components/com_breezingformsng/layouts/forms/advanced_options.php';
    }
}
