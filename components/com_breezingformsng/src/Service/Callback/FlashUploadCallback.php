<?php

declare(strict_types=1);

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Site\Service\Callback;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\Input\Input;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\TemporaryUploadDirectory;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\FlashChunkAssembler;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\FlashUploadSizeValidator;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadDirectoryProtection;

/**
 * Chunked (legacy flash) upload endpoint (flashUpload=1).
 */
final class FlashUploadCallback
{
    private ?FlashChunkAssembler $chunkAssemblerService = null;

    public function __construct(
        private readonly CMSApplication $application,
        private readonly DatabaseInterface $database,
        private readonly FlashUploadSizeValidator $uploadSizeValidator,
    ) {
    }

    public function handle(): void
    {
        $db = $this->database;

    if (ob_get_level() > 0) {
        ob_end_clean();
    }
        // Responses are displayed as plain text by the uploader, never as HTML.
        $this->application->setHeader('Content-Type', 'text/plain; charset=utf-8', true);
        $this->application->sendHeaders();
    $input = $this->application->getInput();
    $uploadedFile = $input->files->get('Filedata', [], 'array');
    if (is_numeric($input->getString('form', '')) && $uploadedFile !== [] && $this->isUploadTarget($input)) {

        $formId = $input->getInt('form', -1);
        $itemName = $input->getString('itemName', '');
        $query = $db->createQuery()
            ->select(['form.id', 'form.template_code_processed', 'form.template_code'])
            ->from($db->quoteName('#__facileforms_forms', 'form'))
            ->from($db->quoteName('#__facileforms_elements', 'element'))
            ->where('form.id = :formId')
            ->where('form.published = 1')
            ->where('element.name = :itemName')
            ->where('element.form = :formId2')
            ->bind(':formId', $formId, ParameterType::INTEGER)
            ->bind(':itemName', $itemName, ParameterType::STRING)
            ->bind(':formId2', $formId, ParameterType::INTEGER);
        $db->setQuery($query);
        $objectList = $db->loadObjectList();
        $formIdCount = count($objectList);
        if ($formIdCount > 0) {
            $tempFile = (string) ($uploadedFile['tmp_name'] ?? '');
            $temporaryUploads = new TemporaryUploadDirectory((string) $this->application->get('tmp_path'));
            $targetPath = $temporaryUploads->path();
            if ($temporaryUploads->ensure()) {
                UploadDirectoryProtection::ensureWithinWebRoot($targetPath, JPATH_SITE);
                $secureTicket = $this->application->getSession()->get('secure_ticket', '');
                if ($secureTicket == '') {
                    $secureTicket = bin2hex(random_bytes(16));
                    $this->application->getSession()->set('secure_ticket', $secureTicket);
                }

                // plupload sends the chunk number, not a byte offset: derive the offset from the announced chunk size.
                $offset = $input->getInt('offset', -1);
                if ($offset < 0) {
                    $offset = max(0, $input->getInt('chunk', 0)) * FlashUploadSizeValidator::CHUNK_BYTES;
                }

                $targetFile = str_replace('//', '/', $targetPath) . 'chunks/' . $offset . '_' . bf_sanitizeFilename($input->getString('name', 'unknown')) . '_' . $input->getString('itemName', '') . '_' . $input->getString('bfFlashUploadTicket', '') . '_' . $secureTicket . '_chunktmp';
                $finaltargetFile = str_replace('//', '/', $targetPath) . bf_sanitizeFilename($input->getString('name', 'unknown')) . '_' . $input->getString('itemName', '') . '_' . $input->getString('bfFlashUploadTicket', '') . '_' . $secureTicket . '_flashtmp';

                $dataObject = json_decode(bf_b64dec($objectList[0]->template_code), true);
                $dataObject = \is_array($dataObject) ? $dataObject : [];
                $limit = $this->uploadSizeValidator->limitFor($dataObject, $itemName);

                if (!File::upload($tempFile, $targetFile)) {
                    echo Text::_('COM_BREEZINGFORMSNG_PROCESS_UPLOADFAILED');
                } else {
                    // The cumulative size and the offset are checked before the chunk is appended; a rejection deletes the partial file.
                    $status = $this->chunkAssembler()->appendAt($targetFile, $finaltargetFile, $targetPath, $offset, $limit['maxBytes']);

                    if ($status === FlashChunkAssembler::TOO_LARGE) {
                        echo ($limit['label'] !== '' ? $limit['label'] : $itemName) . ': ' . Text::sprintf(
                            'COM_BREEZINGFORMSNG_UPLOAD_FILE_TOO_LARGE_DETAIL',
                            basename(str_replace('\\', '/', $input->getString('name', 'unknown'))),
                            (string) $limit['maxBytes']
                        );
                        $this->application->close();
                    } elseif ($status !== FlashChunkAssembler::OK) {
                        echo Text::_('COM_BREEZINGFORMSNG_PROCESS_UPLOADFAILED');
                        $this->application->close();
                    }

                    File::delete($targetFile);
                }
            } else {
                echo Text::_('COM_BREEZINGFORMSNG_PROCESS_UPLOADFAILED');
            }
        } else {
            echo Text::_('JERROR_AN_ERROR_HAS_OCCURRED');
        }
    }
    $this->application->close();
    }

    /**
     * The ticket and the element name go into file names: the ticket must be
     * one the form renderer issued to this session (32 hex digits), the
     * element name must hold no path.
     */
    private function isUploadTarget(Input $input): bool
    {
        $ticket = $input->getString('bfFlashUploadTicket', '');
        $tickets = (array) $this->application->getSession()->get('bfFlashUploadTickets', []);

        return preg_match('/^[0-9a-f]{32}$/', $ticket) === 1
            && array_key_exists($ticket, $tickets)
            && preg_match('#[/\\\\\x00]|\.\.#', $input->getString('itemName', '')) !== 1;
    }

    private function chunkAssembler(): FlashChunkAssembler
    {
        return $this->chunkAssemblerService ??= new FlashChunkAssembler();
    }

}
