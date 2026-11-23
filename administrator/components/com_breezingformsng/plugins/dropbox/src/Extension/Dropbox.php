<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Plugin\BreezingformsAddons\Dropbox\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use Vcmb\Component\BreezingformsNG\Site\Event\SubmissionArtifactsEvent;
use Vcmb\Plugin\BreezingformsAddons\Dropbox\Service\DropboxUploader;

/** Uploads configured submission artifacts to Dropbox. */
final class Dropbox extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    protected $autoloadLanguage = true;

    /** @return array<string, string> */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [SubmissionArtifactsEvent::NAME => 'onSubmissionArtifacts'];
    }

    public function onSubmissionArtifacts(SubmissionArtifactsEvent $event): void
    {
        $form = $this->loadForm((int) $event->getArgument('formId'));
        if ($form === null || (int) ($form->dropbox_submission_enabled ?? 0) !== 1) {
            return;
        }
        $accessToken = trim((string) ($form->dropbox_email ?? ''));
        if ($accessToken === '') {
            return;
        }

        $folder = $this->remoteFolder(
            (string) (($form->dropbox_folder ?? '') ?: $event->getFormName())
        );
        $uploader = new DropboxUploader();
        foreach ($event->getArtifacts() as $artifact) {
            $path = (string) ($artifact['path'] ?? '');
            $name = basename((string) ($artifact['name'] ?? ''));
            if ($name === '' || !is_file($path) || !is_readable($path)) {
                continue;
            }

            $remotePath = '/' . ($folder !== '' ? $folder . '/' : '') . $name;
            try {
                $uploader->upload($accessToken, $remotePath, $path);
            } catch (\Throwable) {
                Log::add(
                    Text::_('PLG_BREEZINGFORMS_ADDONS_DROPBOX_UPLOAD_FAILED'),
                    Log::WARNING,
                    'com_breezingformsng'
                );
            }
        }
    }

    private function loadForm(int $formId): ?object
    {
        $database = $this->getDatabase();
        $query = $database->createQuery()
            ->select('*')
            ->from($database->quoteName('#__facileforms_forms'))
            ->where($database->quoteName('id') . ' = :formId')
            ->bind(':formId', $formId, ParameterType::INTEGER);

        $form = $database->setQuery($query)->loadObject();

        return is_object($form) ? $form : null;
    }

    private function remoteFolder(string $folder): string
    {
        $segments = preg_split('#[\\/]+#', trim($folder, " \\/"));
        if (!is_array($segments)) {
            return '';
        }

        return implode('/', array_values(array_filter(
            array_map('trim', $segments),
            static fn(string $segment): bool => $segment !== '' && $segment !== '.' && $segment !== '..'
        )));
    }
}
